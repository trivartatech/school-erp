<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    assets:          Object,
    categories:      Array,
    stats:           Object,
    openMaintenance: Number,
});

const params      = new URLSearchParams(window.location.search);
const search      = ref(params.get('search')      ?? '');
const statusF     = ref(params.get('status')      ?? '');
const categoryF   = ref(params.get('category_id') ?? '');

const applyFilters = () => {
    router.get('/school/inventory', {
        search:      search.value || undefined,
        status:      statusF.value || undefined,
        category_id: categoryF.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

let fTimer;
watch([search, statusF, categoryF], () => { clearTimeout(fTimer); fTimer = setTimeout(applyFilters, 400); });

// ── Add Asset Modal ────────────────────────────────────────────────────────
const showAdd   = ref(false);
const addForm   = useForm({
    category_id: '', name: '', asset_code: '', brand: '', model_no: '', serial_no: '',
    purchase_date: '', purchase_cost: '', supplier: '', warranty_until: '', useful_life_years: 5,
    condition: 'good', notes: '',
});
const submitAdd = () => addForm.post('/school/inventory', { preserveScroll: true, onSuccess: () => { showAdd.value = false; addForm.reset(); } });

// ── Category Modal ─────────────────────────────────────────────────────────
const showCat    = ref(false);
const catForm    = useForm({ name: '', description: '' });
const submitCat  = () => catForm.post('/school/inventory/categories', { preserveScroll: true, onSuccess: () => { showCat.value = false; catForm.reset(); } });

// ── Assign Modal ───────────────────────────────────────────────────────────
const showAssign  = ref(false);
const assignTarget = ref(null);
const assignForm = useForm({
    location: '', assignee_type: 'classroom', assigned_on: new Date().toISOString().slice(0, 10), notes: '',
});
const openAssign = (asset) => { assignTarget.value = asset; showAssign.value = true; };
const submitAssign = () => {
    assignForm.post(`/school/inventory/${assignTarget.value.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => { showAssign.value = false; assignForm.reset(); },
    });
};

// ── Maintenance Modal ──────────────────────────────────────────────────────
const showMaint   = ref(false);
const maintTarget = ref(null);
const maintForm   = useForm({ issue_description: '', type: 'corrective', reported_on: new Date().toISOString().slice(0, 10) });
const openMaint   = (asset) => { maintTarget.value = asset; showMaint.value = true; };
const submitMaint = () => {
    maintForm.post(`/school/inventory/${maintTarget.value.id}/maintenance`, {
        preserveScroll: true,
        onSuccess: () => { showMaint.value = false; maintForm.reset(); },
    });
};

const returnAsset = (id) => {
    if (!confirm('Mark this asset as returned?')) return;
    useForm({}).patch(`/school/inventory/${id}/return`, { preserveScroll: true });
};

const statusColor  = { available: 'badge-green', assigned: 'badge-blue', under_maintenance: 'badge-amber', disposed: 'badge-gray' };
const conditionColor = { excellent: '#10b981', good: '#3b82f6', fair: '#f59e0b', poor: '#ef4444', condemned: '#6b7280' };
const fmt = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—';
const fmtCost = (n) => n ? '₹' + Number(n).toLocaleString('en-IN') : '—';
</script>

<template>
    <SchoolLayout title="Inventory">
        <div class="page-header">
            <div>
                <h1 class="page-header-title">Inventory & Assets</h1>
                <p style="color:#64748b;font-size:.9rem;">Track school assets, assignments, and maintenance.</p>
            </div>
            <div style="display:flex;gap:8px;">
                <Button variant="secondary" @click="showCat = true">+ Category</Button>
                <Button @click="showAdd = true">+ Add Asset</Button>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
            <div class="card" style="padding:14px;">
                <div style="font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Available</div>
                <div style="font-size:1.6rem;font-weight:700;color:#10b981;">{{ stats?.available?.count ?? 0 }}</div>
                <div style="font-size:.75rem;color:#94a3b8;">{{ fmtCost(stats?.available?.total_cost) }}</div>
            </div>
            <div class="card" style="padding:14px;">
                <div style="font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Assigned</div>
                <div style="font-size:1.6rem;font-weight:700;color:#3b82f6;">{{ stats?.assigned?.count ?? 0 }}</div>
            </div>
            <div class="card" style="padding:14px;">
                <div style="font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">In Maintenance</div>
                <div style="font-size:1.6rem;font-weight:700;color:#f59e0b;">{{ openMaintenance }}</div>
            </div>
            <div class="card" style="padding:14px;">
                <div style="font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Disposed</div>
                <div style="font-size:1.6rem;font-weight:700;color:#6b7280;">{{ stats?.disposed?.count ?? 0 }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom:16px;padding:12px 16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <input v-model="search" placeholder="Search name, code, serial..." style="flex:1;min-width:200px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;" />
            <select v-model="categoryF" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }} ({{ c.assets_count }})</option>
            </select>
            <select v-model="statusF" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;">
                <option value="">All Statuses</option>
                <option value="available">Available</option>
                <option value="assigned">Assigned</option>
                <option value="under_maintenance">Under Maintenance</option>
                <option value="disposed">Disposed</option>
            </select>
        </div>

        <!-- Asset Table -->
        <div class="card">
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Purchase</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in assets.data" :key="a.id">
                            <td>
                                <div style="font-weight:500;">{{ a.name }}</div>
                                <div style="font-size:.75rem;color:#94a3b8;">
                                    <span v-if="a.asset_code">{{ a.asset_code }}</span>
                                    <span v-if="a.brand"> · {{ a.brand }}</span>
                                    <span v-if="a.serial_no"> · S/N: {{ a.serial_no }}</span>
                                </div>
                            </td>
                            <td style="font-size:.85rem;">{{ a.category?.name }}</td>
                            <td style="font-size:.85rem;">
                                <div>{{ fmtCost(a.purchase_cost) }}</div>
                                <div style="color:#94a3b8;font-size:.75rem;">{{ fmt(a.purchase_date) }}</div>
                            </td>
                            <td>
                                <span :style="{ fontSize:'.8rem', fontWeight:600, color: conditionColor[a.condition] }">{{ a.condition }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="statusColor[a.status]" style="font-size:.75rem;text-transform:capitalize;">{{ a.status?.replace('_',' ') }}</span>
                            </td>
                            <td style="font-size:.8rem;color:#64748b;">
                                <span v-if="a.active_assignment">{{ a.active_assignment.location }}</span>
                                <span v-else style="color:#94a3b8;">—</span>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <Button v-if="a.status === 'available'" size="xs" variant="secondary" @click="openAssign(a)">Assign</Button>
                                    <Button v-if="a.status === 'assigned'"  size="xs" variant="secondary" @click="returnAsset(a.id)">Return</Button>
                                    <Button v-if="a.status !== 'under_maintenance' && a.status !== 'disposed'" size="xs" variant="secondary" @click="openMaint(a)">Issue</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!assets.data?.length">
                            <td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">No assets found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="assets.last_page > 1" style="display:flex;justify-content:center;gap:4px;padding:16px;">
                <a v-for="p in assets.links" :key="p.label" :href="p.url ?? '#'"
                   v-html="p.label"
                   :style="{ padding:'6px 12px', borderRadius:'6px', fontSize:'.85rem', background: p.active ? '#3b82f6' : '#f1f5f9', color: p.active ? '#fff' : '#475569', textDecoration:'none', pointerEvents: p.url ? 'auto' : 'none', opacity: p.url ? 1 : 0.4 }">
                </a>
            </div>
        </div>

        <!-- Add Asset Modal -->
        <Teleport to="body">
            <div v-if="showAdd" class="modal-backdrop" @click.self="showAdd = false">
                <div class="modal" style="max-width:560px;width:100%;max-height:90vh;overflow-y:auto;">
                    <div class="modal-header">
                        <h3 class="modal-title">Add Asset</h3>
                        <button @click="showAdd = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitAdd">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div class="form-field" style="margin:0;grid-column:span 2;">
                                    <label>Asset Name *</label>
                                    <input v-model="addForm.name" required placeholder="e.g. Dell Laptop" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Category *</label>
                                    <select v-model="addForm.category_id" required>
                                        <option value="">Select...</option>
                                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Asset Code</label>
                                    <input v-model="addForm.asset_code" placeholder="e.g. LAP-001" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Brand</label>
                                    <input v-model="addForm.brand" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Serial No</label>
                                    <input v-model="addForm.serial_no" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Purchase Date</label>
                                    <input v-model="addForm.purchase_date" type="date" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Purchase Cost (₹)</label>
                                    <input v-model="addForm.purchase_cost" type="number" min="0" step="0.01" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Useful Life (years)</label>
                                    <input v-model="addForm.useful_life_years" type="number" min="1" max="50" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Condition</label>
                                    <select v-model="addForm.condition">
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Notes</label>
                                <textarea v-model="addForm.notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showAdd = false">Cancel</Button>
                            <Button type="submit" :loading="addForm.processing">Add Asset</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Category Modal -->
        <Teleport to="body">
            <div v-if="showCat" class="modal-backdrop" @click.self="showCat = false">
                <div class="modal" style="max-width:400px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">New Category</h3>
                        <button @click="showCat = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitCat">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                            <div class="form-field"><label>Name *</label><input v-model="catForm.name" required /></div>
                            <div class="form-field"><label>Description</label><input v-model="catForm.description" /></div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showCat = false">Cancel</Button>
                            <Button type="submit" :loading="catForm.processing">Create</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Assign Modal -->
        <Teleport to="body">
            <div v-if="showAssign" class="modal-backdrop" @click.self="showAssign = false">
                <div class="modal" style="max-width:420px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">Assign: {{ assignTarget?.name }}</h3>
                        <button @click="showAssign = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitAssign">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                            <div class="form-field"><label>Location / Room *</label><input v-model="assignForm.location" required placeholder="e.g. Lab 2, Room 101, Staff Room" /></div>
                            <div class="form-field"><label>Assign To Type</label>
                                <select v-model="assignForm.assignee_type">
                                    <option value="classroom">Classroom</option>
                                    <option value="staff">Staff</option>
                                    <option value="department">Department</option>
                                    <option value="general">General Use</option>
                                </select>
                            </div>
                            <div class="form-field"><label>Assigned On *</label><input v-model="assignForm.assigned_on" type="date" required /></div>
                            <div class="form-field"><label>Notes</label><textarea v-model="assignForm.notes" rows="2"></textarea></div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showAssign = false">Cancel</Button>
                            <Button type="submit" :loading="assignForm.processing">Assign</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Maintenance Modal -->
        <Teleport to="body">
            <div v-if="showMaint" class="modal-backdrop" @click.self="showMaint = false">
                <div class="modal" style="max-width:420px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">Log Issue: {{ maintTarget?.name }}</h3>
                        <button @click="showMaint = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitMaint">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                            <div class="form-field"><label>Issue Description *</label><textarea v-model="maintForm.issue_description" rows="3" required></textarea></div>
                            <div class="form-field"><label>Type *</label>
                                <select v-model="maintForm.type" required>
                                    <option value="corrective">Corrective</option>
                                    <option value="preventive">Preventive</option>
                                    <option value="inspection">Inspection</option>
                                </select>
                            </div>
                            <div class="form-field"><label>Reported On *</label><input v-model="maintForm.reported_on" type="date" required /></div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showMaint = false">Cancel</Button>
                            <Button type="submit" :loading="maintForm.processing">Log Issue</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </SchoolLayout>
</template>

<style scoped>
.modal-backdrop { position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:1000; }
.modal { background:#fff;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1); }
.modal-header { padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center; }
.modal-title { font-size:1rem;font-weight:700;color:#1e293b; }
.modal-close { background:none;border:none;font-size:1.5rem;line-height:1;color:#94a3b8;cursor:pointer; }
.modal-body { padding:20px; }
.modal-footer { padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 12px 12px;display:flex;justify-content:flex-end;gap:10px; }
</style>
