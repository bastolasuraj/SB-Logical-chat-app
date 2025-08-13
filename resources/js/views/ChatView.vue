<template>
    <div class="min-h-screen bg-gray-100">
        <div class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    Chat Application
                </h1>
                <p class="text-lg text-gray-600 mb-8">
                    Welcome to the main chat interface!
                </p>
                <button
                    @click="logout"
                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                >
                    Logout
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const logout = async () => {
    try {
        await authStore.logout();
        router.push("/login");
    } catch (error) {
        console.error("Logout error:", error);
        // Force logout even if API call fails
        router.push("/login");
    }
};
</script>
