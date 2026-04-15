<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useClassSections } from '@/Composables/useClassSections';

const props = defineProps({
    school:  { type: Object, required: true },
    classes: { type: Array,  required: true },
    filters: { type: Object, default: () => ({}) },
});

// ── Filters ──────────────────────────────────────────────────────
const selectedClass   = ref(props.filters.class_id   ?? '');
const selectedSection = ref(props.filters.section_id ?? '');
const { sections, fetchSections } = useClassSections();
if (selectedClass.value) fetchSections(selectedClass.value);
watch(selectedClass, val => { selectedSection.value = ''; fetchSections(val); });

// ── Card canvas size for editing (CR80 ratio: 85.6 / 54 = 1.585) ──
const CANVAS_W = 514;
const CANVAS_H = 324;

// ── Available field definitions ───────────────────────────────────
const FIELDS = [
    { type: 'photo',  label: 'Photo',            icon: '👤', defaultW: 22, defaultH: 60 },
    { type: 'qr',     label: 'QR Code',           icon: '▦',  defaultW: 20, defaultH: 55 },
    { type: 'field',  field: 'school_name',        label: 'School Name',      icon: '🏫', defaultW: 70 },
    { type: 'field',  field: 'name',               label: 'Full Name',        icon: '👤', defaultW: 55 },
    { type: 'field',  field: 'class_section',      label: 'Class & Section',  icon: '🎓', defaultW: 45 },
    { type: 'field',  field: 'class',              label: 'Class',            icon: '🎓', defaultW: 28 },
    { type: 'field',  field: 'section',            label: 'Section',          icon: '🔤', defaultW: 22 },
    { type: 'field',  field: 'roll_no',            label: 'Roll Number',      icon: '#',  defaultW: 35 },
    { type: 'field',  field: 'admission_no',       label: 'Admission No',     icon: '#',  defaultW: 42 },
    { type: 'field',  field: 'blood_group',        label: 'Blood Group',      icon: '🩸', defaultW: 20 },
    { type: 'field',  field: 'dob',                label: 'Date of Birth',    icon: '📅', defaultW: 40 },
    { type: 'field',  field: 'parent_phone',       label: 'Parent Phone',     icon: '📞', defaultW: 40 },
    { type: 'field',  field: 'father_name',        label: 'Father Name',      icon: '👨', defaultW: 45 },
    { type: 'field',  field: 'academic_year',      label: 'Academic Year',    icon: '📆', defaultW: 28 },
    { type: 'text',   label: 'Custom Text',        icon: 'T',  defaultW: 40 },
    { type: 'line',   label: 'Divider Line',       icon: '—',  defaultW: 80 },
];

// ── Default template ──────────────────────────────────────────────
const defaultTemplate = () => ({
    background: { type: 'color', value: '#1e3a8a' },
    columns: 2,
    elements: [
        { id: 'e1', type: 'field', field: 'school_name', label: 'School Name',     x: 2,  y: 3,  w: 96, fontSize: 12, fontWeight: 'bold',   color: '#ffffff', textAlign: 'center', prefix: '', suffix: '' },
        { id: 'e2', type: 'text',  text: 'STUDENT IDENTITY CARD',                  x: 2,  y: 12, w: 96, fontSize: 8,  fontWeight: 'normal',  color: '#bfdbfe', textAlign: 'center' },
        { id: 'e3', type: 'photo',                                                  x: 3,  y: 20, w: 22, h: 65, borderRadius: 4 },
        { id: 'e4', type: 'field', field: 'name',        label: 'Full Name',        x: 27, y: 22, w: 48, fontSize: 13, fontWeight: 'bold',   color: '#ffffff', textAlign: 'left',   prefix: '', suffix: '' },
        { id: 'e5', type: 'field', field: 'class_section', label: 'Class & Section', x: 27, y: 38, w: 48, fontSize: 10, fontWeight: 'normal', color: '#bfdbfe', textAlign: 'left',   prefix: 'Class: ', suffix: '' },
        { id: 'e6', type: 'field', field: 'roll_no',     label: 'Roll No',          x: 27, y: 53, w: 28, fontSize: 10, fontWeight: 'normal', color: '#e2e8f0', textAlign: 'left',   prefix: 'Roll: ', suffix: '' },
        { id: 'e7', type: 'field', field: 'blood_group', label: 'Blood Group',      x: 57, y: 53, w: 18, fontSize: 10, fontWeight: 'bold',   color: '#fca5a5', textAlign: 'left',   prefix: '', suffix: '' },
        { id: 'e8', type: 'qr',                                                     x: 78, y: 20, w: 20, h: 65 },
        { id: 'e9', type: 'field', field: 'academic_year', label: 'Academic Year',  x: 2,  y: 90, w: 96, fontSize: 8,  fontWeight: 'normal', color: '#94a3b8', textAlign: 'center', prefix: '', suffix: '' },
    ],
});

// ── Template state ────────────────────────────────────────────────
const template = ref(defaultTemplate());
const STORAGE_KEY = 'idcard_template_v2';

const saveTemplate = () => localStorage.setItem(STORAGE_KEY, JSON.stringify(template.value));
const loadTemplate = () => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) template.value = { ...defaultTemplate(), ...JSON.parse(saved) };
    } catch { /* use default */ }
};

watch(template, saveTemplate, { deep: true });

// ── Drag logic ────────────────────────────────────────────────────
const canvasRef = ref(null);
const dragging  = ref(null);
const selected  = ref(null);

const startDrag = (e, el) => {
    if (e.button !== 0) return;
    e.preventDefault();
    e.stopPropagation();
    selected.value = el.id;
    const canvasRect = canvasRef.value.getBoundingClientRect();
    const elPxX = (el.x / 100) * canvasRect.width;
    const elPxY = (el.y / 100) * canvasRect.height;
    dragging.value = {
        id: el.id,
        offsetX: e.clientX - canvasRect.left - elPxX,
        offsetY: e.clientY - canvasRect.top  - elPxY,
    };
};

const onMouseMove = (e) => {
    if (!dragging.value || !canvasRef.value) return;
    const rect = canvasRef.value.getBoundingClientRect();
    const el   = template.value.elements.find(el => el.id === dragging.value.id);
    if (!el) return;
    let nx = ((e.clientX - rect.left - dragging.value.offsetX) / rect.width)  * 100;
    let ny = ((e.clientY - rect.top  - dragging.value.offsetY) / rect.height) * 100;
    // Snap to 0.5% grid
    nx = Math.round(nx * 2) / 2;
    ny = Math.round(ny * 2) / 2;
    el.x = Math.max(0, Math.min(100 - el.w, nx));
    el.y = Math.max(0, Math.min(97, ny));
};

const stopDrag = () => { dragging.value = null; };

const clickCanvas = (e) => {
    if (e.target === canvasRef.value) selected.value = null;
};

onMounted(() => {
    loadTemplate();
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', stopDrag);
});
onUnmounted(() => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', stopDrag);
});

// ── Selected element ──────────────────────────────────────────────
const selectedEl = computed(() => template.value.elements.find(e => e.id === selected.value) ?? null);

const deleteSelected = () => {
    if (!selected.value) return;
    template.value.elements = template.value.elements.filter(e => e.id !== selected.value);
    selected.value = null;
};

const duplicateSelected = () => {
    if (!selectedEl.value) return;
    const clone = { ...selectedEl.value, id: 'e' + Date.now(), x: selectedEl.value.x + 2, y: selectedEl.value.y + 2 };
    template.value.elements.push(clone);
    selected.value = clone.id;
};

// ── Add element ───────────────────────────────────────────────────
const addElement = (def) => {
    const id = 'e' + Date.now();
    const base = { id, type: def.type, x: 10, y: 40, w: def.defaultW || 40 };
    if (def.type === 'photo') {
        Object.assign(base, { h: def.defaultH || 55, borderRadius: 4 });
    } else if (def.type === 'qr') {
        Object.assign(base, { h: def.defaultH || 55 });
    } else if (def.type === 'field') {
        Object.assign(base, { field: def.field, label: def.label, fontSize: 11, fontWeight: 'normal', color: '#ffffff', textAlign: 'left', prefix: '', suffix: '' });
    } else if (def.type === 'text') {
        Object.assign(base, { text: 'Your text', fontSize: 11, fontWeight: 'normal', color: '#ffffff', textAlign: 'left' });
    } else if (def.type === 'line') {
        Object.assign(base, { color: '#ffffff' });
    }
    template.value.elements.push(base);
    selected.value = id;
};

// ── Background ────────────────────────────────────────────────────
const bgInput = ref(null);

const onBgUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { template.value.background = { type: 'image', value: ev.target.result }; };
    reader.readAsDataURL(file);
    e.target.value = '';
};

const removeBgImage = () => {
    template.value.background = { type: 'color', value: '#1e3a8a' };
};

// ── Canvas styles ─────────────────────────────────────────────────
const canvasBg = computed(() => {
    const bg = template.value.background;
    return bg.type === 'image'
        ? { backgroundImage: `url(${bg.value})`, backgroundSize: 'cover', backgroundPosition: 'center' }
        : { background: bg.value };
});

const elStyle = (el) => ({
    position: 'absolute',
    left:  el.x + '%',
    top:   el.y + '%',
    width: el.w + '%',
    ...(el.h ? { height: el.h + '%' } : {}),
    cursor: 'move',
    userSelect: 'none',
    zIndex: selected.value === el.id ? 20 : 5,
    outline: selected.value === el.id ? '1.5px dashed rgba(255,255,255,0.9)' : '1px dashed rgba(255,255,255,0.15)',
    outlineOffset: '1px',
    boxSizing: 'border-box',
});

const textCss = (el) => ({
    fontSize:   (el.fontSize || 11) + 'px',
    fontWeight: el.fontWeight || 'normal',
    color:      el.color || '#ffffff',
    textAlign:  el.textAlign || 'left',
    lineHeight: '1.2',
    overflow:   'hidden',
    whiteSpace: 'nowrap',
    textOverflow: 'ellipsis',
});

// ── Sample data for preview ───────────────────────────────────────
const SAMPLE = {
    name:          'Aarav Sharma',
    class:         'Class X',
    section:       'A',
    class_section: 'X - A',
    roll_no:       '12',
    admission_no:  'ADM/24/001',
    blood_group:   'B+',
    dob:           '15 Mar 2010',
    parent_phone:  '9876543210',
    father_name:   'Raj Sharma',
    school_name:   props.school?.name || 'School Name',
    academic_year: '2026-27',
};

const getPreview = (el) => {
    if (el.type === 'text') return el.text || '';
    return (el.prefix || '') + (SAMPLE[el.field] || el.label || '') + (el.suffix || '');
};

// ── Print ─────────────────────────────────────────────────────────
const printUrl = computed(() => {
    const p = new URLSearchParams();
    if (selectedClass.value)   p.set('class_id',   selectedClass.value);
    if (selectedSection.value) p.set('section_id', selectedSection.value);
    return `/school/utility/id-cards/print?${p.toString()}`;
});

const goToPrint = () => {
    saveTemplate();
    window.open(printUrl.value, '_blank');
};

const resetTemplate = () => {
    if (!confirm('Reset to default template? Your current design will be lost.')) return;
    template.value = defaultTemplate();
    selected.value = null;
};
</script>

<template>
    <Head title="ID Card Designer" />
    <SchoolLayout title="ID Card Designer">

        <!-- ── Top bar ── -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-800">ID Card Designer</h1>
                <p class="text-sm text-slate-500 mt-0.5">Design your card template, then generate &amp; print</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Class filter -->
                <select v-model="selectedClass"
                        class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Select class —</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                </select>

                <select v-model="selectedSection" :disabled="!selectedClass || !sections.length"
                        class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                    <option value="">All sections</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>

                <!-- Columns per page -->
                <div class="flex items-center gap-1 border border-slate-300 rounded-lg overflow-hidden">
                    <span class="text-xs text-slate-500 px-2">Cols/page</span>
                    <button v-for="n in [1, 2, 4]" :key="n"
                            @click="template.columns = n"
                            :class="['px-3 py-1.5 text-sm font-medium border-l border-slate-300 transition-colors',
                                     template.columns === n ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100']">
                        {{ n }}
                    </button>
                </div>

                <button @click="resetTemplate" class="px-3 py-1.5 text-sm text-slate-500 border border-slate-300 rounded-lg hover:bg-slate-50">
                    Reset
                </button>

                <Button @click="goToPrint" :disabled="!selectedClass">
                    Generate &amp; Print
                </Button>
            </div>
        </div>

        <!-- ── Three-column layout ── -->
        <div class="flex gap-4 items-start">

            <!-- ── Left: Elements palette ── -->
            <div class="w-44 flex-shrink-0 bg-white rounded-xl border border-slate-200 p-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Elements</div>
                <div class="space-y-1">
                    <button v-for="def in FIELDS" :key="def.type + (def.field || '')"
                            @click="addElement(def)"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition-colors text-left border border-transparent hover:border-blue-200">
                        <span class="text-base w-5 text-center flex-shrink-0">{{ def.icon || '▪' }}</span>
                        <span class="truncate">{{ def.label }}</span>
                    </button>
                </div>

                <!-- Background section -->
                <div class="mt-4 pt-3 border-t border-slate-200">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Background</div>

                    <div v-if="template.background.type === 'color'" class="flex items-center gap-2 mb-2">
                        <input type="color" v-model="template.background.value"
                               class="w-8 h-8 rounded border border-slate-200 cursor-pointer flex-shrink-0" />
                        <input type="text" v-model="template.background.value"
                               class="flex-1 border border-slate-300 rounded px-2 py-1 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-blue-400" />
                    </div>

                    <div v-else class="flex items-center gap-2 mb-2">
                        <div class="text-xs text-green-600 flex-1">Image uploaded</div>
                        <button @click="removeBgImage" class="text-xs text-red-500 hover:text-red-700">✕ Remove</button>
                    </div>

                    <label class="block w-full text-center py-1.5 text-xs bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer transition-colors text-slate-600 border border-slate-300">
                        Upload Image
                        <input ref="bgInput" type="file" accept="image/*" class="hidden" @change="onBgUpload" />
                    </label>
                    <p class="text-xs text-slate-400 mt-1">Upload your pre-designed card as PNG/JPG background</p>
                </div>
            </div>

            <!-- ── Center: Canvas ── -->
            <div class="flex-1 min-w-0">
                <div class="bg-slate-100 rounded-xl p-4 flex flex-col items-center gap-3">

                    <!-- Canvas -->
                    <div
                        ref="canvasRef"
                        class="relative overflow-hidden rounded-lg shadow-xl"
                        :style="[canvasBg, { width: CANVAS_W + 'px', height: CANVAS_H + 'px', flexShrink: 0 }]"
                        @click="clickCanvas"
                    >
                        <!-- Render elements -->
                        <div
                            v-for="el in template.elements" :key="el.id"
                            :style="elStyle(el)"
                            @mousedown="(e) => startDrag(e, el)"
                            @click.stop="selected = el.id"
                        >
                            <!-- Photo placeholder -->
                            <template v-if="el.type === 'photo'">
                                <div class="w-full h-full bg-white/20 flex items-center justify-center overflow-hidden"
                                     :style="{ borderRadius: (el.borderRadius || 0) + 'px' }">
                                    <span class="text-3xl">👤</span>
                                </div>
                            </template>

                            <!-- QR placeholder -->
                            <template v-else-if="el.type === 'qr'">
                                <div class="w-full h-full bg-white flex items-center justify-center rounded overflow-hidden p-0.5">
                                    <svg viewBox="0 0 21 21" fill="none" class="w-full h-full text-slate-800">
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
                            </template>

                            <!-- Divider line -->
                            <template v-else-if="el.type === 'line'">
                                <div class="w-full" :style="{ borderTop: `1px solid ${el.color || '#ffffff'}` }"></div>
                            </template>

                            <!-- Text / field -->
                            <template v-else>
                                <div :style="textCss(el)">{{ getPreview(el) }}</div>
                            </template>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400">
                        Click an element to select • Drag to reposition • Use properties panel to style
                    </p>
                </div>
            </div>

            <!-- ── Right: Properties panel ── -->
            <div class="w-52 flex-shrink-0">
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Properties</div>

                    <div v-if="!selectedEl" class="text-sm text-slate-400 text-center py-6">
                        Click an element on the canvas to edit its properties
                    </div>

                    <template v-else>
                        <!-- Element label -->
                        <div class="text-xs font-medium text-slate-700 mb-3 px-2 py-1.5 bg-slate-50 rounded-lg truncate">
                            {{ selectedEl.label || selectedEl.type }}
                        </div>

                        <div class="space-y-3">
                            <!-- Position -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">X %</label>
                                    <input type="number" v-model.number="selectedEl.x" min="0" max="99" step="0.5"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">Y %</label>
                                    <input type="number" v-model.number="selectedEl.y" min="0" max="99" step="0.5"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                </div>
                            </div>

                            <!-- Size -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">Width %</label>
                                    <input type="number" v-model.number="selectedEl.w" min="1" max="100" step="1"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                </div>
                                <div v-if="selectedEl.h !== undefined">
                                    <label class="block text-xs text-slate-500 mb-0.5">Height %</label>
                                    <input type="number" v-model.number="selectedEl.h" min="1" max="100" step="1"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                </div>
                            </div>

                            <!-- Text-specific properties -->
                            <template v-if="selectedEl.type !== 'photo' && selectedEl.type !== 'qr' && selectedEl.type !== 'line'">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">Font Size</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="selectedEl.fontSize" min="6" max="36" step="1"
                                               class="flex-1" />
                                        <span class="text-xs text-slate-600 w-7">{{ selectedEl.fontSize }}</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">Color</label>
                                    <div class="flex items-center gap-1">
                                        <input type="color" v-model="selectedEl.color"
                                               class="w-7 h-7 rounded border border-slate-200 cursor-pointer flex-shrink-0" />
                                        <input type="text" v-model="selectedEl.color"
                                               class="flex-1 border border-slate-300 rounded px-2 py-1 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button @click="selectedEl.fontWeight = selectedEl.fontWeight === 'bold' ? 'normal' : 'bold'"
                                            :class="['flex-1 py-1 text-xs rounded border font-bold transition-colors',
                                                     selectedEl.fontWeight === 'bold' ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-300 text-slate-600']">
                                        Bold
                                    </button>
                                    <button v-for="align in ['left','center','right']" :key="align"
                                            @click="selectedEl.textAlign = align"
                                            :class="['flex-1 py-1 text-xs rounded border transition-colors',
                                                     selectedEl.textAlign === align ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-300 text-slate-600']">
                                        {{ align === 'left' ? '⬅' : align === 'center' ? '↔' : '➡' }}
                                    </button>
                                </div>

                                <!-- Custom text content -->
                                <div v-if="selectedEl.type === 'text'">
                                    <label class="block text-xs text-slate-500 mb-0.5">Text</label>
                                    <input type="text" v-model="selectedEl.text"
                                           class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                </div>

                                <!-- Prefix / Suffix for field elements -->
                                <template v-if="selectedEl.type === 'field'">
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-0.5">Prefix</label>
                                        <input type="text" v-model="selectedEl.prefix" placeholder="e.g. Roll: "
                                               class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-0.5">Suffix</label>
                                        <input type="text" v-model="selectedEl.suffix" placeholder="optional"
                                               class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                    </div>
                                </template>
                            </template>

                            <!-- Divider line color -->
                            <template v-if="selectedEl.type === 'line'">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">Color</label>
                                    <div class="flex items-center gap-1">
                                        <input type="color" v-model="selectedEl.color"
                                               class="w-7 h-7 rounded border border-slate-200 cursor-pointer flex-shrink-0" />
                                        <input type="text" v-model="selectedEl.color"
                                               class="flex-1 border border-slate-300 rounded px-2 py-1 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-blue-400" />
                                    </div>
                                </div>
                            </template>

                            <!-- Photo border radius -->
                            <template v-if="selectedEl.type === 'photo'">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-0.5">Corner Radius</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="selectedEl.borderRadius" min="0" max="50" step="1"
                                               class="flex-1" />
                                        <span class="text-xs text-slate-600 w-7">{{ selectedEl.borderRadius }}</span>
                                    </div>
                                </div>
                            </template>

                            <!-- Actions -->
                            <div class="flex gap-2 pt-1 border-t border-slate-100">
                                <button @click="duplicateSelected"
                                        class="flex-1 py-1.5 text-xs bg-slate-100 hover:bg-slate-200 rounded text-slate-600 transition-colors">
                                    Duplicate
                                </button>
                                <button @click="deleteSelected"
                                        class="flex-1 py-1.5 text-xs bg-red-50 hover:bg-red-100 rounded text-red-600 transition-colors">
                                    Delete
                                </button>
                            </div>

                            <!-- Layer order -->
                            <div class="flex gap-2">
                                <button @click="template.elements.push(template.elements.splice(template.elements.findIndex(e => e.id === selected), 1)[0])"
                                        class="flex-1 py-1 text-xs border border-slate-300 rounded text-slate-500 hover:bg-slate-50 transition-colors">
                                    Bring Front
                                </button>
                                <button @click="template.elements.unshift(template.elements.splice(template.elements.findIndex(e => e.id === selected), 1)[0])"
                                        class="flex-1 py-1 text-xs border border-slate-300 rounded text-slate-500 hover:bg-slate-50 transition-colors">
                                    Send Back
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Tips -->
                <div class="mt-3 bg-blue-50 border border-blue-200 rounded-xl p-3">
                    <p class="text-xs text-blue-700 font-semibold mb-1">Tips</p>
                    <ul class="text-xs text-blue-600 space-y-1 list-disc list-inside">
                        <li>Upload a designed card image as background</li>
                        <li>Add variables on top of it</li>
                        <li>Drag to position precisely</li>
                        <li>Template auto-saves</li>
                    </ul>
                </div>
            </div>

        </div>

    </SchoolLayout>
</template>
