<script setup>
import SchoolLayout from '@/Layouts/SchoolLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ sessions: Array });

// ── Create Session ────────────────────────────────────────────────
const showCreate = ref(false);

// TODO: load staff from page props in a real app via controller
const form = useForm({
    title: '', date: '', start_time: '09:00', end_time: '13:00',
    slot_duration_minutes: 15, description: '', staff_ids: [],
});

const submit = () => {
    form.post('/school/ptm', { preserveScroll: true, onSuccess: () => { showCreate.value = false; form.reset(); } });
};

const updateStatus = (id, status) => {
    router.patch(`/school/ptm/${id}/status`, { status }, { preserveScroll: true });
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const statusBadge = { draft: 'badge-gray', open: 'badge-green', closed: 'badge-amber' };
</script>

<template>
    <SchoolLayout title="Parent-Teacher Meetings">
        <div class="page-header">
            <h1 class="page-header-title">PTM Sessions</h1>
            <Button @click="showCreate = true">+ New Session</Button>
        </div>

        <div class="card">
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Slot Duration</th>
                            <th style="text-align:center;">Slots</th>
                            <th style="text-align:center;">Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sessions" :key="s.id">
                            <td>
                                <Link :href="`/school/ptm/${s.id}`" style="font-weight:500;color:#3b82f6;">{{ s.title }}</Link>
                            </td>
                            <td style="white-space:nowrap;">{{ fmt(s.date) }}</td>
                            <td style="white-space:nowrap;">{{ s.start_time?.slice(0,5) }} – {{ s.end_time?.slice(0,5) }}</td>
                            <td>{{ s.slot_duration_minutes }} min</td>
                            <td style="text-align:center;">{{ s.slots_count }}</td>
                            <td style="text-align:center;">{{ s.bookings_count }}</td>
                            <td><span class="badge" :class="statusBadge[s.status]" style="text-transform:capitalize;">{{ s.status }}</span></td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <Button v-if="s.status === 'draft'" variant="success" size="xs" @click="updateStatus(s.id, 'open')">Open</Button>
                                    <Button v-if="s.status === 'open'"  variant="secondary" size="xs" @click="updateStatus(s.id, 'closed')">Close</Button>
                                    <Link :href="`/school/ptm/${s.id}`" class="btn btn-secondary btn-xs">View</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!sessions?.length">
                            <td colspan="8" style="text-align:center;padding:32px;color:#94a3b8;">No PTM sessions yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreate" class="modal-backdrop" @click.self="showCreate = false">
                <div class="modal" style="max-width:500px;width:100%;">
                    <div class="modal-header">
                        <h3 class="modal-title">New PTM Session</h3>
                        <button @click="showCreate = false" class="modal-close">&times;</button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
                            <div class="form-field">
                                <label>Session Title *</label>
                                <input v-model="form.title" required placeholder="e.g. Mid-Term PTM 2026" />
                            </div>
                            <div class="form-field">
                                <label>Date *</label>
                                <input v-model="form.date" type="date" required />
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                                <div class="form-field" style="margin:0;">
                                    <label>Start Time *</label>
                                    <input v-model="form.start_time" type="time" required />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>End Time *</label>
                                    <input v-model="form.end_time" type="time" required />
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <label>Slot (min) *</label>
                                    <input v-model="form.slot_duration_minutes" type="number" min="5" max="60" required />
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Description</label>
                                <textarea v-model="form.description" rows="2"></textarea>
                            </div>
                            <div style="font-size:.8rem;color:#64748b;background:#f1f5f9;padding:8px 12px;border-radius:6px;">
                                Note: After creating, add teacher slots from the session detail page.
                                Staff IDs field is for API use. You can add individual teacher slots post-creation.
                            </div>
                            <input v-model="form.staff_ids" type="hidden" />
                        </div>
                        <div class="modal-footer">
                            <Button variant="secondary" type="button" @click="showCreate = false">Cancel</Button>
                            <Button type="submit" :loading="form.processing">Create Session</Button>
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
