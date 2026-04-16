<script setup>
defineProps({ issuance: Object, error: String });
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <!-- Header -->
            <div class="text-center mb-6">
                <div class="text-5xl mb-2">📜</div>
                <h1 class="text-2xl font-bold text-gray-800">Certificate Verification</h1>
                <p class="text-sm text-gray-500 mt-1">Official Document Authentication</p>
            </div>

            <!-- Error / Not Found -->
            <div v-if="error" class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="text-5xl mb-4">❌</div>
                <h2 class="text-xl font-bold text-red-600 mb-2">Verification Failed</h2>
                <p class="text-gray-500 text-sm">{{ error }}</p>
            </div>

            <!-- Valid Certificate -->
            <div v-else-if="issuance" class="bg-white rounded-2xl shadow-xl overflow-hidden">

                <!-- Verified Banner -->
                <div class="p-4 text-center font-bold text-lg bg-emerald-100 text-emerald-800">
                    ✅ Certificate Verified
                </div>

                <div class="p-6 space-y-4">

                    <!-- Student -->
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                        <div class="w-12 h-12 rounded-full bg-emerald-600 flex items-center justify-center text-white text-xl font-bold">
                            {{ issuance.student_name?.charAt(0) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-lg">{{ issuance.student_name }}</p>
                            <p class="text-sm text-gray-500">Admission No: {{ issuance.admission_no }}</p>
                        </div>
                    </div>

                    <!-- Details -->
                    <table class="w-full text-sm">
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 text-gray-500 font-medium w-36">Certificate</td>
                            <td class="py-2.5 font-semibold text-gray-800">{{ issuance.template_name }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 text-gray-500 font-medium">Issued By</td>
                            <td class="py-2.5 text-gray-800">{{ issuance.school_name }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 text-gray-500 font-medium">Issue Date</td>
                            <td class="py-2.5 text-gray-800">{{ issuance.issued_date }}</td>
                        </tr>
                        <tr v-if="issuance.certificate_no" class="border-b border-gray-100">
                            <td class="py-2.5 text-gray-500 font-medium">Certificate No</td>
                            <td class="py-2.5 font-mono text-gray-800">{{ issuance.certificate_no }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 text-gray-500 font-medium">Verified At</td>
                            <td class="py-2.5 text-gray-800">{{ new Date().toLocaleString() }}</td>
                        </tr>
                    </table>

                    <!-- Authenticity badge -->
                    <div class="flex gap-2 flex-wrap">
                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                            ✓ Authentic Document
                        </span>
                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            ✓ School Issued
                        </span>
                    </div>

                    <div class="text-xs text-gray-400 text-center pt-2 border-t border-gray-100 font-mono">
                        Token: {{ issuance.verification_token?.slice(0, 16) }}...
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
