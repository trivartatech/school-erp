<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SchoolLayout from '@/Layouts/SchoolLayout.vue';

const props = defineProps({
    suppliers: Array,
});

const pageErrors   = usePage().props.errors ?? {};
const showModal    = ref(false);
const editing      = ref(null);
const deleteTarget = ref(null);

const blankForm = {
    name: '', contact_person: '', phone: '', email: '',
    gstin: '', address: '', city: '', state: '', website: '', notes: '',
};
const form       = useForm({ ...blankForm });
const deleteForm = useForm({});

function openAdd() {
    editing.value = null;
    form.reset();
    showModal.value = true;
}
function openEdit(s) {
    editing.value           = s;
    form.name           = s.name           || '';
    form.contact_person = s.contact_person || '';
    form.phone          = s.phone          || '';
    form.email          = s.email          || '';
    form.gstin          = s.gstin          || '';
    form.address        = s.address        || '';
    form.city           = s.city           || '';
    form.state          = s.state          || '';
    form.website        = s.website        || '';
    form.notes          = s.notes          || '';
    showModal.value = true;
}
function submitForm() {
    const opts = { preserveScroll: true, onSuccess: () => { showModal.value = false; } };
    editing.value
        ? form.put(`/school/inventory-suppliers/${editing.value.id}`, opts)
        : form.post('/school/inventory-suppliers', opts);
}
function doDelete() {
    deleteForm.delete(`/school/inventory-suppliers/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
}

const linkedCount = () => (props.suppliers ?? []).filter(s => s.assets_count > 0).length;
</script>

<template>
    <SchoolLayout title="Suppliers">

        <!-- Header -->
        <div class="page-header">
            <div>
                <div class="breadcrumb">
                    <a href="/school/inventory">Inventory</a>
                    <span class="sep">/</span>
                    <span>Suppliers</span>
                </div>
                <h1 class="page-header-title">Item Suppliers</h1>
                <p style="color:#64748b;font-size:.875rem;margin-top:2px;">Manage your vendors and procurement contacts.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="/school/inventory" class="btn-outline">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                    Assets
                </a>
                <a href="/school/inventory-stores" class="btn-outline">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                    Stores
                </a>
                <button class="btn-primary" @click="openAdd">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Supplier
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="flash-success">{{ $page.props.flash.success }}</div>
        <div v-if="pageErrors.supplier" class="flash-error">{{ pageErrors.supplier }}</div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card stat-blue">
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                <div>
                    <div class="stat-label">Total Suppliers</div>
                    <div class="stat-value" style="color:#3b82f6;">{{ suppliers.length }}</div>
                    <div class="stat-sub">registered</div>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div>
                    <div class="stat-label">Linked to Assets</div>
                    <div class="stat-value" style="color:#10b981;">{{ linkedCount() }}</div>
                    <div class="stat-sub">active suppliers</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card" style="overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone / Email</th>
                            <th>City / State</th>
                            <th>GSTIN</th>
                            <th style="text-align:center;">Assets</th>
                            <th style="text-align:center;">Items</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in suppliers" :key="s.id">
                            <td>
                                <div style="font-weight:600;font-size:.875rem;color:#1e293b;">{{ s.name }}</div>
                                <div v-if="s.website" style="font-size:.72rem;color:#94a3b8;">{{ s.website }}</div>
                            </td>
                            <td style="font-size:.85rem;color:#475569;">{{ s.contact_person || '—' }}</td>
                            <td>
                                <div v-if="s.phone" style="font-size:.82rem;color:#374151;">{{ s.phone }}</div>
                                <div v-if="s.email" style="font-size:.72rem;color:#64748b;">{{ s.email }}</div>
                                <span v-if="!s.phone && !s.email" style="color:#cbd5e1;font-size:.8rem;">—</span>
                            </td>
                            <td style="font-size:.82rem;color:#475569;">
                                {{ [s.city, s.state].filter(Boolean).join(', ') || '—' }}
                            </td>
                            <td>
                                <span v-if="s.gstin" style="font-family:monospace;font-size:.78rem;color:#374151;background:#f1f5f9;padding:2px 7px;border-radius:4px;">{{ s.gstin }}</span>
                                <span v-else style="color:#cbd5e1;font-size:.8rem;">—</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="count-pill count-blue">{{ s.assets_count }}</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="count-pill count-purple">{{ s.store_items_count }}</span>
                            </td>
                            <td>
                                <div style="display:flex;gap:5px;justify-content:flex-end;">
                                    <button class="act-btn act-amber" @click="openEdit(s)">Edit</button>
                                    <button class="act-btn act-red" :disabled="s.assets_count > 0"
                                        :title="s.assets_count > 0 ? 'Linked to assets — cannot delete' : 'Delete'"
                                        @click="deleteTarget = s">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!suppliers.length">
                            <td colspan="8" class="empty-row">
                                <svg width="40" height="40" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" style="margin-bottom:8px;display:block;margin-inline:auto;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                No suppliers yet. Add your first supplier to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="backdrop" @click.self="showModal = false">
                <div class="modal-box" style="max-width:580px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#dbeafe;color:#2563eb;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">{{ editing ? 'Edit Supplier' : 'Add Supplier' }}</h3>
                            <p class="modal-sub">{{ editing ? editing.name : 'Fill in the supplier details' }}</p>
                        </div>
                        <button class="modal-close" @click="showModal = false"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="submitForm">
                        <div class="modal-body">
                            <div class="field full">
                                <label class="field-label">Supplier Name <span class="req">*</span></label>
                                <input v-model="form.name" class="field-input" required placeholder="e.g. Tech Solutions Pvt Ltd" />
                                <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Contact Person</label>
                                    <input v-model="form.contact_person" class="field-input" placeholder="Name of your point of contact" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Phone</label>
                                    <input v-model="form.phone" class="field-input" maxlength="20" placeholder="+91 9876543210" />
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Email</label>
                                    <input v-model="form.email" type="email" class="field-input" placeholder="supplier@example.com" />
                                    <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
                                </div>
                                <div class="field">
                                    <label class="field-label">GSTIN</label>
                                    <input v-model="form.gstin" class="field-input" maxlength="20" placeholder="29ABCDE1234F1Z5" />
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">City</label>
                                    <input v-model="form.city" class="field-input" />
                                </div>
                                <div class="field">
                                    <label class="field-label">State</label>
                                    <input v-model="form.state" class="field-input" />
                                </div>
                            </div>
                            <div class="field full">
                                <label class="field-label">Website</label>
                                <input v-model="form.website" class="field-input" placeholder="https://" />
                                <p v-if="form.errors.website" class="field-error">{{ form.errors.website }}</p>
                            </div>
                            <div class="field full">
                                <label class="field-label">Address</label>
                                <textarea v-model="form.address" class="field-input" rows="2" placeholder="Street, area…"></textarea>
                            </div>
                            <div class="field full">
                                <label class="field-label">Notes</label>
                                <textarea v-model="form.notes" class="field-input" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showModal = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : (editing ? 'Update Supplier' : 'Add Supplier') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete confirm -->
        <Teleport to="body">
            <div v-if="deleteTarget" class="backdrop" @click.self="deleteTarget = null">
                <div class="modal-box" style="max-width:420px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#fee2e2;color:#dc2626;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>
                        <div><h3 class="modal-title">Delete Supplier</h3><p class="modal-sub">{{ deleteTarget.name }}</p></div>
                        <button class="modal-close" @click="deleteTarget = null"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="modal-body">
                        <div class="dispose-warning">This will permanently remove <strong>{{ deleteTarget.name }}</strong> from your supplier list. This cannot be undone.</div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="deleteTarget = null">Cancel</button>
                        <button class="btn-danger" :disabled="deleteForm.processing" @click="doDelete">
                            {{ deleteForm.processing ? 'Deleting…' : 'Delete Supplier' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </SchoolLayout>
</template>

<style scoped>
.btn-primary  { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;text-decoration:none; }
.btn-primary:hover:not(:disabled) { background:#2563eb; }
.btn-primary:disabled { opacity:.6;cursor:not-allowed; }
.btn-outline  { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer;transition:background .15s;text-decoration:none; }
.btn-outline:hover { background:#f9fafb; }
.btn-danger   { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s; }
.btn-danger:hover:not(:disabled) { background:#b91c1c; }
.btn-danger:disabled { opacity:.6;cursor:not-allowed; }

.breadcrumb   { display:flex;align-items:center;gap:6px;font-size:.78rem;color:#94a3b8;margin-bottom:4px; }
.breadcrumb a { color:#64748b;text-decoration:none; } .breadcrumb a:hover { color:#3b82f6; }
.sep          { color:#cbd5e1; }
.page-header  { display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap; }
.page-header-title { font-size:1.5rem;font-weight:800;color:#0f172a;margin:0; }

.flash-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:10px;padding:10px 16px;font-size:.85rem;margin-bottom:16px; }
.flash-error   { background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:10px 16px;font-size:.85rem;margin-bottom:16px; }

.stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px; }
@media (max-width:900px) { .stats-row { grid-template-columns:repeat(2,1fr); } }
.stat-card { display:flex;align-items:flex-start;gap:14px;background:#fff;border-radius:12px;padding:18px 20px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat-icon  { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.stat-green .stat-icon { background:#dcfce7;color:#16a34a; }
.stat-blue  .stat-icon { background:#dbeafe;color:#2563eb; }
.stat-label { font-size:.7rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em; }
.stat-value { font-size:1.75rem;font-weight:800;line-height:1.1;margin-top:2px; }
.stat-sub   { font-size:.72rem;color:#94a3b8;margin-top:2px; }

.card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05); }
.inv-table { width:100%;border-collapse:collapse; }
.inv-table th { padding:11px 16px;text-align:left;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;background:#f8fafc;border-bottom:1px solid #e2e8f0;white-space:nowrap; }
.inv-table td { padding:12px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.inv-table tr:last-child td { border-bottom:none; }
.inv-table tr:hover td { background:#fafbff; }

.act-btn { font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;transition:opacity .15s;white-space:nowrap; }
.act-btn:hover { opacity:.8; }
.act-btn:disabled { opacity:.4;cursor:not-allowed; }
.act-amber { background:#fef3c7;color:#d97706; }
.act-red   { background:#fee2e2;color:#dc2626; }

.count-pill   { display:inline-block;font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:20px; }
.count-blue   { background:#dbeafe;color:#2563eb; }
.count-purple { background:#ede9fe;color:#7c3aed; }

.empty-row { text-align:center;padding:48px 24px;color:#94a3b8;font-size:.9rem; }

.backdrop   { position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;z-index:1000;padding:16px; }
.modal-box  { background:#fff;border-radius:16px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);max-height:92vh;overflow-y:auto; }
.modal-head { display:flex;align-items:flex-start;gap:14px;padding:20px 20px 16px;border-bottom:1px solid #f1f5f9;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0; }
.modal-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.modal-title { font-size:1rem;font-weight:700;color:#0f172a;margin:0; }
.modal-sub   { font-size:.8rem;color:#64748b;margin:2px 0 0; }
.modal-close { margin-left:auto;background:#f1f5f9;border:none;border-radius:8px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:background .15s;flex-shrink:0; }
.modal-close:hover { background:#e2e8f0;color:#0f172a; }
.modal-body  { padding:20px;display:flex;flex-direction:column;gap:14px; }
.modal-foot  { padding:16px 20px;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;justify-content:flex-end;gap:10px;border-radius:0 0 16px 16px;position:sticky;bottom:0; }

.field-row  { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
.field.full { grid-column:span 2; }
.field-label { display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:5px; }
.req         { color:#ef4444; }
.field-input { width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.875rem;color:#1e293b;background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box; }
.field-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px #3b82f620; }
textarea.field-input { resize:vertical; }
.field-error { font-size:.75rem;color:#ef4444;margin-top:4px; }
.dispose-warning { background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#92400e;line-height:1.6; }
</style>
