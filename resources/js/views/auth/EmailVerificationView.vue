<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2
                    class="mt-6 text-center text-3xl font-extrabold text-gray-900"
                >
                    Verify your email
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    We've sent a verification code to your email address. Please
                    enter it below to verify your account.
                </p>
            </div>

            <!-- Success message -->
            <div v-if="isVerified" class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg
                            class="h-5 w-5 text-green-400"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Email verified successfully! Redirecting to chat...
                        </p>
                    </div>
                </div>
            </div>

            <form
                v-if="!isVerified"
                class="mt-8 space-y-6"
                @submit.prevent="handleSubmit"
            >
                <div>
                    <label
                        for="code"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Verification Code
                    </label>
                    <input
                        id="code"
                        v-model="form.code"
                        name="code"
                        type="text"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                        class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-center text-2xl tracking-widest"
                        :class="{ 'border-red-500': errors.code }"
                        placeholder="000000"
                        @input="formatCode"
                    />
                    <div v-if="errors.code" class="text-red-500 text-sm mt-1">
                        {{ errors.code }}
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Enter the 6-digit code sent to your email
                    </p>
                </div>

                <div
                    v-if="generalError"
                    class="text-red-500 text-sm text-center"
                >
                    {{ generalError }}
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="isLoading || form.code.length !== 6"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span
                            v-if="isLoading"
                            class="absolute left-0 inset-y-0 flex items-center pl-3"
                        >
                            <svg
                                class="animate-spin h-5 w-5 text-indigo-500"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                        </span>
                        {{ isLoading ? "Verifying..." : "Verify Email" }}
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <span class="text-gray-500"
                            >Didn't receive the code?</span
                        >
                    </div>
                    <div class="text-sm">
                        <button
                            type="button"
                            :disabled="isResending || resendCooldown > 0"
                            @click="resendCode"
                            class="font-medium text-indigo-600 hover:text-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{
                                resendCooldown > 0
                                    ? `Resend in ${resendCooldown}s`
                                    : isResending
                                    ? "Sending..."
                                    : "Resend code"
                            }}
                        </button>
                    </div>
                </div>

                <div class="text-center">
                    <router-link
                        to="/login"
                        class="text-sm text-indigo-600 hover:text-indigo-500"
                    >
                        Back to login
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useValidation } from "../../composables/useValidation";
import type { EmailVerificationData } from "../../types/auth";

const router = useRouter();
const authStore = useAuthStore();
const { errors, validate, clearErrors, setServerErrors } = useValidation();

const form = reactive<EmailVerificationData>({
    code: "",
});

const isLoading = ref(false);
const isResending = ref(false);
const isVerified = ref(false);
const generalError = ref("");
const resendCooldown = ref(0);

let cooldownInterval: NodeJS.Timeout | null = null;

const validationRules = {
    code: [
        { required: true, message: "Verification code is required" },
        { minLength: 6, message: "Verification code must be 6 digits" },
    ],
};

const formatCode = (event: Event) => {
    const target = event.target as HTMLInputElement;
    // Only allow numbers
    target.value = target.value.replace(/\D/g, "");
    form.code = target.value;
};

const handleSubmit = async () => {
    clearErrors();
    generalError.value = "";
    authStore.clearError();

    // Client-side validation
    const isValid = validate({ code: form.code }, validationRules);
    if (!isValid) {
        return;
    }

    isLoading.value = true;

    try {
        await authStore.verifyEmail(form.code);

        isVerified.value = true;

        // Redirect after a short delay
        setTimeout(() => {
            router.push("/chat");
        }, 2000);
    } catch (error: any) {
        if (error.status === 422) {
            // Validation errors from server
            setServerErrors(error.errors);
        } else if (error.status === 400) {
            generalError.value = "Invalid or expired verification code";
        } else {
            generalError.value =
                error.message || "An error occurred. Please try again.";
        }
    } finally {
        isLoading.value = false;
    }
};

const resendCode = async () => {
    if (resendCooldown.value > 0) return;

    isResending.value = true;
    clearErrors();
    generalError.value = "";
    authStore.clearError();

    try {
        await authStore.resendVerificationEmail();

        // Start cooldown
        resendCooldown.value = 60;
        cooldownInterval = setInterval(() => {
            resendCooldown.value--;
            if (resendCooldown.value <= 0 && cooldownInterval) {
                clearInterval(cooldownInterval);
                cooldownInterval = null;
            }
        }, 1000);
    } catch (error: any) {
        generalError.value =
            error.message || "Failed to resend code. Please try again.";
    } finally {
        isResending.value = false;
    }
};

onMounted(() => {
    // Auto-focus the code input
    const codeInput = document.getElementById("code");
    if (codeInput) {
        codeInput.focus();
    }
});

onUnmounted(() => {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
});
</script>
