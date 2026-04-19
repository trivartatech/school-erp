<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    records: Object,
    students: Array,
    classes: Array,
    sections: Array,
    summary: Object,
    filters: Object,
});

const CATEGORIES = ['Misconduct', 'Bullying', 'Damage to Property', 'Dress Code Violation', 'Absenteeism', 'Cheating', 'Disrespect', 'Violence', 'Other'];
const CONSEQUENCES = ['none', 'warning', 'detention', 'parent_call', 'suspension', 'expulsion'];
const TODAY = new Date().toISOString().split('T')[0];

// ── Filters ───────────────────────────────────────────────────────
const filterStatus   = ref(props.filters?.status ?? '');
const filterSeverity = ref(props.filters?.severity ?? '');
const filterStudent  = ref(props.filters?.student_id ?? '');

const applyFilters = () => {
    router.get('/school/disciplinary', { status: filterStatus.value, severity: filterSeverity.value, student_id: filterStudent.value }, { preserveScroll: true, replace: true });
};

// ── Browse-to-Add (class/section → student rows) ─────────────────
const showBrowse    = ref(false);
const browseClass   = ref('');
const browseSection = ref('');
const expandedId    = ref(null);

const filteredSections = computed(() =>
    browseClass.value ? props.sections.filter(s => s.course_class_id == browseClass.value) : []
);

const browsedStudents = computed(() => {
    if (!browseClass.value) return [];
    return props.students.filter(s => {
        const h = s.current_academic_history;
        if (!h || h.class_id != browseClass.value) return false;
        if (browseSection.value && h.section_id != browseSection.value) return false;
        return true;
    });
});

const quickForm = useForm({
    student_id: '', incident_date: TODAY,
    category: '', severity: 'minor', description: '',
    action_taken: '', consequence: '', consequence_from: '', consequence_to: '',
    student_statement: '', notes: '',
});

const openBrowse = () => {
    browseClass.value = ''; browseSection.value = ''; expandedId.value = null;
    quickForm.reset();
    showBrowse.value = true;
};
const closeBrowse = () => { showBrowse.value = false; expandedId.value = null; quickForm.reset(); };

const toggleRow = (studentId) => {
    if (expandedId.value === studentId) { expandedId.value = null; return; }
    expandedId.value = studentId;
    quickForm.reset();
    quickForm.student_id = studentId;
    quickForm.incident_date = TODAY;
    quickForm.severity = 'minor';
};

const submitQuick = () => {
    quickForm.post('/school/disciplinary', {
        preserveScroll: true,
        onSuccess: () => { expandedId.value = null; quickForm.reset(); },
    });
};

// ── Edit Modal ────────────────────────────────────────────────────
const showEdit   = ref(false);
const editRecord = ref(null);

const form = useForm({
    student_id: '', incident_date: TODAY,
    category: '', severity: 'minor', description: '', action_taken: '',
    status: 'open', consequence: '', consequence_from: '', consequence_to: '',
    parent_notified: false, student_statement: '', notes: '',
});

const openEdit = (r) => {
    editRecord.value = r;
    Object.assign(form, {
        student_id: r.student_id, incident_date: r.incident_date?.slice(0, 10),
        category: r.category, severity: r.severity, description: r.description,
        action_taken: r.action_taken ?? '', status: r.status,
        consequence: r.consequence ?? '', consequence_from: r.consequence_from?.slice(0, 10) ?? '',
        consequence_to: r.consequence_to?.slice(0, 10) ?? '', parent_notified: r.parent_notified,
        student_statement: r.student_statement ?? '', notes: r.notes ?? '',
    });
    showEdit.value = true;
};
const closeEdit = () => { showEdit.value = false; editRecord.value = null; };

const submitEdit = () => {
    form.put(`/school/disciplinary/${editRecord.value.id}`, { preserveScroll: true, onSuccess: closeEdit });
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
            <Button @click="openBrowse">+ Add Record</Button>
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

        <!-- Records Table -->
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

        <!-- ── Browse-to-Add Modal ───────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showBrowse" class="modal-backdrop" @click.self="closeBrowse">
                <div class="modal browse-modal">
                    <div class="modal-header">
                        <h3 class="modal-title">Add Disciplinary Incident</h3>
                        <button @click="closeBrowse" class="modal-close">&times;</button>
                    </div>

                    <!-- Class / Section selectors -->
                    <div class="browse-filters">
                        <div class="browse-filter-group">
                            <label>Class</label>
                            <select v-model="browseClass" @change="browseSection = ''; expandedId = null; quickForm.reset();">
                                <option value="">— Select Class —</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div class="browse-filter-group">
                            <label>Section</label>
                            <select v-model="browseSection" :disabled="!browseClass" @change="expandedId = null; quickForm.reset();">
                                <option value="">All Sections</option>
                                <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div v-if="browseClass && browsedStudents.length" class="browse-count">
                            {{ browsedStudents.length }} student{{ browsedStudents.length !== 1 ? 's' : '' }}
                        </div>
                    </div>

                    <!-- Prompt when no class picked -->
                    <div v-if="!browseClass" class="browse-empty">
                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#cbd5e1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p>Select a class above to view students</p>
                    </div>

                    <!-- No students found -->
                    <div v-else-if="!browsedStudents.length" class="browse-empty">
                        <p style="color:#94a3b8;">No students found for the selected class/section.</p>
                    </div>

                    <!-- Student table with inline forms -->
                    <div v-else class="browse-table-wrap">
                        <table class="table browse-table">
                            <thead>
                                <tr>
                                    <th style="width:32px;">#</th>
                                    <th>Student</th>
                                    <th style="width:80px;">Roll No</th>
                                    <th style="width:130px;text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(s, idx) in browsedStudents" :key="s.id">
                                    <!-- Student row -->
                                    <tr :class="['student-row', { 'row-active': expandedId === s.id }]">
                                        <td style="color:#94a3b8;font-size:.8rem;">{{ idx + 1 }}</td>
                                        <td>
                                            <div style="font-weight:500;">{{ s.first_name }} {{ s.last_name }}</div>
                                            <div style="font-size:.7rem;color:#94a3b8;">{{ s.admission_no }}</div>
                                        </td>
                                        <td style="font-size:.85rem;color:#64748b;">{{ s.current_academic_history?.roll_no || '—' }}</td>
                                        <td style="text-align:right;">
                                            <Button
                                                :variant="expandedId === s.id ? 'secondary' : 'primary'"
                                                size="xs"
                                                @click="toggleRow(s.id)"
                                            >
                                                {{ expandedId === s.id ? '✕ Cancel' : '+ Add Incident' }}
                                            </Button>
                                        </td>
                                    </tr>

                                    <!-- Inline incident form -->
                                    <tr v-if="expandedId === s.id" class="inline-form-row">
                                        <td colspan="4" style="padding:0;">
                                            <form @submit.prevent="submitQuick" class="quick-form">
                                                <div class="quick-grid">
                                                    <div class="form-field">
                                                        <label>Date *</label>
                                                        <input v-model="quickForm.incident_date" type="date" required />
                                                        <span v-if="quickForm.errors.incident_date" class="field-error">{{ quickForm.errors.incident_date }}</span>
                                                    </div>
                                                    <div class="form-field">
                                                        <label>Category *</label>
                                                        <select v-model="quickForm.category" required>
                                                            <option value="">Select</option>
                                                            <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
                                                        </select>
                                                        <span v-if="quickForm.errors.category" class="field-error">{{ quickForm.errors.category }}</span>
                                                    </div>
                                                    <div class="form-field">
                                                        <label>Severity *</label>
                                                        <select v-model="quickForm.severity" required>
                                                            <option value="minor">Minor</option>
                                                            <option value="moderate">Moderate</option>
                                                            <option value="major">Major</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-field" style="grid-column:1/-1;">
                                                        <label>Description *</label>
                                                        <textarea v-model="quickForm.description" rows="2" required placeholder="Describe the incident…"></textarea>
                                                        <span v-if="quickForm.errors.description" class="field-error">{{ quickForm.errors.description }}</span>
                                                    </div>
                                                    <div class="form-field">
                                                        <label>Consequence</label>
                                                        <select v-model="quickForm.consequence">
                                                            <option value="">— None —</option>
                                                            <option v-for="c in CONSEQUENCES" :key="c" :value="c" style="text-transform:capitalize;">{{ c.replace('_', ' ') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-field">
                                                        <label>Action Taken</label>
                                                        <input v-model="quickForm.action_taken" type="text" placeholder="Optional" />
                                                    </div>
                                                    <div v-if="quickForm.consequence === 'suspension' || quickForm.consequence === 'detention'" class="form-field" style="grid-column:1/-1;">
                                                        <label>Consequence Period</label>
                                                        <div style="display:flex;gap:6px;">
                                                            <input v-model="quickForm.consequence_from" type="date" style="flex:1;" />
                                                            <span style="align-self:center;color:#94a3b8;">to</span>
                                                            <input v-model="quickForm.consequence_to" type="date" style="flex:1;" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="quick-actions">
                                                    <Button variant="secondary" size="sm" type="button" @click="toggleRow(s.id)">Cancel</Button>
                                                    <Button size="sm" type="submit" :loading="quickForm.processing">Save Record</Button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer" style="justify-content:flex-start;">
                        <Button variant="secondary" @click="closeBrowse">Close</Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Edit Modal ─────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showEdit" class="modal-backdrop" @click.self="closeEdit">
                <div class="modal" style="max-width:580px;width:100%;max-height:90vh;overflow-y:auto;">
                    <div class="modal-header">
                        <h3 class="modal-title">Edit Record — {{ editRecord?.student?.first_name }} {{ editRecord?.student?.last_name }}</h3>
                        <button @click="closeEdit" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submitEdit">
                        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
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
                            <div class="form-field">
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
                            <div class="form-field" style="grid-column:1/-1;">
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
                            <Button variant="secondary" type="button" @click="closeEdit">Cancel</Button>
                            <Button type="submit" :loading="form.processing">Update Record</Button>
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

/* Modals */
.modal-backdrop { position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:1000; }
.modal { background:#fff;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1); }
.modal-header { padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center; }
.modal-title { font-size:1rem;font-weight:700;color:#1e293b; }
.modal-close { background:none;border:none;font-size:1.5rem;line-height:1;color:#94a3b8;cursor:pointer; }
.modal-body { padding:20px; }
.modal-footer { padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 12px 12px;display:flex;justify-content:flex-end;gap:10px; }

/* Browse modal */
.browse-modal { width:min(860px,95vw);max-height:90vh;display:flex;flex-direction:column;overflow:hidden; }
.browse-filters { display:flex;align-items:flex-end;gap:16px;padding:14px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0; }
.browse-filter-group { display:flex;flex-direction:column;gap:4px; }
.browse-filter-group label { font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em; }
.browse-filter-group select { width:180px; }
.browse-count { margin-left:auto;font-size:.8rem;color:#64748b;align-self:center; }

.browse-empty { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:48px 20px;color:#94a3b8;font-size:.9rem; }
.browse-table-wrap { overflow-y:auto;flex:1;min-height:0; }
.browse-table { margin:0; }
.browse-table td, .browse-table th { vertical-align:middle; }

/* Student rows */
.student-row { transition:background .15s; }
.student-row:hover { background:#f8fafc; }
.student-row.row-active { background:#eff6ff; }

/* Inline form */
.inline-form-row td { background:#f0f9ff;border-top:1px dashed #bae6fd;border-bottom:1px dashed #bae6fd; }
.quick-form { padding:14px 16px; }
.quick-grid { display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px; }
.quick-actions { display:flex;justify-content:flex-end;gap:8px;margin-top:12px; }
.field-error { color:#dc2626;font-size:.72rem;margin-top:2px;display:block; }
</style>
