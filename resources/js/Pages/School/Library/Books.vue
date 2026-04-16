<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    books: Object,
    categories: Array,
    filters: Object,
});

// ── Filters ───────────────────────────────────────────────────────
const search   = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? '');

const applyFilters = () => {
    router.get('/school/library/books', { search: search.value, category: category.value }, { preserveScroll: true, replace: true });
};

// ── Add Book Modal ────────────────────────────────────────────────
const showAdd  = ref(false);
const editBook = ref(null);

const form = useForm({
    title: '', author: '', isbn: '', publisher: '', publish_year: '',
    category: '', subject: '', language: 'English', location: '',
    total_copies: 1, price: '', description: '', barcode: '',
});

const openAdd = () => { form.reset(); editBook.value = null; showAdd.value = true; };
const openEdit = (b) => {
    editBook.value = b;
    Object.keys(form).forEach(k => { if (k in b) form[k] = b[k] ?? ''; });
    showAdd.value = true;
};
const closeModal = () => { showAdd.value = false; editBook.value = null; };

const submitBook = () => {
    if (editBook.value) {
        form.put(`/school/library/books/${editBook.value.id}`, { preserveScroll: true, onSuccess: closeModal });
    } else {
        form.post('/school/library/books', { preserveScroll: true, onSuccess: closeModal });
    }
};

const deleteBook = (id) => {
    if (confirm('Remove this book from catalog?')) {
        router.delete(`/school/library/books/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <SchoolLayout title="Library — Books">
        <div class="page-header">
            <h1 class="page-header-title">Book Catalog</h1>
            <Button @click="openAdd">+ Add Book</Button>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
                <input v-model="search" @input="applyFilters" type="text" placeholder="Search title, author, ISBN..." style="flex:1;min-width:200px;" />
                <select v-model="category" @change="applyFilters" style="width:180px;">
                    <option value="">All Categories</option>
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
        </div>

        <!-- Books Table -->
        <div class="card">
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title / Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th style="text-align:center;">Total</th>
                            <th style="text-align:center;">Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in books.data" :key="b.id">
                            <td>
                                <div style="font-weight:500;">{{ b.title }}</div>
                                <div style="font-size:.75rem;color:#94a3b8;">{{ b.author }}</div>
                            </td>
                            <td style="font-family:monospace;font-size:.8rem;">{{ b.isbn || '—' }}</td>
                            <td>{{ b.category || '—' }}</td>
                            <td>{{ b.location || '—' }}</td>
                            <td style="text-align:center;">{{ b.total_copies }}</td>
                            <td style="text-align:center;">
                                <span :style="{ color: b.available_copies > 0 ? '#16a34a' : '#dc2626', fontWeight: 600 }">
                                    {{ b.available_copies }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <Button variant="secondary" size="xs" @click="openEdit(b)">Edit</Button>
                                    <Button variant="danger" size="xs" @click="deleteBook(b.id)">Delete</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!books.data?.length">
                            <td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">No books found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="books.last_page > 1" class="card-footer" style="display:flex;justify-content:center;gap:8px;padding:12px;">
                <a v-for="link in books.links" :key="link.label"
                   :href="link.url" v-html="link.label"
                   :class="['page-link', { 'active': link.active, 'disabled': !link.url }]" />
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Teleport to="body">
            <div v-if="showAdd" class="modal-backdrop" @click.self="closeModal">
                <div class="modal" style="max-width:560px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ editBook ? 'Edit Book' : 'Add Book to Catalog' }}</h3>
                        <button @click="closeModal" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitBook">
                        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Title *</label>
                                <input v-model="form.title" required />
                                <span v-if="form.errors.title" class="error-text">{{ form.errors.title }}</span>
                            </div>
                            <div class="form-field">
                                <label>Author</label>
                                <input v-model="form.author" />
                            </div>
                            <div class="form-field">
                                <label>ISBN</label>
                                <input v-model="form.isbn" />
                            </div>
                            <div class="form-field">
                                <label>Publisher</label>
                                <input v-model="form.publisher" />
                            </div>
                            <div class="form-field">
                                <label>Publish Year</label>
                                <input v-model="form.publish_year" type="number" min="1800" />
                            </div>
                            <div class="form-field">
                                <label>Category</label>
                                <input v-model="form.category" />
                            </div>
                            <div class="form-field">
                                <label>Subject</label>
                                <input v-model="form.subject" />
                            </div>
                            <div class="form-field">
                                <label>Location / Shelf</label>
                                <input v-model="form.location" />
                            </div>
                            <div class="form-field">
                                <label>Total Copies *</label>
                                <input v-model="form.total_copies" type="number" min="1" required />
                            </div>
                            <div class="form-field">
                                <label>Price (₹)</label>
                                <input v-model="form.price" type="number" step="0.01" min="0" />
                            </div>
                            <div class="form-field">
                                <label>Barcode</label>
                                <input v-model="form.barcode" />
                            </div>
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Description</label>
                                <textarea v-model="form.description" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="closeModal">Cancel</Button>
                            <Button type="submit" :loading="form.processing">{{ editBook ? 'Update' : 'Add Book' }}</Button>
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
.page-link { padding:4px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:.8rem;text-decoration:none;color:#374151; }
.page-link.active { background:#3b82f6;color:#fff;border-color:#3b82f6; }
.page-link.disabled { opacity:.4;pointer-events:none; }
</style>
