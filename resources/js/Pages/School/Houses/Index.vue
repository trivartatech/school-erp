<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { canDo } = usePermissions();

const props = defineProps({
    houses:   { type: Array, default: () => [] },
    staff:    { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
});

const showModal = ref(false);
const editing   = ref(null);
const loading   = ref(false);
const errors    = ref({});

const defaultForm = () => ({ name: '', color: '#6366f1', incharge_staff_id: '', captain_student_id: '' });
const form = reactive(defaultForm());

function openCreate() {
    editing.value = null;
    errors.value  = {};
    Object.assign(form, defaultForm());
    showModal.value = true;
}

function openEdit(house) {
    editing.value = house;
    errors.value  = {};
    Object.assign(form, {
        name:               house.name,
        color:              house.color,
        incharge_staff_id:  house.incharge_staff_id  || '',
        captain_student_id: house.captain_student_id || '',
    });
    showModal.value = true;
}

function save() {
    loading.value = true;
    const url  = editing.value ? `/school/houses/${editing.value.id}` : '/school/houses';
    const opts = {
        onSuccess: () => { showModal.value = false; },
        onError:   (e) => { errors.value = e; },
        onFinish:  () => { loading.value = false; },
    };
    if (editing.value) {
        router.put(url, { ...form }, opts);
    } else {
        router.post(url, { ...form }, opts);
    }
}

function destroy(house) {
    if (!confirm(`Delete "${house.name}"? This cannot be undone.`)) return;
    router.delete(`/school/houses/${house.id}`);
}

const sortedHouses = computed(() =>
    [...props.houses].sort((a, b) => b.total_points - a.total_points)
);

const studentName = (s) => s ? `${s.first_name} ${s.last_name}` : '';
</script>

<template>
<SchoolLayout title="Student Houses">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">Student Houses</h1>
            <p class="page-header-sub">Manage school houses, assign students, and track points.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <Link href="/school/houses/leaderboard" class="btn btn-secondary">Leaderboard</Link>
            <Button v-if="canDo('create','houses')" @click="openCreate">+ New House</Button>
        </div>
    </div>

    <!-- Empty state -->
    <div v-if="!houses.length" class="card" style="text-align:center;padding:3rem 2rem;">
        <div style="font-size:2.5rem;margin-bottom:8px;">🏠</div>
        <p style="color:var(--text-muted);font-size:.9rem;">No houses yet. Create one to get started.</p>
        <Button v-if="canDo('create','houses')" @click="openCreate" style="margin-top:16px;">+ New House</Button>
    </div>

    <!-- House Cards Grid -->
    <div v-else class="house-grid">
        <div v-for="house in sortedHouses" :key="house.id" class="house-card">
            <div class="house-card-top" :style="{ background: house.color }">
                <span class="house-name">{{ house.name }}</span>
                <span class="house-points-badge">{{ house.total_points }} pts</span>
            </div>
            <div class="house-card-body">
                <div class="house-meta">
                    <span class="meta-label">Incharge</span>
                    <span class="meta-value">{{ house.incharge?.name || '—' }}</span>
                </div>
                <div class="house-meta">
                    <span class="meta-label">Captain</span>
                    <span class="meta-value">{{ house.captain ? studentName(house.captain) : '—' }}</span>
                </div>
                <div class="house-meta">
                    <span class="meta-label">Students</span>
                    <span class="meta-value">{{ house.student_count }}</span>
                </div>
            </div>
            <div class="house-card-footer">
                <Link :href="`/school/houses/${house.id}`" class="btn btn-secondary btn-xs">View</Link>
                <Button v-if="canDo('edit','houses')" variant="secondary" size="xs" @click="openEdit(house)">Edit</Button>
                <Button v-if="canDo('delete','houses')" variant="danger" size="xs" @click="destroy(house)">Delete</Button>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="showModal = false">
        <div class="modal">
            <div class="card-header">
                <h3 class="card-title">{{ editing ? 'Edit House' : 'Create House' }}</h3>
            </div>
            <div class="card-body">
                <form @submit.prevent="save">
                    <div v-if="Object.keys(errors).length" class="form-errors">
                        <div v-for="(msg, field) in errors" :key="field">{{ msg }}</div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-field">
                            <label>Name <span class="required">*</span></label>
                            <input v-model="form.name" required maxlength="100" placeholder="e.g. Red House">
                        </div>
                        <div class="form-field">
                            <label>Color <span class="required">*</span></label>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="color" v-model="form.color" style="width:42px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--border);cursor:pointer;">
                                <input v-model="form.color" style="flex:1;" placeholder="#6366f1" maxlength="7">
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2" style="margin-top:14px;">
                        <div class="form-field">
                            <label>Incharge (Staff)</label>
                            <select v-model="form.incharge_staff_id">
                                <option value="">— None —</option>
                                <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>House Captain (Student)</label>
                            <select v-model="form.captain_student_id">
                                <option value="">— None —</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">
                                    {{ s.first_name }} {{ s.last_name }} ({{ s.admission_no }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;padding-top:12px;border-top:1px solid var(--border);">
                        <Button variant="secondary" type="button" @click="showModal = false">Cancel</Button>
                        <Button type="submit" :loading="loading">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </Teleport>
</SchoolLayout>
</template>

<style scoped>
.house-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

.house-card {
    border-radius: 12px;
    border: 1.5px solid var(--border);
    overflow: hidden;
    background: #fff;
    transition: box-shadow .15s;
}
.house-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }

.house-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px 14px;
}
.house-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    text-shadow: 0 1px 3px rgba(0,0,0,.25);
}
.house-points-badge {
    background: rgba(255,255,255,.25);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}

.house-card-body { padding: 14px 16px 10px; }

.house-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: .8rem;
}
.house-meta:last-child { border-bottom: none; }
.meta-label { color: var(--text-muted); font-weight: 600; }
.meta-value  { color: var(--text-primary); font-weight: 500; }

.house-card-footer {
    display: flex;
    gap: 6px;
    padding: 10px 14px 14px;
    border-top: 1px solid var(--border);
}

.modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,.5);
    display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
    background: #fff; border-radius: .75rem; width: 100%; max-width: 520px;
    max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.form-errors {
    background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
    padding: 10px 14px; font-size: .82rem; color: #dc2626; margin-bottom: 14px;
}
.required { color: #ef4444; }

.btn { display:inline-flex; align-items:center; justify-content:center; padding:6px 14px; border-radius:6px; font-size:.82rem; font-weight:600; text-decoration:none; transition:all .15s; cursor:pointer; }
.btn-secondary { background: var(--bg); border: 1px solid var(--border); color: var(--text-primary); }
.btn-secondary:hover { background: #f1f5f9; }
.btn-xs { padding: 4px 10px; font-size: .75rem; }
</style>
