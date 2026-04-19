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

// ── Page view toggle ───────────────────────────────────────────────
// 'list' = records table | 'add' = add-by-class page
const view = ref('list');

// ── List filters ───────────────────────────────────────────────────
const filterStatus   = ref(props.filters?.status ?? '');
const filterSeverity = ref(props.filters?.severity ?? '');
const filterStudent  = ref(props.filters?.student_id ?? '');

const applyFilters = () => {
    router.get('/school/disciplinary', {
        status: filterStatus.value,
        severity: filterSeverity.value,
        student_id: filterStudent.value,
    }, { preserveScroll: true, replace: true });
};

// ── Add-by-class state ─────────────────────────────────────────────
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

const openAdd = () => {
    browseClass.value = ''; browseSection.value = '';
    expandedId.value = null; quickForm.reset();
    view.value = 'add';
};

const backToList = () => {
    view.value = 'list';
    expandedId.value = null;
    quickForm.reset();
};

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

// ── Edit modal ─────────────────────────────────────────────────────
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
        consequence: r.consequence ?? '',
        consequence_from: r.consequence_from?.slice(0, 10) ?? '',
        consequence_to: r.consequence_to?.slice(0, 10) ?? '',
        parent_notified: r.parent_notified,
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

        <!-- ═══════════════════════════════════════════════════════════
             LIST VIEW
        ════════════════════════════════════════════════════════════ -->
        <template v-if="view === 'list'">
            <div class="page-header">
                <h1 class="page-header-title">Disciplinary Records</h1>
                <Button @click="openAdd">+ Add Record</Button>
            </div>

            <!-- Summary cards -->
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

            <!-- Records table -->
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
        </template>

        <!-- ═══════════════════════════════════════════════════════════
             ADD VIEW — class/section browse with inline incident forms
        ════════════════════════════════════════════════════════════ -->
        <template v-else-if="view === 'add'">
            <!-- Page header -->
            <div class="page-header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="back-btn" @click="backToList">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to Records
                    </button>
                    <h1 class="page-header-title" style="margin:0;">Add Disciplinary Incident</h1>
                </div>
            </div>

            <!-- Class / Section filter card -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-body add-filter-bar">
                    <div class="add-filter-field">
                        <label class="add-filter-label">Class</label>
                        <select v-model="browseClass" @change="browseSection = ''; expandedId = null; quickForm.reset();">
                            <option value="">— Select Class —</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="add-filter-field">
                        <label class="add-filter-label">Section</label>
                        <select v-model="browseSection" :disabled="!browseClass" @change="expandedId = null; quickForm.reset();">
                            <option value="">All Sections</option>
                            <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div v-if="browseClass && browsedStudents.length" class="student-pill">
                        {{ browsedStudents.length }} student{{ browsedStudents.length !== 1 ? 's' : '' }}
                    </div>
                </div>
            </div>

            <!-- Empty prompt -->
            <div v-if="!browseClass" class="card">
                <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:56px 20px;">
                    <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="#cbd5e1">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p style="margin:0;color:#94a3b8;font-size:.9rem;">Select a class to view students</p>
                </div>
            </div>

            <div v-else-if="!browsedStudents.length" class="card">
                <div class="card-body" style="text-align:center;padding:40px;color:#94a3b8;">
                    No students found for the selected class / section.
                </div>
            </div>

            <!-- Student list with inline forms -->
            <div v-else class="card" style="overflow:hidden;">
                <!-- Table header -->
                <div class="student-list-header">
                    <span style="width:36px;text-align:center;">#</span>
                    <span style="flex:1;">Student</span>
                    <span style="width:100px;">Roll No</span>
                    <span style="width:140px;"></span>
                </div>

                <!-- Rows -->
                <div class="student-list">
                    <template v-for="(s, idx) in browsedStudents" :key="s.id">

                        <!-- Student row -->
                        <div :class="['student-item', { 'item-active': expandedId === s.id }]">
                            <span class="item-num">{{ idx + 1 }}</span>
                            <span class="item-info">
                                <span class="item-name">{{ s.first_name }} {{ s.last_name }}</span>
                                <span class="item-adm">{{ s.admission_no }}</span>
                            </span>
                            <span class="item-roll">{{ s.current_academic_history?.roll_no || '—' }}</span>
                            <span class="item-action">
                                <button
                                    :class="['incident-btn', expandedId === s.id ? 'btn-cancel' : 'btn-add']"
                                    @click="toggleRow(s.id)"
                                    type="button"
                                >
                                    {{ expandedId === s.id ? '✕ Cancel' : '+ Add Incident' }}
                                </button>
                            </span>
                        </div>

                        <!-- Inline incident form -->
                        <div v-if="expandedId === s.id" class="incident-panel">
                            <form @submit.prevent="submitQuick">
                                <div class="panel-title">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#3b82f6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Recording incident for <strong>{{ s.first_name }} {{ s.last_name }}</strong>
                                </div>

                                <div class="panel-grid">
                                    <div class="form-field">
                                        <label>Date *</label>
                                        <input v-model="quickForm.incident_date" type="date" required />
                                        <span v-if="quickForm.errors.incident_date" class="field-error">{{ quickForm.errors.incident_date }}</span>
                                    </div>
                                    <div class="form-field">
                                        <label>Category *</label>
                                        <select v-model="quickForm.category" required>
                                            <option value="">Select category</option>
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
                                    <div class="form-field">
                                        <label>Consequence</label>
                                        <select v-model="quickForm.consequence">
                                            <option value="">— None —</option>
                                            <option v-for="c in CONSEQUENCES" :key="c" :value="c" style="text-transform:capitalize;">{{ c.replace('_', ' ') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-field panel-full">
                                        <label>Description *</label>
                                        <textarea v-model="quickForm.description" rows="3" required placeholder="Describe the incident in detail…"></textarea>
                                        <span v-if="quickForm.errors.description" class="field-error">{{ quickForm.errors.description }}</span>
                                    </div>
                                    <div class="form-field panel-full">
                                        <label>Action Taken</label>
                                        <input v-model="quickForm.action_taken" type="text" placeholder="Optional — what action was taken?" />
                                    </div>
                                    <div v-if="quickForm.consequence === 'suspension' || quickForm.consequence === 'detention'" class="form-field panel-full">
                                        <label>Consequence Period</label>
                                        <div style="display:flex;gap:10px;align-items:center;">
                                            <input v-model="quickForm.consequence_from" type="date" style="flex:1;" />
                                            <span style="color:#94a3b8;font-size:.8rem;white-space:nowrap;">to</span>
                                            <input v-model="quickForm.consequence_to" type="date" style="flex:1;" />
                                        </div>
                                    </div>
                                </div>

                                <div class="panel-actions">
                                    <button type="button" class="btn-outline" @click="toggleRow(s.id)">Cancel</button>
                                    <Button size="sm" type="submit" :loading="quickForm.processing">Save Record</Button>
                                </div>
                            </form>
                        </div>

                    </template>
                </div>
            </div>
        </template>

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
/* ── Stats ── */
.disc-stats { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px; }
.stat-card .card-body { padding:16px; }
.stat-value { font-size:1.5rem;font-weight:700; }
.stat-label { font-size:.75rem;color:var(--text-muted);margin-top:4px; }

/* ── Back button ── */
.back-btn {
    display:inline-flex;align-items:center;gap:6px;padding:7px 16px;
    background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;
    font-size:.85rem;font-weight:500;color:#475569;cursor:pointer;
    transition:background .15s,color .15s;white-space:nowrap;
}
.back-btn:hover { background:#e2e8f0;color:#1e293b; }

/* ── Add filter bar ── */
.add-filter-bar { display:flex;align-items:flex-end;gap:20px;flex-wrap:wrap; }
.add-filter-field { display:flex;flex-direction:column;gap:5px; }
.add-filter-field select { width:200px; }
.add-filter-label { font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em; }
.student-pill {
    margin-left:auto;padding:6px 16px;background:#eff6ff;
    border:1px solid #bfdbfe;border-radius:20px;
    font-size:.8rem;font-weight:600;color:#1d4ed8;align-self:center;
}

/* ── Student list (flexbox rows) ── */
.student-list-header {
    display:flex;align-items:center;gap:0;
    padding:10px 20px;border-bottom:2px solid #e2e8f0;
    font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;
    background:#f8fafc;
}
.student-list { }

.student-item {
    display:flex;align-items:center;gap:0;
    padding:14px 20px;border-bottom:1px solid #f1f5f9;
    transition:background .12s;cursor:default;
}
.student-item:hover { background:#f8fafc; }
.student-item.item-active { background:#eff6ff;border-left:3px solid #3b82f6;padding-left:17px; }

.item-num  { width:36px;font-size:.8rem;color:#cbd5e1;font-weight:600;text-align:center;flex-shrink:0; }
.item-info { flex:1;display:flex;flex-direction:column;gap:2px; }
.item-name { font-size:.95rem;font-weight:600;color:#1e293b; }
.item-adm  { font-size:.72rem;color:#94a3b8; }
.item-roll { width:100px;font-size:.85rem;color:#64748b; }
.item-action { width:140px;display:flex;justify-content:flex-end; }

.incident-btn {
    padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:600;
    cursor:pointer;border:none;transition:background .15s,color .15s;
}
.btn-add  { background:#3b82f6;color:#fff; }
.btn-add:hover  { background:#2563eb; }
.btn-cancel { background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0; }
.btn-cancel:hover { background:#e2e8f0;color:#1e293b; }

/* ── Incident panel ── */
.incident-panel {
    background:#f0f9ff;border-left:3px solid #3b82f6;
    border-bottom:1px solid #bae6fd;padding:20px 24px;
}
.panel-title {
    display:flex;align-items:center;gap:7px;
    font-size:.82rem;color:#3b82f6;margin-bottom:18px;
    padding-bottom:14px;border-bottom:1px solid #bae6fd;
}
.panel-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:16px; }
.panel-full { grid-column:1/-1; }
.panel-actions { display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:14px;border-top:1px solid #bae6fd; }
.field-error { color:#dc2626;font-size:.72rem;margin-top:3px;display:block; }

.btn-outline {
    padding:7px 18px;border:1px solid #e2e8f0;border-radius:8px;
    background:#fff;color:#64748b;font-size:.85rem;font-weight:500;cursor:pointer;
}
.btn-outline:hover { background:#f1f5f9;color:#1e293b; }

/* ── Edit modal ── */
.modal-backdrop { position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:1000; }
.modal { background:#fff;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1); }
.modal-header { padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center; }
.modal-title { font-size:1rem;font-weight:700;color:#1e293b; }
.modal-close { background:none;border:none;font-size:1.5rem;line-height:1;color:#94a3b8;cursor:pointer; }
.modal-body { padding:20px; }
.modal-footer { padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 12px 12px;display:flex;justify-content:flex-end;gap:10px; }
</style>
