<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    records: Object,
    students: Array,
    summary: Object,
    filters: Object,
});

const CATEGORIES = ['Misconduct', 'Bullying', 'Damage to Property', 'Dress Code Violation', 'Absenteeism', 'Cheating', 'Disrespect', 'Violence', 'Other'];
const CONSEQUENCES = ['none', 'warning', 'detention', 'parent_call', 'suspension', 'expulsion'];

// ── Filters ───────────────────────────────────────────────────────
const filterStatus   = ref(props.filters?.status ?? '');
const filterSeverity = ref(props.filters?.severity ?? '');
const filterStudent  = ref(props.filters?.student_id ?? '');

const applyFilters = () => {
    router.get('/school/disciplinary', { status: filterStatus.value, severity: filterSeverity.value, student_id: filterStudent.value }, { preserveScroll: true, replace: true });
};

// ── Add Record ────────────────────────────────────────────────────
const showAdd   = ref(false);
const editRecord = ref(null);

const form = useForm({
    student_id: '', incident_date: new Date().toISOString().split('T')[0],
    category: '', severity: 'minor', description: '', action_taken: '',
    consequence: '', consequence_from: '', consequence_to: '',
    student_statement: '', notes: '',
});

const openAdd = () => { form.reset(); form.incident_date = new Date().toISOString().split('T')[0]; editRecord.value = null; showAdd.value = true; };
const openEdit = (r) => {
    editRecord.value = r;
    Object.assign(form, {
        student_id: r.student_id, incident_date: r.incident_date?.slice(0,10),
        category: r.category, severity: r.severity, description: r.description,
        action_taken: r.action_taken ?? '', status: r.status,
        consequence: r.consequence ?? '', consequence_from: r.consequence_from?.slice(0,10) ?? '',
        consequence_to: r.consequence_to?.slice(0,10) ?? '', parent_notified: r.parent_notified,
        student_statement: r.student_statement ?? '', notes: r.notes ?? '',
    });
    showAdd.value = true;
};
const closeModal = () => { showAdd.value = false; editRecord.value = null; };

const submitForm = () => {
    if (editRecord.value) {
        form.put(`/school/disciplinary/${editRecord.value.id}`, { preserveScroll: true, onSuccess: closeModal });
    } else {
        form.post('/school/disciplinary', { preserveScroll: true, onSuccess: closeModal });
    }
};

const deleteRecord = (id) => {
    if (confirm('Delete this record?')) router.delete(`/school/disciplinary/${id}`, { preserveScroll: true });
};

const severityColor = { minor: '#d97706', moderate: '#f97316', major: '#dc2626' };
const statusBadge   = { open: 'badge-amber', under_review: 'badge-blue', resolved: 'badge-green', escalated: 'badge-red' };

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <SchoolLayout title="Disciplinary Records">
        <div class="page-header">
            <h1 class="page-header-title">Disciplinary Records</h1>
            <Button @click="openAdd">+ Add Record</Button>
        </div>

        <!-- Summary -->
        <div class="disc-stats">
            <div class="card stat-card"><div class="card-body text-center">
                <div class="stat-value" style="color:#1d4ed8;">{{ summary.total }}</div>
                <div class="stat-label">Total Records</div>
            </div></div>
            <div class="card stat-card"><div class="card-body text-center">
                <div class="stat-value" style="color:#d97706;">{{ summary.open }}</div>
                <div class="stat-label">Open Cases</div>
            </div></div>
            <div class="card stat-card"><div class="card-body text-center">
                <div class="stat-value" style="color:#7c3aed;">{{ summary.this_month }}</div>
                <div class="stat-label">This Month</div>
            </div></div>
            <div class="card stat-card"><div class="card-body text-center">
                <div class="stat-value" style="color:#dc2626;">{{ summary.major }}</div>
                <div class="stat-label">Major Incidents</div>
            </div></div>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
                <select v-model="filterStatus" @change="applyFilters" style="width:150px;">
                    <option value="">All Statuses</option>
                    <option value="open">Open</option>
                    <option value="under_review">Under Review</option>
                    <option value="resolved">Resolved</option>
                    <option value="escalated">Escalated</option>
                </select>
                <select v-model="filterSeverity" @change="applyFilters" style="width:150px;">
                    <option value="">All Severities</option>
                    <option value="minor">Minor</option>
                    <option value="moderate">Moderate</option>
                    <option value="major">Major</option>
                </select>
                <select v-model="filterStudent" @change="applyFilters" style="width:200px;">
                    <option value="">All Students</option>
                    <option v-for="s in students" :key="s.id" :value="s.id">{{ s.first_name }} {{ s.last_name }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Consequence</th>
                            <th>Reported By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in records.data" :key="r.id">
                            <td>
                                <div style="font-weight:500;">{{ r.student?.first_name }} {{ r.student?.last_name }}</div>
                                <div style="font-size:.7rem;color:#94a3b8;">{{ r.student?.admission_no }}</div>
                            </td>
                            <td style="white-space:nowrap;">{{ fmtDate(r.incident_date) }}</td>
                            <td>{{ r.category }}</td>
                            <td>
                                <span style="font-weight:600;text-transform:capitalize;" :style="{ color: severityColor[r.severity] }">{{ r.severity }}</span>
                            </td>
                            <td><span class="badge" :class="statusBadge[r.status]" style="text-transform:capitalize;">{{ r.status?.replace('_', ' ') }}</span></td>
                            <td style="text-transform:capitalize;font-size:.8rem;">{{ r.consequence ? r.consequence.replace('_', ' ') : '—' }}</td>
                            <td style="font-size:.8rem;color:#64748b;">{{ r.reported_by?.name || '—' }}</td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <Button variant="secondary" size="xs" @click="openEdit(r)">Edit</Button>
                                    <Button variant="danger" size="xs" @click="deleteRecord(r.id)">Del</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!records.data?.length">
                            <td colspan="8" style="text-align:center;padding:32px;color:#94a3b8;">No records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Teleport to="body">
            <div v-if="showAdd" class="modal-backdrop" @click.self="closeModal">
                <div class="modal" style="max-width:580px;width:100%;max-height:90vh;overflow-y:auto;">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ editRecord ? 'Edit Record' : 'New Disciplinary Record' }}</h3>
                        <button @click="closeModal" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitForm">
                        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div class="form-field" v-if="!editRecord">
                                <label>Student *</label>
                                <select v-model="form.student_id" required>
                                    <option value="">Select student</option>
                                    <option v-for="s in students" :key="s.id" :value="s.id">{{ s.first_name }} {{ s.last_name }} ({{ s.admission_no }})</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Incident Date *</label>
                                <input v-model="form.incident_date" type="date" required />
                            </div>
                            <div class="form-field">
                                <label>Category *</label>
                                <select v-model="form.category" required>
                                    <option value="">Select</option>
                                    <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Severity *</label>
                                <select v-model="form.severity" required>
                                    <option value="minor">Minor</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="major">Major</option>
                                </select>
                            </div>
                            <div v-if="editRecord" class="form-field">
                                <label>Status *</label>
                                <select v-model="form.status">
                                    <option value="open">Open</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="escalated">Escalated</option>
                                </select>
                            </div>
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Description *</label>
                                <textarea v-model="form.description" rows="3" required></textarea>
                            </div>
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Action Taken</label>
                                <textarea v-model="form.action_taken" rows="2"></textarea>
                            </div>
                            <div class="form-field">
                                <label>Consequence</label>
                                <select v-model="form.consequence">
                                    <option value="">— None —</option>
                                    <option v-for="c in CONSEQUENCES" :key="c" :value="c" style="text-transform:capitalize;">{{ c.replace('_', ' ') }}</option>
                                </select>
                            </div>
                            <div v-if="form.consequence === 'suspension' || form.consequence === 'detention'" class="form-field">
                                <label>From — To</label>
                                <div style="display:flex;gap:6px;">
                                    <input v-model="form.consequence_from" type="date" style="flex:1;" />
                                    <input v-model="form.consequence_to" type="date" style="flex:1;" />
                                </div>
                            </div>
                            <div v-if="editRecord" class="form-field" style="grid-column:1/-1;">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                    <input type="checkbox" v-model="form.parent_notified" />
                                    Parent Notified
                                </label>
                            </div>
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Student Statement</label>
                                <textarea v-model="form.student_statement" rows="2"></textarea>
                            </div>
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Internal Notes</label>
                                <textarea v-model="form.notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="closeModal">Cancel</Button>
                            <Button type="submit" :loading="form.processing">{{ editRecord ? 'Update' : 'Save Record' }}</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </SchoolLayout>
</template>

<style scoped>
.disc-stats { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px; }
.stat-card .card-body { padding:16px; }
.stat-value { font-size:1.5rem;font-weight:700; }
.stat-label { font-size:.75rem;color:var(--text-muted);margin-top:4px; }
.modal-backdrop { position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:1000; }
.modal { background:#fff;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1); }
.modal-header { padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center; }
.modal-title { font-size:1rem;font-weight:700;color:#1e293b; }
.modal-close { background:none;border:none;font-size:1.5rem;line-height:1;color:#94a3b8;cursor:pointer; }
.modal-body { padding:20px; }
.modal-footer { padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 12px 12px;display:flex;justify-content:flex-end;gap:10px; }
</style>
