<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SchoolLayout from '@/Layouts/SchoolLayout.vue';

defineProps({
    stores:    Array,
    suppliers: Array,
});

const showModal    = ref(false);
const editing      = ref(null);
const deleteTarget = ref(null);

const form       = useForm({ name: '', location: '', description: '' });
const deleteForm = useForm({});

function openAdd() {
    editing.value = null;
    form.reset();
    showModal.value = true;
}
function openEdit(s) {
    editing.value    = s;
    form.name        = s.name        || '';
    form.location    = s.location    || '';
    form.description = s.description || '';
    showModal.value  = true;
}
function submitForm() {
    const opts = { preserveScroll: true, onSuccess: () => { showModal.value = false; } };
    editing.value
        ? form.put(`/school/inventory-stores/${editing.value.id}`, opts)
        : form.post('/school/inventory-stores', opts);
}
function doDelete() {
    deleteForm.delete(`/school/inventory-stores/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
}
</script>

<template>
    <SchoolLayout title="Item Stores">

        <!-- Header -->
        <div class="page-header">
            <div>
                <div class="breadcrumb">
                    <a href="/school/inventory">Inventory</a>
                    <span class="sep">/</span>
                    <span>Stores</span>
                </div>
                <h1 class="page-header-title">Item Stores</h1>
                <p style="color:#64748b;font-size:.875rem;margin-top:2px;">Manage storerooms and track consumable stock levels.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="/school/inventory" class="btn-outline">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                    Assets
                </a>
                <a href="/school/inventory-suppliers" class="btn-outline">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Suppliers
                </a>
                <button class="btn-primary" @click="openAdd">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Store
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="flash-success">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.store" class="flash-error">{{ $page.props.errors.store }}</div>

        <!-- Empty state -->
        <div v-if="!stores.length" class="card" style="padding:48px 24px;text-align:center;">
            <svg width="48" height="48" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M3 7h18M3 12h18M3 17h18"/></svg>
            <p style="color:#94a3b8;font-size:.9rem;margin:0;">No stores yet. Create your first storeroom to start tracking stock.</p>
            <button class="btn-primary" style="margin-top:16px;" @click="openAdd">Create First Store</button>
        </div>

        <!-- Store cards grid -->
        <div v-else class="stores-grid">
            <div v-for="s in stores" :key="s.id" class="store-card">
                <div class="store-card-top">
                    <div class="store-icon-wrap">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18"/></svg>
                    </div>
                    <div class="store-info">
                        <h3 class="store-name">{{ s.name }}</h3>
                        <div v-if="s.location" class="store-location">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ s.location }}
                        </div>
                    </div>
                </div>
                <div v-if="s.description" class="store-desc">{{ s.description }}</div>
                <div class="store-footer">
                    <span class="count-pill count-blue">
                        {{ s.items_count }} item type{{ s.items_count !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:6px;margin-left:auto;">
                        <a :href="`/school/inventory-stores/${s.id}`" class="btn-primary" style="padding:6px 12px;font-size:.78rem;">
                            Manage Items
                        </a>
                        <button class="act-btn act-amber" @click="openEdit(s)">Edit</button>
                        <button class="act-btn act-red"
                            :disabled="s.items_count > 0"
                            :title="s.items_count > 0 ? 'Store has items — remove them first' : 'Delete'"
                            @click="deleteTarget = s">Del</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="backdrop" @click.self="showModal = false">
                <div class="modal-box" style="max-width:440px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#f0fdf4;color:#16a34a;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">{{ editing ? 'Edit Store' : 'Create Store' }}</h3>
                            <p class="modal-sub">{{ editing ? editing.name : 'Set up a new storeroom' }}</p>
                        </div>
                        <button class="modal-close" @click="showModal = false"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="submitForm">
                        <div class="modal-body">
                            <div class="field full">
                                <label class="field-label">Store Name <span class="req">*</span></label>
                                <input v-model="form.name" class="field-input" required placeholder="e.g. Science Lab Store, Stationery Room" />
                                <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>
                            </div>
                            <div class="field full">
                                <label class="field-label">Location / Room</label>
                                <input v-model="form.location" class="field-input" placeholder="e.g. Block A, Room 12" />
                            </div>
                            <div class="field full">
                                <label class="field-label">Description</label>
                                <textarea v-model="form.description" class="field-input" rows="2" placeholder="What kind of items are stored here?"></textarea>
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showModal = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : (editing ? 'Update Store' : 'Create Store') }}
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
                        <div><h3 class="modal-title">Delete Store</h3><p class="modal-sub">{{ deleteTarget.name }}</p></div>
                        <button class="modal-close" @click="deleteTarget = null"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="modal-body">
                        <div class="dispose-warning">Delete <strong>{{ deleteTarget.name }}</strong>? All items and transaction history will be permanently removed.</div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="deleteTarget = null">Cancel</button>
                        <button class="btn-danger" :disabled="deleteForm.processing" @click="doDelete">
                            {{ deleteForm.processing ? 'Deleting…' : 'Delete Store' }}
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

.card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05); }

.stores-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px; }

.store-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);transition:box-shadow .15s; }
.store-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.08); }

.store-card-top { display:flex;gap:12px;align-items:flex-start; }
.store-icon-wrap { width:42px;height:42px;border-radius:10px;background:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.store-name { font-size:.95rem;font-weight:700;color:#0f172a;margin:0 0 3px; }
.store-location { display:flex;align-items:center;gap:4px;font-size:.75rem;color:#64748b; }
.store-desc { font-size:.8rem;color:#64748b;line-height:1.5;padding:8px 10px;background:#f8fafc;border-radius:6px; }

.store-footer { display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding-top:4px;border-top:1px solid #f1f5f9;margin-top:4px; }

.count-pill   { display:inline-block;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px; }
.count-blue   { background:#dbeafe;color:#2563eb; }

.act-btn { font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;transition:opacity .15s;white-space:nowrap; }
.act-btn:hover { opacity:.8; }
.act-btn:disabled { opacity:.4;cursor:not-allowed; }
.act-amber { background:#fef3c7;color:#d97706; }
.act-red   { background:#fee2e2;color:#dc2626; }

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

.field.full  { grid-column:span 2; }
.field-label { display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:5px; }
.req         { color:#ef4444; }
.field-input { width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.875rem;color:#1e293b;background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box; }
.field-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px #3b82f620; }
textarea.field-input { resize:vertical; }
.field-error { font-size:.75rem;color:#ef4444;margin-top:4px; }
.dispose-warning { background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#92400e;line-height:1.6; }
</style>
