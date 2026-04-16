<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    quiz: Object,
    questions: Array,
    attempt: Object,
    timeLeft: Number,
});

// ── Timer ─────────────────────────────────────────────────────────
const secondsLeft = ref(props.timeLeft);
let timer = null;

const timerDisplay = computed(() => {
    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;
    return `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
});

const timerClass = computed(() => secondsLeft.value < 120 ? 'timer-danger' : (secondsLeft.value < 300 ? 'timer-warn' : 'timer-ok'));

onMounted(() => {
    timer = setInterval(() => {
        if (secondsLeft.value > 0) {
            secondsLeft.value--;
        } else {
            clearInterval(timer);
            submitQuiz(true);
        }
    }, 1000);

    // Track tab switches
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onBeforeUnmount(() => {
    clearInterval(timer);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});

const tabSwitches = ref(0);
const onVisibilityChange = () => {
    if (document.visibilityState === 'hidden') tabSwitches.value++;
};

// ── Answers ───────────────────────────────────────────────────────
const answers = ref({});
props.questions.forEach(q => { answers.value[q.id] = null; });

const currentQ = ref(0);
const totalQ   = computed(() => props.questions.length);
const answered = computed(() => Object.values(answers.value).filter(v => v !== null && v !== '').length);

// ── Submit ────────────────────────────────────────────────────────
const submitting = ref(false);
const result     = ref(null);

const submitQuiz = async (isAuto = false) => {
    if (submitting.value) return;
    submitting.value = true;
    clearInterval(timer);

    const payload = {
        answers: props.questions.map(q => ({ question_id: q.id, answer: answers.value[q.id] ?? null })),
        tab_switches: tabSwitches.value,
    };

    try {
        const res = await fetch(`/school/quiz/${props.quiz.id}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify(payload),
        });
        result.value = await res.json();
    } catch {
        result.value = { error: 'Submission failed. Please contact your teacher.' };
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <!-- Result screen -->
    <div v-if="result" class="result-screen">
        <div class="result-card">
            <div v-if="result.error">
                <div style="font-size:3rem;margin-bottom:12px;">❌</div>
                <h2>Submission Error</h2>
                <p>{{ result.error }}</p>
            </div>
            <div v-else>
                <div style="font-size:4rem;margin-bottom:12px;">{{ result.passed ? '🎉' : '📖' }}</div>
                <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:8px;">{{ result.passed ? 'Passed!' : 'Not Passed' }}</h2>
                <div class="score-circle">{{ result.percentage }}%</div>
                <p style="color:#64748b;margin-top:8px;">Score: {{ result.score }} / {{ quiz.total_marks }}</p>
                <a href="/school/quiz/my-quizzes" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;">Back to My Quizzes</a>
            </div>
        </div>
    </div>

    <!-- Quiz taking screen -->
    <div v-else class="quiz-container">

        <!-- Header bar -->
        <div class="quiz-header">
            <div style="font-weight:600;font-size:1rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ quiz.title }}</div>
            <div :class="['quiz-timer', timerClass]">⏱ {{ timerDisplay }}</div>
            <div style="font-size:.85rem;color:#64748b;">{{ answered }}/{{ totalQ }} answered</div>
        </div>

        <!-- Question navigation -->
        <div class="quiz-nav">
            <button v-for="(q, i) in questions" :key="q.id"
                @click="currentQ = i"
                :class="['nav-btn', { 'nav-btn-active': i === currentQ, 'nav-btn-answered': answers[q.id] !== null && answers[q.id] !== '' }]">
                {{ i + 1 }}
            </button>
        </div>

        <!-- Current question -->
        <div v-if="questions[currentQ]" class="question-card">
            <div class="question-meta">
                <span>Q{{ currentQ + 1 }} of {{ totalQ }}</span>
                <span style="font-weight:600;color:#3b82f6;">{{ questions[currentQ].marks }} mark{{ questions[currentQ].marks != 1 ? 's' : '' }}</span>
            </div>
            <div class="question-text">{{ questions[currentQ].question_text }}</div>

            <!-- MCQ / True-False -->
            <div v-if="['mcq', 'true_false'].includes(questions[currentQ].type)" class="options-list">
                <label v-for="(opt, oi) in (questions[currentQ].options || [{ text: 'True' }, { text: 'False' }])"
                    :key="oi"
                    :class="['option-label', { 'selected': answers[questions[currentQ].id] == String(oi) }]">
                    <input type="radio" :name="`q${questions[currentQ].id}`"
                        :value="String(oi)"
                        v-model="answers[questions[currentQ].id]"
                        style="display:none;" />
                    <span class="option-letter">{{ String.fromCharCode(65 + oi) }}</span>
                    <span>{{ opt.text ?? opt }}</span>
                </label>
            </div>

            <!-- Short answer / Descriptive -->
            <div v-else>
                <textarea v-model="answers[questions[currentQ].id]"
                    :rows="questions[currentQ].type === 'descriptive' ? 6 : 2"
                    placeholder="Write your answer here..."
                    class="answer-textarea"></textarea>
            </div>

            <!-- Navigation buttons -->
            <div style="display:flex;justify-content:space-between;margin-top:20px;">
                <button @click="currentQ = Math.max(0, currentQ - 1)" :disabled="currentQ === 0" class="nav-arrow">&#9664; Prev</button>
                <button v-if="currentQ < totalQ - 1" @click="currentQ++" class="nav-arrow">Next &#9654;</button>
                <button v-else @click="submitQuiz(false)" :disabled="submitting" class="submit-btn">
                    {{ submitting ? 'Submitting...' : 'Submit Quiz' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.quiz-container { min-height:100vh; background:#f8fafc; }
.quiz-header { position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #e2e8f0;padding:12px 20px;display:flex;align-items:center;gap:16px; }
.quiz-timer { font-size:1.25rem;font-weight:700;font-family:monospace;padding:4px 12px;border-radius:6px; }
.timer-ok   { color:#059669;background:#d1fae5; }
.timer-warn { color:#d97706;background:#fef3c7; }
.timer-danger { color:#dc2626;background:#fee2e2;animation:pulse 1s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.6; } }
.quiz-nav { display:flex;flex-wrap:wrap;gap:6px;padding:14px 20px;background:#fff;border-bottom:1px solid #e2e8f0; }
.nav-btn { width:32px;height:32px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;font-size:.8rem;font-weight:600;background:#fff;color:#374151; }
.nav-btn-active { background:#3b82f6;color:#fff;border-color:#3b82f6; }
.nav-btn-answered { background:#d1fae5;border-color:#6ee7b7;color:#065f46; }
.question-card { max-width:760px;margin:24px auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.06); }
.question-meta { display:flex;justify-content:space-between;font-size:.8rem;color:#94a3b8;margin-bottom:12px; }
.question-text { font-size:1.05rem;font-weight:500;color:#1e293b;line-height:1.6;margin-bottom:20px; }
.options-list { display:flex;flex-direction:column;gap:10px; }
.option-label { display:flex;align-items:center;gap:12px;padding:12px 16px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:.15s; }
.option-label:hover { border-color:#93c5fd; }
.option-label.selected { border-color:#3b82f6;background:#eff6ff; }
.option-letter { width:28px;height:28px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0; }
.option-label.selected .option-letter { background:#3b82f6;color:#fff; }
.answer-textarea { width:100%;border:2px solid #e2e8f0;border-radius:8px;padding:12px;resize:vertical;font-size:.9rem; }
.answer-textarea:focus { outline:none;border-color:#3b82f6; }
.nav-arrow { padding:8px 18px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:.875rem; }
.nav-arrow:disabled { opacity:.4;cursor:not-allowed; }
.submit-btn { padding:10px 24px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem; }
.submit-btn:disabled { opacity:.6;cursor:not-allowed; }
.result-screen { min-height:100vh;background:#f0fdf4;display:flex;align-items:center;justify-content:center; }
.result-card { background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,.07);max-width:400px;width:100%; }
.score-circle { width:100px;height:100px;border-radius:50%;background:#eff6ff;border:4px solid #3b82f6;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;color:#1d4ed8;margin:16px auto; }
</style>
