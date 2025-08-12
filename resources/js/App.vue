<template>
  <div id="app" class="min-h-screen bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          Chat App
        </h1>
        <p class="text-lg text-gray-600">
          Laravel + Vue.js + TypeScript + Tailwind CSS
        </p>
        <div class="mt-8">
          <button 
            @click="incrementCounter"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
          >
            Clicked {{ counter }} times
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// Reactive counter for testing
const counter = ref(0)

const incrementCounter = () => {
  counter.value++
}
</script>