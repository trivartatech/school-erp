<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import IdCardQR from '@/Components/IdCardQR.vue';

const props = defineProps({
    students: { type: Array, required: true },
    school:   { type: Object, required: true },
    template: { type: Object, required: true },
});

const currentYear = new Date().getFullYear();
const academicYearLabel = `${currentYear}-${String(currentYear + 1).slice(2)}`;

const accentColor = computed(() => props.template.accent_color || '#1e3a8a');

const headerBg = computed(() => ({ background: accentColor.value }));
const footerBg = computed(() => {
    if (props.template.style === 'classic') return { background: '#f8fafc', color: '#475569' };
    if (props.template.style === 'minimal') return { background: '#fff', color: accentColor.value, borderTop: `2px solid ${accentColor.value}` };
    return { background: accentColor.value, color: '#fff' };
});
const cardBorder = computed(() => {
    if (props.template.style === 'minimal') return { border: `2px solid ${accentColor.value}` };
    return {};
});

const qrUrl = (uuid) => `${window.location.origin}/q/${uuid}`;

const formatDob = (dob) => {
    if (!dob) return '';
    return new Date(dob).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
};

const t = computed(() => props.template);
</script>

<template>
    <!-- Print toolbar -->
    <div class="no-print toolbar">
        <div class="toolbar-left">
            <span class="toolbar-title">ID Cards</span>
            <span class="toolbar-count">{{ students.length }} student{{ students.length !== 1 ? 's' : '' }}</span>
        </div>
        <div class="toolbar-right">
            <button @click="window.print()" class="btn-print">🖨 Print</button>
            <Link href="/school/students/id-cards" class="btn-back">← Back to Designer</Link>
        </div>
    </div>

    <!-- Empty state -->
    <div v-if="!students.length" class="no-print empty-state">
        <div class="empty-icon">🪪</div>
        <h2>No students found</h2>
        <p>Try adjusting the class or section filter.</p>
        <Link href="/school/students/id-cards" class="btn-back-link">← Back to Designer</Link>
    </div>

    <!-- Cards grid -->
    <div v-else class="cards-page">
        <div
            v-for="student in students"
            :key="student.id"
            class="id-card"
            :style="cardBorder"
        >
            <!-- ── Header ── -->
            <div class="card-header" :style="headerBg">
                <img v-if="school.logo" :src="school.logo" class="hdr-logo" />
                <div v-else class="hdr-logo-placeholder">🏫</div>
                <div class="hdr-info">
                    <div class="hdr-school">{{ school.name }}</div>
                    <div class="hdr-sub">Student Identity Card</div>
                    <div class="hdr-sub" v-if="school.board">{{ school.board }}</div>
                </div>
            </div>

            <!-- ── Body ── -->
            <div class="card-body">

                <!-- Photo column -->
                <div v-if="t.show_photo" class="photo-col">
                    <div class="photo-frame">
                        <img v-if="student.photo_url" :src="student.photo_url" class="photo-img" />
                        <span v-else class="photo-initial">{{ (student.first_name || 'S')[0].toUpperCase() }}</span>
                    </div>
                    <div v-if="t.show_blood && student.blood_group" class="blood-badge">
                        {{ student.blood_group }}
                    </div>
                </div>

                <!-- Info column -->
                <div class="info-col">
                    <div class="student-name">{{ student.name }}</div>
                    <div class="class-row">
                        Class {{ student.class || '—' }}
                        <span v-if="student.section"> &mdash; {{ student.section }}</span>
                    </div>

                    <div class="fields">
                        <div v-if="t.show_roll_no && student.roll_no" class="field-row">
                            <span class="f-label">Roll No</span>
                            <span class="f-value">{{ student.roll_no }}</span>
                        </div>
                        <div v-if="t.show_admission" class="field-row">
                            <span class="f-label">Adm No</span>
                            <span class="f-value">{{ student.admission_no }}</span>
                        </div>
                        <div v-if="t.show_dob && student.dob" class="field-row">
                            <span class="f-label">DOB</span>
                            <span class="f-value">{{ formatDob(student.dob) }}</span>
                        </div>
                        <div v-if="t.show_parent && student.parent_phone" class="field-row">
                            <span class="f-label">Parent</span>
                            <span class="f-value">{{ student.parent_phone }}</span>
                        </div>
                        <div v-if="t.show_address && school.address" class="field-row">
                            <span class="f-label">School</span>
                            <span class="f-value addr">{{ school.address }}</span>
                        </div>
                    </div>
                </div>

                <!-- QR column -->
                <div v-if="t.show_qr && student.uuid" class="qr-col">
                    <IdCardQR :value="qrUrl(student.uuid)" :size="46" />
                    <div class="qr-label">Scan</div>
                </div>

            </div>

            <!-- ── Footer ── -->
            <div class="card-footer" :style="footerBg">
                <span class="footer-phone">{{ school.phone || '' }}</span>
                <span class="footer-year">{{ academicYearLabel }}</span>
            </div>

        </div>
    </div>
</template>

<style>
/* ── Reset for print ── */
* { box-sizing: border-box; }

body {
    margin: 0;
    padding: 0;
    background: #f1f5f9;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ── Toolbar (no-print) ── */
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.toolbar-left  { display: flex; align-items: center; gap: 10px; }
.toolbar-right { display: flex; align-items: center; gap: 10px; }
.toolbar-title { font-weight: 700; font-size: 15px; }
.toolbar-count { font-size: 12px; color: #94a3b8; background: #334155; padding: 2px 8px; border-radius: 12px; }

.btn-print {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
}
.btn-print:hover { background: #1d4ed8; }

.btn-back {
    color: #94a3b8;
    font-size: 13px;
    text-decoration: none;
    padding: 7px 12px;
    border-radius: 8px;
    transition: color 0.15s;
}
.btn-back:hover { color: #fff; }

/* ── Empty state ── */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    gap: 8px;
    color: #475569;
}
.empty-icon  { font-size: 48px; }
.empty-state h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.empty-state p  { font-size: 14px; margin: 0; }
.btn-back-link  { margin-top: 12px; color: #2563eb; text-decoration: underline; font-size: 14px; }

/* ── Cards page ── */
.cards-page {
    margin-top: 56px; /* toolbar height */
    padding: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: flex-start;
}

/* ── Single ID Card — CR80 proportions: ~3.375" × 2.125" ── */
.id-card {
    width: 320px;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    page-break-inside: avoid;
    break-inside: avoid;
}

/* Header */
.card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    color: #fff;
}
.hdr-logo {
    width: 34px;
    height: 34px;
    object-fit: contain;
    background: rgba(255,255,255,0.15);
    border-radius: 5px;
    padding: 2px;
    flex-shrink: 0;
}
.hdr-logo-placeholder {
    width: 34px; height: 34px;
    background: rgba(255,255,255,0.2);
    border-radius: 5px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.hdr-info { flex: 1; min-width: 0; }
.hdr-school {
    font-size: 10px;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0.2px;
}
.hdr-sub { font-size: 8px; opacity: 0.85; line-height: 1.4; }

/* Body */
.card-body {
    display: flex;
    gap: 8px;
    padding: 9px 12px;
    align-items: flex-start;
    flex: 1;
}

/* Photo */
.photo-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
}
.photo-frame {
    width: 52px;
    height: 62px;
    border-radius: 5px;
    border: 1.5px solid #e2e8f0;
    background: #f1f5f9;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.photo-img { width: 100%; height: 100%; object-fit: cover; }
.photo-initial { font-size: 20px; font-weight: 700; color: #94a3b8; font-family: serif; }
.blood-badge {
    font-size: 7.5px;
    font-weight: 700;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 3px;
    padding: 1px 4px;
}

/* Info */
.info-col { flex: 1; min-width: 0; }
.student-name { font-size: 10.5px; font-weight: 700; color: #0f172a; line-height: 1.3; margin-bottom: 1px; }
.class-row { font-size: 8.5px; color: #64748b; font-weight: 500; margin-bottom: 4px; }
.fields { display: flex; flex-direction: column; gap: 1.5px; }
.field-row { display: flex; gap: 4px; align-items: baseline; }
.f-label { font-size: 7.5px; color: #94a3b8; font-weight: 600; width: 34px; flex-shrink: 0; }
.f-value { font-size: 8px; color: #334155; line-height: 1.3; }
.f-value.addr { font-size: 7px; }

/* QR */
.qr-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}
.qr-label { font-size: 7px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

/* Footer */
.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 12px;
    font-size: 7.5px;
    border-top: 1px solid #f1f5f9;
}
.footer-year { font-weight: 700; }

/* ── Print styles ── */
@media print {
    .no-print { display: none !important; }

    body { background: #fff; }

    .cards-page {
        margin-top: 0;
        padding: 0;
        gap: 6mm;
        /* 2 cards per row on A4 */
        display: grid;
        grid-template-columns: repeat(2, 85.6mm);
        justify-content: center;
    }

    .id-card {
        width: 85.6mm;
        box-shadow: none;
        border: 1px solid #d1d5db;
        border-radius: 3mm;
    }

    @page {
        size: A4;
        margin: 10mm;
    }
}
</style>
