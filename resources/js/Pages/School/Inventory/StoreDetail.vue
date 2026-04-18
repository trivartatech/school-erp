<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SchoolLayout from '@/Layouts/SchoolLayout.vue';

const props = defineProps({
    store:     Object,
    suppliers: Array,
});

const fmt    = (n) => Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtQty = (n) => Number(n).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
const isLow  = (item) => Number(item.min_quantity) > 0 && Number(item.quantity) <= Number(item.min_quantity);

const lowStockItems = computed(() => (props.store.items || []).filter(isLow));
const totalValue    = computed(() =>
    (props.store.items || []).reduce((s, i) => s + Number(i.quantity) * Number(i.unit_price), 0)
);

// ── Item CRUD ─────────────────────────────────────────────────────────────────
const showItemModal = ref(false);
const editingItem   = ref(null);
const itemForm = useForm({ name: '', unit: 'pcs', supplier_id: '', quantity: '', min_quantity: '', unit_price: '', notes: '' });

function openAddItem() {
    editingItem.value = null;
    itemForm.reset();
    itemForm.unit = 'pcs';
    showItemModal.value = true;
}
function openEditItem(item) {
    editingItem.value    = item;
    itemForm.name        = item.name        || '';
    itemForm.unit        = item.unit        || 'pcs';
    itemForm.supplier_id = item.supplier_id || '';
    itemForm.min_quantity= item.min_quantity|| '';
    itemForm.unit_price  = item.unit_price  || '';
    itemForm.notes       = item.notes       || '';
    showItemModal.value  = true;
}
function submitItemForm() {
    const opts = { preserveScroll: true, onSuccess: () => { showItemModal.value = false; } };
    editingItem.value
        ? itemForm.put(`/school/inventory-stores/items/${editingItem.value.id}`, opts)
        : itemForm.post(`/school/inventory-stores/${props.store.id}/items`, opts);
}

// ── Delete item ───────────────────────────────────────────────────────────────
const deleteItemTarget = ref(null);
const deleteItemForm   = useForm({});
function doDeleteItem() {
    deleteItemForm.delete(`/school/inventory-stores/items/${deleteItemTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleteItemTarget.value = null; },
    });
}

// ── Transactions ──────────────────────────────────────────────────────────────
const txnTarget = ref(null);
const txnForm   = useForm({ type: 'in', quantity: '', transaction_date: '', reference: '', notes: '' });

function openTxn(item, type) {
    txnTarget.value          = item;
    txnForm.type             = type;
    txnForm.quantity         = '';
    txnForm.transaction_date = new Date().toISOString().slice(0, 10);
    txnForm.reference        = '';
    txnForm.notes            = '';
}
function submitTxn() {
    txnForm.post(`/school/inventory-stores/items/${txnTarget.value.id}/transaction`, {
        preserveScroll: true,
        onSuccess: () => { txnTarget.value = null; },
    });
}
</script>

<template>
    <SchoolLayout :title="store.name">

        <!-- Header -->
        <div class="page-header">
            <div>
                <div class="breadcrumb">
                    <a href="/school/inventory">Inventory</a>
                    <span class="sep">/</span>
                    <a href="/school/inventory-stores">Stores</a>
                    <span class="sep">/</span>
                    <span>{{ store.name }}</span>
                </div>
                <h1 class="page-header-title">{{ store.name }}</h1>
                <p v-if="store.location" style="color:#64748b;font-size:.875rem;margin-top:2px;">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ store.location }}
                </p>
            </div>
            <button class="btn-primary" @click="openAddItem">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Item
            </button>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="flash-success">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.quantity" class="flash-error">{{ $page.props.errors.quantity }}</div>

        <!-- Low stock banner -->
        <div v-if="lowStockItems.length" class="low-stock-banner">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>
                <strong>{{ lowStockItems.length }} item{{ lowStockItems.length > 1 ? 's' : '' }}</strong> below minimum stock:
                {{ lowStockItems.map(i => i.name).join(', ') }}
            </span>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card stat-blue">
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg></div>
                <div>
                    <div class="stat-label">Item Types</div>
                    <div class="stat-value" style="color:#3b82f6;">{{ store.items?.length ?? 0 }}</div>
                    <div class="stat-sub">in this store</div>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div>
                    <div class="stat-label">Total Stock Value</div>
                    <div class="stat-value" style="color:#10b981;">₹{{ fmt(totalValue) }}</div>
                    <div class="stat-sub">at current prices</div>
                </div>
            </div>
            <div class="stat-card stat-amber">
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
                <div>
                    <div class="stat-label">Low Stock Alerts</div>
                    <div class="stat-value" style="color:#f59e0b;">{{ lowStockItems.length }}</div>
                    <div class="stat-sub">items need restocking</div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card" style="overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Supplier</th>
                            <th style="text-align:right;">Qty in Stock</th>
                            <th>Unit</th>
                            <th style="text-align:right;">Min Stock</th>
                            <th style="text-align:right;">Unit Price</th>
                            <th style="text-align:right;">Stock Value</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in store.items" :key="item.id" :class="{ 'row-low': isLow(item) }">
                            <td>
                                <div style="font-weight:600;font-size:.875rem;color:#1e293b;">{{ item.name }}</div>
                                <span v-if="isLow(item)" style="font-size:.68rem;font-weight:700;background:#fee2e2;color:#dc2626;padding:1px 7px;border-radius:10px;">Low Stock</span>
                            </td>
                            <td style="font-size:.82rem;color:#475569;">{{ item.supplier?.name || '—' }}</td>
                            <td style="text-align:right;">
                                <span :style="{ fontWeight: 700, fontSize: '.9rem', color: isLow(item) ? '#dc2626' : '#1e293b' }">{{ fmtQty(item.quantity) }}</span>
                            </td>
                            <td style="font-size:.82rem;color:#64748b;">{{ item.unit }}</td>
                            <td style="text-align:right;font-size:.8rem;color:#94a3b8;">
                                {{ item.min_quantity > 0 ? fmtQty(item.min_quantity) : '—' }}
                            </td>
                            <td style="text-align:right;font-size:.82rem;color:#374151;">
                                {{ item.unit_price > 0 ? '₹' + fmt(item.unit_price) : '—' }}
                            </td>
                            <td style="text-align:right;font-size:.82rem;font-weight:600;color:#1e293b;">
                                {{ item.unit_price > 0 ? '₹' + fmt(Number(item.quantity) * Number(item.unit_price)) : '—' }}
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;justify-content:flex-end;flex-wrap:wrap;">
                                    <button class="act-btn act-green" @click="openTxn(item, 'in')" title="Stock In">+ In</button>
                                    <button class="act-btn act-orange" @click="openTxn(item, 'out')" title="Stock Out">− Out</button>
                                    <button class="act-btn act-blue" @click="openEditItem(item)">Edit</button>
                                    <button class="act-btn act-red" @click="deleteItemTarget = item">Del</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!store.items?.length">
                            <td colspan="8" class="empty-row">
                                <svg width="40" height="40" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" style="margin-bottom:8px;display:block;margin-inline:auto;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                                No items yet. Add your first item to start tracking stock.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="store.items?.length">
                        <tr>
                            <td colspan="6" style="text-align:right;font-size:.8rem;font-weight:700;color:#64748b;padding:12px 16px;">Total Stock Value</td>
                            <td style="text-align:right;font-size:.9rem;font-weight:800;color:#1e293b;padding:12px 16px;">₹{{ fmt(totalValue) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Add / Edit Item Modal -->
        <Teleport to="body">
            <div v-if="showItemModal" class="backdrop" @click.self="showItemModal = false">
                <div class="modal-box" style="max-width:500px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#dbeafe;color:#2563eb;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">{{ editingItem ? 'Edit Item' : 'Add Item' }}</h3>
                            <p class="modal-sub">{{ editingItem ? editingItem.name : store.name }}</p>
                        </div>
                        <button class="modal-close" @click="showItemModal = false"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="submitItemForm">
                        <div class="modal-body">
                            <div class="field full">
                                <label class="field-label">Item Name <span class="req">*</span></label>
                                <input v-model="itemForm.name" class="field-input" required placeholder="e.g. A4 Paper, Marker, Chalk Box" />
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Unit <span class="req">*</span></label>
                                    <input v-model="itemForm.unit" class="field-input" placeholder="pcs / kg / L / box" required />
                                </div>
                                <div class="field">
                                    <label class="field-label">Supplier</label>
                                    <select v-model="itemForm.supplier_id" class="field-input">
                                        <option value="">— None —</option>
                                        <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="field-row">
                                <div v-if="!editingItem" class="field">
                                    <label class="field-label">Opening Qty</label>
                                    <input v-model="itemForm.quantity" type="number" min="0" step="0.01" class="field-input" placeholder="0" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Min Stock Alert</label>
                                    <input v-model="itemForm.min_quantity" type="number" min="0" step="0.01" class="field-input" placeholder="0" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Unit Price (₹)</label>
                                    <input v-model="itemForm.unit_price" type="number" min="0" step="0.01" class="field-input" placeholder="0.00" />
                                </div>
                            </div>
                            <div class="field full">
                                <label class="field-label">Notes</label>
                                <textarea v-model="itemForm.notes" class="field-input" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="showItemModal = false">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="itemForm.processing">
                                {{ itemForm.processing ? 'Saving…' : (editingItem ? 'Update Item' : 'Add Item') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Stock In / Out Modal -->
        <Teleport to="body">
            <div v-if="txnTarget" class="backdrop" @click.self="txnTarget = null">
                <div class="modal-box" style="max-width:420px;">
                    <div class="modal-head">
                        <span class="modal-icon" :style="{ background: txnForm.type === 'in' ? '#dcfce7' : '#fff7ed', color: txnForm.type === 'in' ? '#16a34a' : '#d97706' }">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                        </span>
                        <div>
                            <h3 class="modal-title">Stock {{ txnForm.type === 'in' ? 'In' : 'Out' }}</h3>
                            <p class="modal-sub">{{ txnTarget.name }}</p>
                        </div>
                        <button class="modal-close" @click="txnTarget = null"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="submitTxn">
                        <div class="modal-body">
                            <div class="txn-current">
                                Current stock: <strong>{{ fmtQty(txnTarget.quantity) }} {{ txnTarget.unit }}</strong>
                                <span v-if="isLow(txnTarget)" style="margin-left:8px;font-size:.72rem;font-weight:700;background:#fee2e2;color:#dc2626;padding:1px 6px;border-radius:10px;">Low</span>
                            </div>
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Quantity <span class="req">*</span></label>
                                    <input v-model="txnForm.quantity" type="number" min="0.01" step="0.01" class="field-input" required placeholder="0.00" />
                                </div>
                                <div class="field">
                                    <label class="field-label">Date <span class="req">*</span></label>
                                    <input v-model="txnForm.transaction_date" type="date" class="field-input" required />
                                </div>
                            </div>
                            <div class="field full">
                                <label class="field-label">Reference</label>
                                <input v-model="txnForm.reference" class="field-input" placeholder="PO no., invoice, dept request…" />
                            </div>
                            <div class="field full">
                                <label class="field-label">Notes</label>
                                <textarea v-model="txnForm.notes" class="field-input" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn-outline" @click="txnTarget = null">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="txnForm.processing">
                                {{ txnForm.processing ? 'Saving…' : (txnForm.type === 'in' ? 'Confirm Stock In' : 'Confirm Stock Out') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete item confirm -->
        <Teleport to="body">
            <div v-if="deleteItemTarget" class="backdrop" @click.self="deleteItemTarget = null">
                <div class="modal-box" style="max-width:420px;">
                    <div class="modal-head">
                        <span class="modal-icon" style="background:#fee2e2;color:#dc2626;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>
                        <div><h3 class="modal-title">Delete Item</h3><p class="modal-sub">{{ deleteItemTarget.name }}</p></div>
                        <button class="modal-close" @click="deleteItemTarget = null"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="modal-body">
                        <div class="dispose-warning">Delete <strong>{{ deleteItemTarget.name }}</strong>? All transaction history for this item will also be permanently removed.</div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="deleteItemTarget = null">Cancel</button>
                        <button class="btn-danger" :disabled="deleteItemForm.processing" @click="doDeleteItem">
                            {{ deleteItemForm.processing ? 'Deleting…' : 'Delete Item' }}
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

.low-stock-banner { display:flex;align-items:center;gap:10px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:.85rem;color:#92400e; }

.stats-row { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px; }
@media (max-width:900px) { .stats-row { grid-template-columns:1fr 1fr; } }
.stat-card { display:flex;align-items:flex-start;gap:14px;background:#fff;border-radius:12px;padding:18px 20px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat-icon  { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.stat-green .stat-icon { background:#dcfce7;color:#16a34a; }
.stat-blue  .stat-icon { background:#dbeafe;color:#2563eb; }
.stat-amber .stat-icon { background:#fef3c7;color:#d97706; }
.stat-label { font-size:.7rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em; }
.stat-value { font-size:1.5rem;font-weight:800;line-height:1.1;margin-top:2px; }
.stat-sub   { font-size:.72rem;color:#94a3b8;margin-top:2px; }

.card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05); }
.inv-table { width:100%;border-collapse:collapse; }
.inv-table th { padding:11px 16px;text-align:left;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;background:#f8fafc;border-bottom:1px solid #e2e8f0;white-space:nowrap; }
.inv-table td { padding:12px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.inv-table tr:last-child td { border-bottom:none; }
.inv-table tr:hover td { background:#fafbff; }
.inv-table tfoot td { background:#f8fafc;border-top:2px solid #e2e8f0;border-bottom:none; }
.row-low td { background:rgba(239,68,68,.04); }

.act-btn { font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;transition:opacity .15s;white-space:nowrap; }
.act-btn:hover { opacity:.8; }
.act-btn:disabled { opacity:.4;cursor:not-allowed; }
.act-green  { background:#dcfce7;color:#16a34a; }
.act-orange { background:#fff7ed;color:#c2410c; }
.act-blue   { background:#dbeafe;color:#2563eb; }
.act-red    { background:#fee2e2;color:#dc2626; }

.empty-row { text-align:center;padding:48px 24px;color:#94a3b8;font-size:.9rem; }

.txn-current { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#374151; }

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
