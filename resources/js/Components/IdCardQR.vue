<script setup>
import { ref, onMounted, watch } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    value: { type: String, required: true },
    size:  { type: Number, default: 46 },
});

const canvas = ref(null);

const render = () => {
    if (!canvas.value) return;
    QRCode.toCanvas(canvas.value, props.value, {
        width:          props.size,
        margin:         1,
        color:          { dark: '#0f172a', light: '#ffffff' },
        errorCorrectionLevel: 'M',
    });
};

onMounted(render);
watch(() => props.value, render);
</script>

<template>
    <canvas ref="canvas" :width="size" :height="size" />
</template>
