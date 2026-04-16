<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { supportedLocales, setLocale } from '@/plugins/i18n.js';

const { locale } = useI18n();
const open = ref(false);

const current = () => supportedLocales.find(l => l.code === locale.value) ?? supportedLocales[0];

const change = (code) => {
    setLocale(code);
    open.value = false;
};
</script>

<template>
    <div style="position:relative;display:inline-block;">
        <button
            @click="open = !open"
            style="display:flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:.85rem;color:#475569;"
            :aria-expanded="open">
            <span>{{ current().flag }}</span>
            <span>{{ current().name }}</span>
            <span style="font-size:.7rem;color:#94a3b8;">▾</span>
        </button>

        <div v-if="open"
             style="position:absolute;top:100%;right:0;margin-top:4px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.08);z-index:500;min-width:140px;overflow:hidden;">
            <button
                v-for="l in supportedLocales"
                :key="l.code"
                @click="change(l.code)"
                :style="{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    width: '100%',
                    padding: '10px 16px',
                    background: l.code === locale ? '#f0f9ff' : '#fff',
                    border: 'none',
                    cursor: 'pointer',
                    fontSize: '.875rem',
                    color: l.code === locale ? '#3b82f6' : '#475569',
                    fontWeight: l.code === locale ? '600' : '400',
                    textAlign: 'left',
                }">
                <span>{{ l.flag }}</span>
                <span>{{ l.name }}</span>
                <span v-if="l.code === locale" style="margin-left:auto;color:#3b82f6;font-size:.8rem;">✓</span>
            </button>
        </div>

        <!-- Backdrop to close -->
        <div v-if="open" @click="open = false" style="position:fixed;inset:0;z-index:499;"></div>
    </div>
</template>
