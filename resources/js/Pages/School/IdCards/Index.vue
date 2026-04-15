<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useClassSections } from '@/Composables/useClassSections';

const props = defineProps({
    school:         { type: Object, required: true },
    classes:        { type: Array,  required: true },
    academicYearId: { type: Number, default: null },
    filters:        { type: Object, default: () => ({}) },
});

// ── Filters ──────────────────────────────────────────────────────────────────
const selectedClass   = ref(props.filters.class_id   ?? '');
const selectedSection = ref(props.filters.section_id ?? '');

const { sections, fetchSections } = useClassSections();
if (selectedClass.value) fetchSections(selectedClass.value);

watch(selectedClass, (val) => {
    selectedSection.value = '';
    fetchSections(val);
});

// ── Template settings ─────────────────────────────────────────────────────────
const accentColor   = ref('#1e3a8a');
const cardStyle     = ref('classic');   // classic | modern | minimal
const showPhoto     = ref(true);
const showQr        = ref(true);
const showRollNo    = ref(true);
const showAdmission = ref(false);
const showDob       = ref(false);
const showBlood     = ref(true);
const showParent    = ref(true);
const showAddress   = ref(false);

// ── Preview card (uses props.school + dummy student) ─────────────────────────
const previewStudent = {
    name:         'Aarav Sharma',
    first_name:   'Aarav',
    admission_no: 'ADM/2024/001',
    roll_no:      '12',
    dob:          '2010-03-15',
    blood_group:  'B+',
    gender:       'Male',
    class:        selectedClass.value ? (props.classes.find(c => c.id == selectedClass.value)?.name || 'Class X') : 'Class X',
    section:      'A',
    parent_phone: '9876543210',
    father_name:  'Rajesh Sharma',
    photo_url:    null,
    uuid:         'preview-uuid',
};

// ── Academic year label ───────────────────────────────────────────────────────
const currentYear = new Date().getFullYear();
const academicYearLabel = `${currentYear}-${String(currentYear + 1).slice(2)}`;

// ── Print URL builder ─────────────────────────────────────────────────────────
const printUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedClass.value)   params.set('class_id',   selectedClass.value);
    if (selectedSection.value) params.set('section_id', selectedSection.value);
    params.set('accent_color',   accentColor.value);
    params.set('style',          cardStyle.value);
    params.set('show_photo',     showPhoto.value);
    params.set('show_qr',        showQr.value);
    params.set('show_roll_no',   showRollNo.value);
    params.set('show_admission', showAdmission.value);
    params.set('show_dob',       showDob.value);
    params.set('show_blood',     showBlood.value);
    params.set('show_parent',    showParent.value);
    params.set('show_address',   showAddress.value);
    return `/school/utility/id-cards/print?${params.toString()}`;
});

const goToPrint = () => {
    window.open(printUrl.value, '_blank');
};

// ── Header background for preview ────────────────────────────────────────────
const headerStyle = computed(() => ({ background: accentColor.value }));
const footerStyle = computed(() => ({
    background: cardStyle.value === 'classic' ? '#f8fafc' : accentColor.value,
    color:      cardStyle.value === 'classic' ? '#475569' : '#ffffff',
}));
const cardBorderStyle = computed(() => ({
    border: cardStyle.value === 'minimal' ? `2px solid ${accentColor.value}` : '1px solid #e2e8f0',
}));
</script>

<template>
    <Head title="ID Cards" />
    <SchoolLayout title="ID Cards">

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Bulk ID Cards</h1>
                <p class="text-sm text-slate-500 mt-0.5">Design and print student identity cards</p>
            </div>
            <Button @click="goToPrint" :disabled="!selectedClass">
                Generate &amp; Print
            </Button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── Left panel: Settings ── -->
            <div class="lg:col-span-1 space-y-5">

                <!-- Filter -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Filter Students</h2>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Class <span class="text-red-500">*</span></label>
                            <select v-model="selectedClass"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">— Select class —</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Section</label>
                            <select v-model="selectedSection"
                                    :disabled="!selectedClass || !sections.length"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                                <option value="">All sections</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                            </select>
                        </div>
                    </div>

                    <p v-if="!selectedClass" class="mt-3 text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">
                        Select a class to enable printing.
                    </p>
                </div>

                <!-- Card Style -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Card Style</h2>

                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <button v-for="style in ['classic', 'modern', 'minimal']" :key="style"
                                @click="cardStyle = style"
                                :class="[
                                    'py-2 rounded-lg text-xs font-medium border capitalize transition-all',
                                    cardStyle === style
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-slate-200 text-slate-500 hover:border-slate-300'
                                ]">
                            {{ style }}
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Accent Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" v-model="accentColor"
                                   class="w-8 h-8 rounded cursor-pointer border border-slate-200" />
                            <input type="text" v-model="accentColor"
                                   class="flex-1 border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <!-- Color presets -->
                        <div class="flex gap-1.5 mt-2 flex-wrap">
                            <button v-for="color in ['#1e3a8a','#065f46','#7c2d12','#581c87','#1e293b','#be123c','#0e7490']"
                                    :key="color"
                                    @click="accentColor = color"
                                    :style="{ background: color }"
                                    class="w-6 h-6 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform"
                                    :class="accentColor === color ? 'ring-2 ring-offset-1 ring-slate-400' : ''" />
                        </div>
                    </div>
                </div>

                <!-- Fields to show -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Fields to Include</h2>
                    <div class="space-y-2.5">
                        <label v-for="field in [
                            { model: 'showPhoto',     label: 'Student Photo' },
                            { model: 'showQr',        label: 'QR Code' },
                            { model: 'showRollNo',    label: 'Roll Number' },
                            { model: 'showAdmission', label: 'Admission No' },
                            { model: 'showBlood',     label: 'Blood Group' },
                            { model: 'showDob',       label: 'Date of Birth' },
                            { model: 'showParent',    label: 'Parent Contact' },
                            { model: 'showAddress',   label: 'School Address' },
                        ]" :key="field.model" class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox"
                                   v-model="showPhoto"     v-if="field.model === 'showPhoto'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showQr"        v-else-if="field.model === 'showQr'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showRollNo"    v-else-if="field.model === 'showRollNo'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showAdmission" v-else-if="field.model === 'showAdmission'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showBlood"     v-else-if="field.model === 'showBlood'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showDob"       v-else-if="field.model === 'showDob'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showParent"    v-else-if="field.model === 'showParent'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <input type="checkbox"
                                   v-model="showAddress"   v-else-if="field.model === 'showAddress'"
                                   class="w-4 h-4 rounded text-blue-600" />
                            <span class="text-sm text-slate-600">{{ field.label }}</span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- ── Right panel: Live preview ── -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5">Live Preview</h2>

                    <div class="flex justify-center">
                        <!-- ID Card Preview -->
                        <div class="id-card-preview" :style="cardBorderStyle">

                            <!-- Header -->
                            <div class="card-header" :style="headerStyle">
                                <img v-if="school.logo" :src="school.logo" class="card-logo" />
                                <div v-else class="card-logo-placeholder">🏫</div>
                                <div class="card-school-info">
                                    <div class="card-school-name">{{ school.name }}</div>
                                    <div class="card-school-sub">Student Identity Card</div>
                                    <div class="card-school-sub" v-if="school.board">{{ school.board }}</div>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="card-body">
                                <!-- Photo side -->
                                <div v-if="showPhoto" class="card-photo-col">
                                    <div class="card-photo">
                                        <span class="card-photo-initial">{{ previewStudent.first_name[0] }}</span>
                                    </div>
                                    <div v-if="showBlood && previewStudent.blood_group" class="card-blood">
                                        {{ previewStudent.blood_group }}
                                    </div>
                                </div>

                                <!-- Info side -->
                                <div class="card-info-col">
                                    <div class="card-student-name">{{ previewStudent.name }}</div>
                                    <div class="card-class-row">
                                        Class {{ previewStudent.class }}
                                        <span v-if="previewStudent.section"> — Sec {{ previewStudent.section }}</span>
                                    </div>
                                    <div class="card-fields">
                                        <div v-if="showRollNo" class="card-field">
                                            <span class="cf-label">Roll No</span>
                                            <span class="cf-value">{{ previewStudent.roll_no }}</span>
                                        </div>
                                        <div v-if="showAdmission" class="card-field">
                                            <span class="cf-label">Adm No</span>
                                            <span class="cf-value">{{ previewStudent.admission_no }}</span>
                                        </div>
                                        <div v-if="showDob" class="card-field">
                                            <span class="cf-label">DOB</span>
                                            <span class="cf-value">15/03/2010</span>
                                        </div>
                                        <div v-if="showParent" class="card-field">
                                            <span class="cf-label">Parent</span>
                                            <span class="cf-value">{{ previewStudent.parent_phone }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- QR side -->
                                <div v-if="showQr" class="card-qr-col">
                                    <div class="card-qr-placeholder">
                                        <svg viewBox="0 0 21 21" fill="none" class="card-qr-icon">
                                            <rect x="1" y="1" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                            <rect x="3" y="3" width="4" height="4" fill="currentColor"/>
                                            <rect x="12" y="1" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                            <rect x="14" y="3" width="4" height="4" fill="currentColor"/>
                                            <rect x="1" y="12" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                            <rect x="3" y="14" width="4" height="4" fill="currentColor"/>
                                            <rect x="12" y="12" width="2" height="2" fill="currentColor"/>
                                            <rect x="15" y="12" width="2" height="2" fill="currentColor"/>
                                            <rect x="18" y="12" width="2" height="2" fill="currentColor"/>
                                            <rect x="12" y="15" width="2" height="2" fill="currentColor"/>
                                            <rect x="15" y="15" width="5" height="5" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <div class="card-qr-label">Scan</div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="card-footer" :style="footerStyle">
                                <span v-if="showAddress && school.address">{{ school.address }}</span>
                                <span v-else-if="school.phone">{{ school.phone }}</span>
                                <span v-else>{{ academicYearLabel }}</span>
                                <span class="card-year">{{ academicYearLabel }}</span>
                            </div>

                        </div>
                    </div>

                    <p class="text-center text-xs text-slate-400 mt-4">
                        Preview shows a sample student. Actual cards will have real student photos and data.
                    </p>
                </div>

                <!-- Print info -->
                <div class="mt-4 bg-blue-50 rounded-xl border border-blue-200 p-4">
                    <div class="flex gap-3 items-start">
                        <span class="text-blue-500 text-lg">ℹ</span>
                        <div class="text-sm text-blue-700">
                            <strong>Print tips:</strong> Cards print 2 per row on A4 paper. Use Chrome or Edge for best results.
                            Set margins to "None" or "Minimum" in the print dialog for full bleed.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </SchoolLayout>
</template>

<style scoped>
/* ── ID Card preview dimensions (CR80 proportions, scaled up for screen) ── */
.id-card-preview {
    width: 320px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,0.12);
    font-family: 'Inter', system-ui, sans-serif;
    background: #fff;
}

/* Header */
.card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    color: #fff;
}
.card-logo {
    width: 36px;
    height: 36px;
    object-fit: contain;
    background: rgba(255,255,255,0.15);
    border-radius: 6px;
    padding: 2px;
    flex-shrink: 0;
}
.card-logo-placeholder {
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.card-school-info { flex: 1; min-width: 0; }
.card-school-name {
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: 0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.card-school-sub { font-size: 8.5px; opacity: 0.85; line-height: 1.3; }

/* Body */
.card-body {
    display: flex;
    gap: 8px;
    padding: 10px 12px;
    align-items: flex-start;
}

/* Photo column */
.card-photo-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.card-photo {
    width: 54px;
    height: 64px;
    border-radius: 6px;
    border: 1.5px solid #e2e8f0;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.card-photo-initial {
    font-size: 22px;
    font-weight: 700;
    color: #94a3b8;
    font-family: serif;
}
.card-blood {
    font-size: 8px;
    font-weight: 700;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 1px 5px;
    letter-spacing: 0.5px;
}

/* Info column */
.card-info-col { flex: 1; min-width: 0; }
.card-student-name {
    font-size: 11px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: 2px;
}
.card-class-row {
    font-size: 9px;
    color: #475569;
    font-weight: 500;
    margin-bottom: 5px;
}
.card-fields { display: flex; flex-direction: column; gap: 2px; }
.card-field { display: flex; gap: 4px; align-items: baseline; }
.cf-label { font-size: 8px; color: #94a3b8; font-weight: 600; width: 36px; flex-shrink: 0; }
.cf-value { font-size: 8.5px; color: #334155; }

/* QR column */
.card-qr-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}
.card-qr-placeholder {
    width: 46px;
    height: 46px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
}
.card-qr-icon { width: 100%; height: 100%; color: #334155; }
.card-qr-label { font-size: 7px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

/* Footer */
.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 12px;
    font-size: 7.5px;
    border-top: 1px solid #f1f5f9;
}
.card-year { font-weight: 700; }
</style>
