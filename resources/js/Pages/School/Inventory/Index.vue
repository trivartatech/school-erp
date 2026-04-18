<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    assets:          Object,
    categories:      Array,
    stats:           Object,
    openMaintenance: Number,
});

const params    = new URLSearchParams(window.location.search);
const search    = ref(params.get('search')      ?? '');
const statusF   = ref(params.get('status')      ?? '');
const categoryF = ref(params.get('category_id') ?? '');

const applyFilters = () => router.get('/school/inventory', {
    search:      search.value      || undefined,
    status:      statusF.value     || undefined,
    category_id: categoryF.value   || undefined,
}, { preserveState: true, preserveScroll: true });

let fTimer;
watch([search, statusF, categoryF], () => { clearTimeout(fTimer); fTimer = setTimeout(applyFilters, 400); });

// ── Add Asset ──────────────────────────────────────────────────────────────
const showAdd = ref(false);
const addForm = useForm({
    category_id: '', name: '', asset_code: '', brand: '', model_no: '', serial_no: '',
    purchase_date: '', purchase_cost: '', supplier: '', warranty_until: '', useful_life_years: 5,
    condition: 'good', notes: '',
});
const submitAdd = () => addForm.post('/school/inventory', {
    preserveScroll: true,
    onSuccess: () => { showAdd.value = false; addForm.reset(); },
});

// ── Category ───────────────────────────────────────────────────────────────
const showCat = ref(false);
const catForm = useForm({ name: '', description: '' });
const submitCat = () => catForm.post('/school/inventory/categories', {
    preserveScroll: true,
    onSuccess: () => { showCat.value = false; catForm.reset(); },
});

// ── Assign ─────────────────────────────────────────────────────────────────
const showAssign   = ref(false);
const assignTarget = ref(null);
const assignForm   = useForm({ location: '', assignee_type: 'classroom', assigned_on: new Date().toISOString().slice(0,10), notes: '' });
const openAssign   = (a) => { assignTarget.value = a; showAssign.value = true; };
const submitAssign = () => assignForm.post(`/school/inventory/${assignTarget.value.id}/assign`, {
    preserveScroll: true,
    onSuccess: () => { showAssign.value = false; assignForm.reset(); },
});

// ── Maintenance ────────────────────────────────────────────────────────────
const showMaint   = ref(false);
const maintTarget = ref(null);
const maintForm   = useForm({ issue_description: '', type: 'corrective', reported_on: new Date().toISOString().slice(0,10) });
const openMaint   = (a) => { maintTarget.value = a; showMaint.value = true; };
const submitMaint = () => maintForm.post(`/school/inventory/${maintTarget.value.id}/maintenance`, {
    preserveScroll: true,
    onSuccess: () => { showMaint.value = false; maintForm.reset(); },
});

const returnAsset = (id) => {
    if (!confirm('Mark this asset as returned to inventory?')) return;
    useForm({}).patch(`/school/inventory/${id}/return`, { preserveScroll: true });
};

const statusBadge  = { available: '#10b981', assigned: '#3b82f6', under_maintenance: '#f59e0b', disposed: '#94a3b8' };
const statusLabel  = { available: 'Available', assigned: 'Assigned', under_maintenance: 'Maintenance', disposed: 'Disposed' };
const conditionDot = { excellent: '#10b981', good: '#3b82f6', fair: '#f59e0b', poor: '#ef4444', condemned: '#6b7280' };
const fmt     = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—';
const fmtCost = (n) => n ? '₹' + Number(n).toLocaleString('en-IN') : '—';
</script>

<template>
    <SchoolLayout title="Inventory">

        <!-- Header -->
        <div class="page-header">
            <div>
                <h1 class="page-header-title">Inventory & Assets</h1>
                <p style="color:#64748b;font-size:.875rem;margin-top:2px;">Track school assets, assignments and maintenance records.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn-outline" @click="showCat = true">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Category
                </button>
                <button class="btn-primary" @click="showAdd = true">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Asset
                </button>
            </div>
        </div>

        <!-- Stats row -->
        <div class="stats-row">
            <div class="stat-card stat-green">
                <div class="stat-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Available</div>
                    <div class="stat-value" style="color:#10b981;">{{ stats?.available?.count ?? 0 }}</div>
                    <div class="stat-sub">{{ fmtCost(stats?.available?.total_cost) }}</div>
                </div>
            </div>
            <div class="stat-card stat-blue">
                <div class="stat-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Assigned</div>
                    <div class="stat-value" style="color:#3b82f6;">{{ stats?.assigned?.count ?? 0 }}</div>
                    <div class="stat-sub">{{ fmtCost(stats?.assigned?.total_cost) }}</div>
                </div>
            </div>
            <div class="stat-card stat-amber">
                <div class="stat-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="stat-label">In Maintenance</div>
                    <div class="stat-value" style="color:#f59e0b;">{{ openMaintenance }}</div>
                    <div class="stat-sub">open tickets</div>
                </div>
            </div>
            <div class="stat-card stat-gray">
                <div class="stat-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div>
                    <div class="stat-label">Disposed</div>
                    <div class="stat-value" style="color:#64748b;">{{ stats?.disposed?.count ?? 0 }}</div>
                    <div class="stat-sub">written off</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="search-wrap">
                <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input v-model="search" class="search-input" placeholder="Search name, code, serial…" />
            </div>
            <select v-model="categoryF" class="filter-select">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }} ({{ c.assets_count }})</option>
            </select>
            <select v-model="statusF" class="filter-select">
                <option value="">All Statuses</option>
                <option value="available">Available</option>
                <option value="assigned">Assigned</option>
                <option value="under_maintenance">Under Maintenance</option>
                <option value="disposed">Disposed</option>
            </select>
        </div>

        <!-- Table -->
        <div class="card" style="overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Purchase</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in assets.data" :key="a.id">
                            <td>
                                <div class="asset-name">{{ a.name }}</div>
                                <div class="asset-meta">
                                    <span v-if="a.asset_code" class="meta-chip">{{ a.asset_code }}</span>
                                    <span v-if="a.brand" class="meta-chip">{{ a.brand }}</span>
                                    <span v-if="a.serial_no" class="meta-chip">S/N: {{ a.serial_no }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="cat-badge">{{ a.category?.name ?? '—' }}</span>
                            </td>
                            <td>
                                <div class="cost-val">{{ fmtCost(a.purchase_cost) }}</div>
                                <div class="cost-date">{{ fmt(a.purchase_date) }}</div>
                            </td>
                            <td>
                                <span class="condition-dot" :style="{ background: conditionDot[a.condition] }"></span>
                                <span class="condition-text" style="text-transform:capitalize;">{{ a.condition }}</span>
                            </td>
                            <td>
                                <span class="status-pill" :style="{ background: statusBadge[a.status] + '1a', color: statusBadge[a.status], border: '1px solid ' + statusBadge[a.status] + '40' }">
                                    {{ statusLabel[a.status] ?? a.status }}
                                </span>
                            </td>
                            <td>
                                <span v-if="a.active_assignment" style="font-size:.8rem;color:#475569;">{{ a.active_assignment.location }}</span>
                                <span v-else style="font-size:.8rem;color:#cbd5e1;">—</span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <button v-if="a.status === 'available'"              class="act-btn act-blue"  @click="openAssign(a)">Assign</button>
                                    <button v-if="a.status === 'assigned'"               class="act-btn act-gray"  @click="returnAsset(a.id)">Return</button>
                                    <button v-if="!['under_maintenance','disposed'].includes(a.status)" class="act-btn act-amber" @click="openMaint(a)">Issue</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!assets.data?.length">
                            <td colspan="7" class="empty-row">
                                <svg width="40" height="40" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" style="margin-bottom:8px;display:block;margin-inline:auto;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                                No assets found. Add your first asset to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="assets.last_page > 1" class="pagination">
                <a v-for="p in assets.links" :key="p.label"
                   :href="p.url ?? '#'" v-html="p.label"
                   :class="['page-link', { active: p.active, disabled: !p.url }]">
                </a>
            </div>
        </div>

        <!-- ── Add Asset Modal ─────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showAdd" class="backdrop" @click.self="showAdd = false">
                <div class="modal-box" style="max-width:580px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#ede9fe;color:#7c3aed;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">Add New Asset</h3>
                            <p class="modal-sub">Fill in the asset details below</p>
                        </div>
                        <button class="modal-close" @click="showAdd = false">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAdd">
                        <div class="modal-body">
                            <!-- Asset Name (full width) -->
                            <div class="field full">
                                <label class="field-label">Asset Name <span class="req">*</span></label>
                                <input v-model="addForm.name" class="field-input" required placeholder="e.g. Dell Latitude Laptop" />
                                <p v-if="addForm.errors.name" class="field-error">{{ addForm.errors.name }}</p>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Category <span class="req">*</span></label>
                                    <select v-model="addForm.category_id" class="field-input" required>
                                        <option value="">Select category…</option>
                                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                    <p v-if="addForm.errors.category_id" class="field-error">{{ addForm.errors.category_id }}</p>
                                </div>
                                <div class="field">
                                    <label class="field-label">Asset Code</label>
                                    <input v-model="addForm.asset_code" class="field-input" placeholder="e.g. LAP-001" />
                                </div>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Brand</label>
                                    <input v-model="addForm.brand" class="field-input" placeholder="e.g. Dell, HP, Samsung" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Serial No</label>
                                    <input v-model="addForm.serial_no" class="field-input" placeholder="e.g. SN123456" />
                                </div>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Purchase Date</label>
                                    <input v-model="addForm.purchase_date" class="field-input" type="date" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Purchase Cost (₹)</label>
                                    <input v-model="addForm.purchase_cost" class="field-input" type="number" min="0" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Useful Life (years)</label>
                                    <input v-model="addForm.useful_life_years" class="field-input" type="number" min="1" max="50" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Condition</label>
                                    <select v-model="addForm.condition" class="field-input">
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                        <option value="condemned">Condemned</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field full">
                                <label class="field-label">Supplier / Vendor</label>
                                <input v-model="addForm.supplier" class="field-input" placeholder="e.g. ABC Electronics" />
                            </div>

                            <div class="field full">
                                <label class="field-label">Notes</label>
                                <textarea v-model="addForm.notes" class="field-input" rows="2" placeholder="Any additional remarks…"></textarea>
                            </div>
                        </div>

                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showAdd = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="addForm.processing">
                                <span v-if="addForm.processing">Saving…</span>
                                <span v-else>Add Asset</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Category Modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showCat" class="backdrop" @click.self="showCat = false">
                <div class="modal-box" style="max-width:400px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#dcfce7;color:#16a34a;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">New Category</h3>
                            <p class="modal-sub">Group your assets by type</p>
                        </div>
                        <button class="modal-close" @click="showCat = false">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitCat">
                        <div class="modal-body">
                            <div class="field full">
                                <label class="field-label">Category Name <span class="req">*</span></label>
                                <input v-model="catForm.name" class="field-input" required placeholder="e.g. Electronics, Furniture…" />
                            </div>
                            <div class="field full">
                                <label class="field-label">Description</label>
                                <input v-model="catForm.description" class="field-input" placeholder="Optional description" />
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showCat = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="catForm.processing">Create Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Assign Modal ────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showAssign" class="backdrop" @click.self="showAssign = false">
                <div class="modal-box" style="max-width:420px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#dbeafe;color:#2563eb;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">Assign Asset</h3>
                            <p class="modal-sub">{{ assignTarget?.name }}</p>
                        </div>
                        <button class="modal-close" @click="showAssign = false">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitAssign">
                        <div class="modal-body">
                            <div class="field full">
                                <label class="field-label">Location / Room <span class="req">*</span></label>
                                <input v-model="assignForm.location" class="field-input" required placeholder="e.g. Computer Lab, Room 101" />
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Assign To</label>
                                    <select v-model="assignForm.assignee_type" class="field-input">
                                        <option value="classroom">Classroom</option>
                                        <option value="staff">Staff</option>
                                        <option value="department">Department</option>
                                        <option value="general">General Use</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label class="field-label">Assigned On <span class="req">*</span></label>
                                    <input v-model="assignForm.assigned_on" class="field-input" type="date" required />
                                </div>
                            </div>
                            <div class="field full">
                                <label class="field-label">Notes</label>
                                <textarea v-model="assignForm.notes" class="field-input" rows="2" placeholder="Optional remarks…"></textarea>
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showAssign = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="assignForm.processing">Assign</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Maintenance Modal ───────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showMaint" class="backdrop" @click.self="showMaint = false">
                <div class="modal-box" style="max-width:420px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#fef3c7;color:#d97706;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">Log Issue</h3>
                            <p class="modal-sub">{{ maintTarget?.name }}</p>
                        </div>
                        <button class="modal-close" @click="showMaint = false">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitMaint">
                        <div class="modal-body">
                            <div class="field full">
                                <label class="field-label">Issue Description <span class="req">*</span></label>
                                <textarea v-model="maintForm.issue_description" class="field-input" rows="3" required placeholder="Describe the problem…"></textarea>
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Type <span class="req">*</span></label>
                                    <select v-model="maintForm.type" class="field-input" required>
                                        <option value="corrective">Corrective</option>
                                        <option value="preventive">Preventive</option>
                                        <option value="inspection">Inspection</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label class="field-label">Reported On <span class="req">*</span></label>
                                    <input v-model="maintForm.reported_on" class="field-input" type="date" required />
                                </div>
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showMaint = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="maintForm.processing">Log Issue</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </SchoolLayout>
</template>

<style scoped>
/* ── Buttons ────────────────────────────────────────────────────────────── */
.btn-primary {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #3b82f6; color: #fff;
    border: none; border-radius: 8px; font-size: .875rem; font-weight: 600;
    cursor: pointer; transition: background .15s;
}
.btn-primary:hover:not(:disabled) { background: #2563eb; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-outline {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #fff; color: #374151;
    border: 1px solid #d1d5db; border-radius: 8px; font-size: .875rem; font-weight: 500;
    cursor: pointer; transition: background .15s;
}
.btn-outline:hover { background: #f9fafb; }

/* ── Stats ──────────────────────────────────────────────────────────────── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .stats-row { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 500px) { .stats-row { grid-template-columns: 1fr; } }

.stat-card {
    display: flex; align-items: flex-start; gap: 14px;
    background: #fff; border-radius: 12px;
    padding: 18px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.stat-green .stat-icon { background: #dcfce7; color: #16a34a; }
.stat-blue  .stat-icon { background: #dbeafe; color: #2563eb; }
.stat-amber .stat-icon { background: #fef3c7; color: #d97706; }
.stat-gray  .stat-icon { background: #f1f5f9; color: #64748b; }
.stat-label { font-size: .7rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.1; margin-top: 2px; }
.stat-sub   { font-size: .72rem; color: #94a3b8; margin-top: 2px; }

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.filter-bar {
    display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.search-wrap {
    flex: 1; min-width: 220px;
    position: relative;
}
.search-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; pointer-events: none;
}
.search-input {
    width: 100%; padding: 9px 12px 9px 36px;
    border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: .875rem; color: #1e293b; background: #fff;
    outline: none; transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px #3b82f620; }
.filter-select {
    padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: .875rem; color: #374151; background: #fff;
    outline: none; cursor: pointer; min-width: 150px;
    transition: border-color .15s;
}
.filter-select:focus { border-color: #3b82f6; }

/* ── Table ──────────────────────────────────────────────────────────────── */
.inv-table {
    width: 100%; border-collapse: collapse;
}
.inv-table th {
    padding: 11px 16px; text-align: left;
    font-size: .72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .05em;
    background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.inv-table td {
    padding: 12px 16px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.inv-table tr:last-child td { border-bottom: none; }
.inv-table tr:hover td { background: #fafbff; }

.asset-name { font-size: .875rem; font-weight: 600; color: #1e293b; }
.asset-meta { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 3px; }
.meta-chip  {
    font-size: .68rem; color: #64748b; background: #f1f5f9;
    border: 1px solid #e2e8f0; border-radius: 4px; padding: 1px 6px;
}
.cat-badge {
    font-size: .75rem; font-weight: 500; color: #6d28d9;
    background: #ede9fe; padding: 2px 8px; border-radius: 20px;
}
.cost-val  { font-size: .85rem; font-weight: 600; color: #1e293b; }
.cost-date { font-size: .72rem; color: #94a3b8; margin-top: 1px; }

.condition-dot {
    display: inline-block; width: 7px; height: 7px;
    border-radius: 50%; margin-right: 5px; vertical-align: middle;
}
.condition-text { font-size: .8rem; color: #475569; }
.status-pill {
    display: inline-block; font-size: .72rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px; white-space: nowrap;
}
.act-btn {
    font-size: .72rem; font-weight: 600; padding: 4px 10px;
    border-radius: 6px; border: none; cursor: pointer;
    transition: opacity .15s;
}
.act-btn:hover { opacity: .8; }
.act-blue  { background: #dbeafe; color: #2563eb; }
.act-gray  { background: #f1f5f9; color: #475569; }
.act-amber { background: #fef3c7; color: #d97706; }

.empty-row {
    text-align: center; padding: 48px 24px;
    color: #94a3b8; font-size: .9rem;
}

/* ── Pagination ─────────────────────────────────────────────────────────── */
.pagination {
    display: flex; justify-content: center; gap: 4px;
    padding: 14px; border-top: 1px solid #f1f5f9;
}
.page-link {
    padding: 5px 11px; border-radius: 6px; font-size: .8rem; font-weight: 500;
    text-decoration: none; background: #f1f5f9; color: #475569;
    transition: background .15s;
}
.page-link:hover { background: #e2e8f0; }
.page-link.active  { background: #3b82f6; color: #fff; }
.page-link.disabled { opacity: .4; pointer-events: none; }

/* ── Modals ─────────────────────────────────────────────────────────────── */
.backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.5); backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000; padding: 16px;
}
.modal-box {
    background: #fff; border-radius: 16px; width: 100%;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
    max-height: 92vh; overflow-y: auto;
}
.modal-head {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 20px 20px 16px; border-bottom: 1px solid #f1f5f9;
    position: sticky; top: 0; background: #fff; z-index: 1;
    border-radius: 16px 16px 0 0;
}
.modal-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.modal-sub   { font-size: .8rem; color: #64748b; margin: 2px 0 0; }
.modal-close {
    margin-left: auto; background: #f1f5f9; border: none; border-radius: 8px;
    width: 34px; height: 34px; cursor: pointer; display: flex;
    align-items: center; justify-content: center; color: #64748b;
    transition: background .15s; flex-shrink: 0;
}
.modal-close:hover { background: #e2e8f0; color: #0f172a; }

.modal-body {
    padding: 20px; display: flex; flex-direction: column; gap: 14px;
}
.modal-foot {
    padding: 16px 20px; border-top: 1px solid #f1f5f9;
    background: #f8fafc; display: flex; justify-content: flex-end;
    gap: 10px; border-radius: 0 0 16px 16px;
    position: sticky; bottom: 0;
}

/* ── Form fields ────────────────────────────────────────────────────────── */
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field.full { grid-column: span 2; }
.field-label {
    display: block; font-size: .78rem; font-weight: 600;
    color: #374151; margin-bottom: 5px;
}
.req { color: #ef4444; }
.field-input {
    width: 100%; padding: 9px 12px;
    border: 1px solid #d1d5db; border-radius: 8px;
    font-size: .875rem; color: #1e293b; background: #fff;
    outline: none; transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.field-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px #3b82f620; }
textarea.field-input { resize: vertical; }
.field-error { font-size: .75rem; color: #ef4444; margin-top: 4px; }
</style>
