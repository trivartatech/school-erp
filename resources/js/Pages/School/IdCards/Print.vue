<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import IdCardQR from '@/Components/IdCardQR.vue';

const props = defineProps({
    students: { type: Array,  required: true },
    school:   { type: Object, required: true },
    template: { type: Object, required: true }, // server-side fallback (unused; overridden by localStorage)
});

// ── Load template from localStorage (saved by designer) ──────────
const STORAGE_KEY = 'idcard_template_v2';

const tpl = ref(null);

const defaultTpl = () => ({
    background: { type: 'color', value: '#1e3a8a' },
    columns: 2,
    elements: [],
});

onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        tpl.value = saved ? JSON.parse(saved) : defaultTpl();
    } catch {
        tpl.value = defaultTpl();
    }
});

// ── Card background style ─────────────────────────────────────────
const cardBg = computed(() => {
    if (!tpl.value) return {};
    const bg = tpl.value.background;
    return bg?.type === 'image'
        ? { backgroundImage: `url(${bg.value})`, backgroundSize: 'cover', backgroundPosition: 'center' }
        : { background: bg?.value || '#1e3a8a' };
});

// ── Element positioning styles ────────────────────────────────────
const elStyle = (el) => ({
    position: 'absolute',
    left:  el.x + '%',
    top:   el.y + '%',
    width: el.w + '%',
    ...(el.h ? { height: el.h + '%' } : {}),
    overflow: 'hidden',
    boxSizing: 'border-box',
});

const textStyle = (el) => ({
    fontSize:     (el.fontSize || 11) + 'px',
    fontWeight:   el.fontWeight  || 'normal',
    color:        el.color       || '#ffffff',
    textAlign:    el.textAlign   || 'left',
    lineHeight:   '1.2',
    whiteSpace:   'nowrap',
    overflow:     'hidden',
    textOverflow: 'ellipsis',
    display:      'block',
});

// ── Field value resolver ──────────────────────────────────────────
const currentYear = new Date().getFullYear();
const academicYear = `${currentYear}-${String(currentYear + 1).slice(2)}`;

const fieldValue = (student, field) => {
    const map = {
        name:          student.name,
        first_name:    student.first_name,
        last_name:     student.last_name,
        class:         student.class    ? `Class ${student.class}` : '',
        section:       student.section  || '',
        class_section: student.class && student.section
                           ? `${student.class} - ${student.section}`
                           : (student.class || ''),
        roll_no:       student.roll_no      || '',
        admission_no:  student.admission_no || '',
        blood_group:   student.blood_group  || '',
        dob:           student.dob ? new Date(student.dob).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '',
        parent_phone:  student.parent_phone || '',
        father_name:   student.father_name  || '',
        school_name:   props.school?.name   || '',
        academic_year: academicYear,
    };
    return map[field] ?? '';
};

const getFieldText = (el, student) =>
    (el.prefix || '') + fieldValue(student, el.field) + (el.suffix || '');

// ── QR URL ────────────────────────────────────────────────────────
const qrUrl = (uuid) => `${window.location.origin}/q/${uuid}`;

// ── Columns per page → CSS class ─────────────────────────────────
const gridClass = computed(() => {
    const cols = tpl.value?.columns || 2;
    return `grid-cols-${cols}`;
});
</script>

<template>
    <!-- Toolbar (hidden on print) -->
    <div class="no-print toolbar">
        <div class="toolbar-left">
            <span class="toolbar-title">ID Cards</span>
            <span class="toolbar-count">{{ students.length }} student{{ students.length !== 1 ? 's' : '' }}</span>
            <span v-if="tpl" class="toolbar-cols">{{ tpl.columns }} col{{ tpl.columns !== 1 ? 's' : '' }}/page</span>
        </div>
        <div class="toolbar-right">
            <button @click="window.print()" class="btn-print">🖨 Print</button>
            <Link href="/school/utility/id-cards" class="btn-back">← Back to Designer</Link>
        </div>
    </div>

    <!-- Loading state -->
    <div v-if="!tpl" class="no-print loading">Loading template…</div>

    <!-- Empty state -->
    <div v-else-if="!students.length" class="no-print empty-state">
        <div class="empty-icon">🪪</div>
        <h2>No students found</h2>
        <p>Try adjusting the class or section filter.</p>
        <Link href="/school/utility/id-cards" class="btn-back-link">← Back to Designer</Link>
    </div>

    <!-- Cards grid -->
    <div v-else-if="tpl" class="cards-page" :class="gridClass">
        <div v-for="student in students" :key="student.id" class="id-card" :style="cardBg">

            <template v-for="el in tpl.elements" :key="el.id">

                <!-- Photo -->
                <div v-if="el.type === 'photo'" :style="elStyle(el)">
                    <img v-if="student.photo_url"
                         :src="student.photo_url"
                         :style="{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: (el.borderRadius || 0) + 'px', display: 'block' }" />
                    <div v-else
                         :style="{ width: '100%', height: '100%', background: 'rgba(255,255,255,0.2)', display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: (el.borderRadius || 0) + 'px', fontSize: '1.4em', color: '#fff', fontWeight: 'bold' }">
                        {{ (student.first_name || 'S')[0].toUpperCase() }}
                    </div>
                </div>

                <!-- QR Code -->
                <div v-else-if="el.type === 'qr' && student.uuid" :style="{ ...elStyle(el), padding: '2px', background: '#fff', boxSizing: 'border-box', borderRadius: '2px' }">
                    <IdCardQR :value="qrUrl(student.uuid)" :size="80" style="width:100%;height:100%" />
                </div>

                <!-- Divider line -->
                <div v-else-if="el.type === 'line'" :style="{ ...elStyle(el), borderTop: `1px solid ${el.color || '#fff'}` }"></div>

                <!-- Text / field -->
                <div v-else-if="el.type === 'text' || el.type === 'field'" :style="{ ...elStyle(el), ...textStyle(el) }">
                    <template v-if="el.type === 'text'">{{ el.text }}</template>
                    <template v-else>{{ getFieldText(el, student) }}</template>
                </div>

            </template>
        </div>
    </div>
</template>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: #e2e8f0;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ── Toolbar ── */
.toolbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    background: #1e293b;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 24px;
    height: 48px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.toolbar-left  { display: flex; align-items: center; gap: 10px; }
.toolbar-right { display: flex; align-items: center; gap: 10px; }
.toolbar-title { font-weight: 700; font-size: 15px; }
.toolbar-count,
.toolbar-cols  { font-size: 12px; color: #94a3b8; background: #334155; padding: 2px 8px; border-radius: 12px; }
.btn-print {
    background: #2563eb; color: #fff; border: none;
    border-radius: 8px; padding: 6px 18px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background 0.15s;
}
.btn-print:hover { background: #1d4ed8; }
.btn-back { color: #94a3b8; font-size: 13px; text-decoration: none; padding: 6px 12px; border-radius: 8px; transition: color 0.15s; }
.btn-back:hover { color: #fff; }

/* ── States ── */
.loading    { display: flex; align-items: center; justify-content: center; min-height: 100vh; font-size: 14px; color: #64748b; margin-top: 48px; }
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; gap: 8px; color: #475569; margin-top: 48px; }
.empty-icon  { font-size: 48px; }
.empty-state h2 { font-size: 20px; font-weight: 700; color: #1e293b; }
.empty-state p  { font-size: 14px; }
.btn-back-link { margin-top: 12px; color: #2563eb; text-decoration: underline; font-size: 14px; }

/* ── Cards page (screen) ── */
.cards-page {
    margin-top: 56px;
    padding: 20px;
    display: grid;
    gap: 16px;
    justify-items: center;
}
.cards-page.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 420px)); justify-content: center; }
.cards-page.grid-cols-2 { grid-template-columns: repeat(2, 340px); }
.cards-page.grid-cols-4 { grid-template-columns: repeat(4, 240px); }

/* ── Single ID Card ── */
.id-card {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    page-break-inside: avoid;
    break-inside: avoid;
    /* CR80 aspect ratio: 85.6 / 54 */
    aspect-ratio: 85.6 / 54;
}
.cards-page.grid-cols-1 .id-card { width: 420px; }
.cards-page.grid-cols-2 .id-card { width: 340px; }
.cards-page.grid-cols-4 .id-card { width: 240px; }

/* ── Print ── */
@media print {
    .no-print { display: none !important; }
    body { background: #fff; }

    .cards-page {
        margin-top: 0;
        padding: 5mm;
        gap: 5mm;
    }

    /* 1 column: 1 large card per page */
    .cards-page.grid-cols-1 {
        grid-template-columns: 1fr;
        justify-items: center;
    }
    .cards-page.grid-cols-1 .id-card {
        width: 180mm;
        border-radius: 4mm;
        box-shadow: none;
        border: 0.5pt solid #d1d5db;
        page-break-after: always;
    }

    /* 2 columns: 2 cards per row, ~4-5 rows per A4 = ~8-10 cards */
    .cards-page.grid-cols-2 {
        grid-template-columns: repeat(2, 85.6mm);
    }
    .cards-page.grid-cols-2 .id-card {
        width: 85.6mm;
        border-radius: 2mm;
        box-shadow: none;
        border: 0.5pt solid #d1d5db;
    }

    /* 4 columns: 4 per row, smaller */
    .cards-page.grid-cols-4 {
        grid-template-columns: repeat(4, 42mm);
    }
    .cards-page.grid-cols-4 .id-card {
        width: 42mm;
        border-radius: 1mm;
        box-shadow: none;
        border: 0.5pt solid #d1d5db;
    }

    @page { size: A4; margin: 10mm; }
}
</style>
