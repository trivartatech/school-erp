<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    alumni:  Object,  // paginated
    years:   Array,
    classes: Array,
});

// ── Filters ────────────────────────────────────────────────────────────────
const params = new URLSearchParams(window.location.search);
const search      = ref(params.get('search') ?? '');
const passoutYear = ref(params.get('passout_year') ?? '');
const finalClass  = ref(params.get('final_class') ?? '');

const applyFilters = () => {
    router.get('/school/alumni', {
        search:       search.value || undefined,
        passout_year: passoutYear.value || undefined,
        final_class:  finalClass.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

let filterTimer;
watch([search, passoutYear, finalClass], () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(applyFilters, 400);
});

// ── Graduate Modal ─────────────────────────────────────────────────────────
const showGraduate = ref(false);
const gradForm = useForm({
    student_id:       '',
    final_class:      '',
    passout_year:     '',
    final_percentage: '',
    final_grade:      '',
    graduated_on:     new Date().toISOString().slice(0, 10),
    notes:            '',
});

const studentResults = ref([]);
const studentSearch  = ref('');
let searchTimer;

const searchStudents = (q) => {
    clearTimeout(searchTimer);
    if (q.length < 2) { studentResults.value = []; return; }
    searchTimer = setTimeout(async () => {
        const res = await fetch(`/school/alumni/search-students?q=${encodeURIComponent(q)}`);
        studentResults.value = await res.json();
    }, 300);
};

const selectStudent = (s) => {
    gradForm.student_id = s.id;
    studentSearch.value = `${s.first_name} ${s.last_name} (${s.admission_no})`;
    studentResults.value = [];
};

const graduate = () => {
    gradForm.post('/school/alumni/graduate', {
        preserveScroll: true,
        onSuccess: () => { showGraduate.value = false; gradForm.reset(); studentSearch.value = ''; },
    });
};

// ── Edit Modal ─────────────────────────────────────────────────────────────
const showEdit    = ref(false);
const editTarget  = ref(null);
const editForm = useForm({
    current_occupation: '',
    current_employer:   '',
    current_city:       '',
    current_state:      '',
    personal_email:     '',
    personal_phone:     '',
    linkedin_url:       '',
    achievements:       '',
    notes:              '',
    final_percentage:   '',
    final_grade:        '',
});

const openEdit = (a) => {
    editTarget.value = a;
    editForm.current_occupation = a.current_occupation ?? '';
    editForm.current_employer   = a.current_employer   ?? '';
    editForm.current_city       = a.current_city       ?? '';
    editForm.current_state      = a.current_state      ?? '';
    editForm.personal_email     = a.personal_email     ?? '';
    editForm.personal_phone     = a.personal_phone     ?? '';
    editForm.linkedin_url       = a.linkedin_url       ?? '';
    editForm.achievements       = a.achievements       ?? '';
    editForm.notes              = a.notes              ?? '';
    editForm.final_percentage   = a.final_percentage   ?? '';
    editForm.final_grade        = a.final_grade        ?? '';
    showEdit.value = true;
};

const saveEdit = () => {
    editForm.put(`/school/alumni/${editTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { showEdit.value = false; },
    });
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—';
</script>

<template>
    <SchoolLayout title="Alumni">
        <div class="page-header">
            <div>
                <h1 class="page-header-title">Alumni Directory</h1>
                <p style="color:#64748b;font-size:.9rem;">Passout students and their current profiles.</p>
            </div>
            <Button @click="showGraduate = true">+ Graduate Student</Button>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom:16px;">
            <div style="padding:12px 16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <input v-model="search" placeholder="Search by name, admission no..." style="flex:1;min-width:200px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;" />
                <select v-model="passoutYear" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;">
                    <option value="">All Batches</option>
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
                <select v-model="finalClass" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;">
                    <option value="">All Classes</option>
                    <option v-for="c in classes" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
            <div class="card" style="padding:16px;text-align:center;">
                <div style="font-size:1.8rem;font-weight:700;color:#3b82f6;">{{ alumni.total }}</div>
                <div style="font-size:.8rem;color:#64748b;">Total Alumni</div>
            </div>
            <div class="card" style="padding:16px;text-align:center;">
                <div style="font-size:1.8rem;font-weight:700;color:#10b981;">{{ years.length }}</div>
                <div style="font-size:.8rem;color:#64748b;">Graduation Years</div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Admission No</th>
                            <th>Batch</th>
                            <th>Final Class</th>
                            <th>Grade / %</th>
                            <th>Current Occupation</th>
                            <th>City</th>
                            <th>Graduated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in alumni.data" :key="a.id">
                            <td>
                                <div style="font-weight:500;">{{ a.student?.first_name }} {{ a.student?.last_name }}</div>
                            </td>
                            <td style="font-size:.85rem;color:#64748b;">{{ a.student?.admission_no }}</td>
                            <td><span class="badge badge-blue">{{ a.passout_year ?? '—' }}</span></td>
                            <td>{{ a.final_class ?? '—' }}</td>
                            <td>
                                <span v-if="a.final_grade" class="badge badge-green">{{ a.final_grade }}</span>
                                <span v-if="a.final_percentage" style="font-size:.8rem;color:#64748b;margin-left:4px;">{{ a.final_percentage }}%</span>
                                <span v-if="!a.final_grade && !a.final_percentage">—</span>
                            </td>
                            <td style="font-size:.85rem;">{{ a.current_occupation ?? '—' }}</td>
                            <td style="font-size:.85rem;">{{ a.current_city ?? '—' }}<span v-if="a.current_state">, {{ a.current_state }}</span></td>
                            <td style="font-size:.85rem;">{{ fmt(a.graduated_on) }}</td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <Button size="xs" variant="secondary" @click="openEdit(a)">Edit</Button>
                                    <Button size="xs" variant="danger" @click="$inertia.delete(`/school/alumni/${a.id}`, { preserveScroll: true })">Remove</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!alumni.data?.length">
                            <td colspan="9" style="text-align:center;padding:32px;color:#94a3b8;">No alumni records yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="alumni.last_page > 1" style="display:flex;justify-content:center;gap:4px;padding:16px;">
                <a v-for="p in alumni.links" :key="p.label" :href="p.url ?? '#'"
                   v-html="p.label"
                   :style="{ padding:'6px 12px', borderRadius:'6px', fontSize:'.85rem', background: p.active ? '#3b82f6' : '#f1f5f9', color: p.active ? '#fff' : '#475569', textDecoration:'none', pointerEvents: p.url ? 'auto' : 'none', opacity: p.url ? 1 : 0.4 }">
                </a>
            </div>
        </div>

        <!-- Graduate Modal -->
        <Teleport to="body">
            <div v-if="showGraduate" class="modal-backdrop" @click.self="showGraduate = false">
                <div class="modal" style="max-width:500px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">Graduate a Student</h3>
                        <button @click="showGraduate = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="graduate">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                            <div class="form-field">
                                <label>Student *</label>
                                <input v-model="studentSearch" @input="searchStudents(studentSearch)"
                                       placeholder="Type name or admission no..." autocomplete="off" />
                                <div v-if="studentResults.length" style="border:1px solid #e2e8f0;border-radius:6px;max-height:180px;overflow-y:auto;background:#fff;margin-top:4px;">
                                    <div v-for="s in studentResults" :key="s.id"
                                         @click="selectStudent(s)"
                                         style="padding:8px 12px;cursor:pointer;font-size:.9rem;border-bottom:1px solid #f1f5f9;"
                                         @mouseenter="$event.target.style.background='#f8fafc'"
                                         @mouseleave="$event.target.style.background='#fff'">
                                        {{ s.first_name }} {{ s.last_name }} — {{ s.admission_no }}
                                        <span v-if="s.current_academic_history?.course_class" style="color:#94a3b8;font-size:.8rem;"> · {{ s.current_academic_history.course_class.name }}</span>
                                    </div>
                                </div>
                                <div v-if="gradForm.errors.student_id" style="color:#ef4444;font-size:.8rem;margin-top:4px;">{{ gradForm.errors.student_id }}</div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div class="form-field" style="margin:0;">
                                    <label>Final Class</label>
                                    <input v-model="gradForm.final_class" placeholder="e.g. Class XII" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Passout Year</label>
                                    <input v-model="gradForm.passout_year" placeholder="e.g. 2025-26" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Final Percentage</label>
                                    <input v-model="gradForm.final_percentage" type="number" min="0" max="100" step="0.01" placeholder="e.g. 87.50" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Grade</label>
                                    <input v-model="gradForm.final_grade" placeholder="e.g. A+" maxlength="5" />
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Graduated On</label>
                                <input v-model="gradForm.graduated_on" type="date" />
                            </div>

                            <div class="form-field">
                                <label>Notes</label>
                                <textarea v-model="gradForm.notes" rows="2" placeholder="Any remarks..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showGraduate = false">Cancel</Button>
                            <Button type="submit" :loading="gradForm.processing" :disabled="!gradForm.student_id">Graduate</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit Alumni Modal -->
        <Teleport to="body">
            <div v-if="showEdit" class="modal-backdrop" @click.self="showEdit = false">
                <div class="modal" style="max-width:540px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">Update Alumni Profile</h3>
                        <button @click="showEdit = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="saveEdit">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div class="form-field" style="margin:0;">
                                    <label>Final %</label>
                                    <input v-model="editForm.final_percentage" type="number" min="0" max="100" step="0.01" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Grade</label>
                                    <input v-model="editForm.final_grade" maxlength="5" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Current Occupation</label>
                                    <input v-model="editForm.current_occupation" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Employer</label>
                                    <input v-model="editForm.current_employer" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>City</label>
                                    <input v-model="editForm.current_city" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>State</label>
                                    <input v-model="editForm.current_state" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Personal Email</label>
                                    <input v-model="editForm.personal_email" type="email" />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Personal Phone</label>
                                    <input v-model="editForm.personal_phone" />
                                </div>
                            </div>
                            <div class="form-field">
                                <label>LinkedIn URL</label>
                                <input v-model="editForm.linkedin_url" type="url" placeholder="https://linkedin.com/in/..." />
                            </div>
                            <div class="form-field">
                                <label>Achievements</label>
                                <textarea v-model="editForm.achievements" rows="2"></textarea>
                            </div>
                            <div class="form-field">
                                <label>Notes</label>
                                <textarea v-model="editForm.notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showEdit = false">Cancel</Button>
                            <Button type="submit" :loading="editForm.processing">Save</Button>
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
