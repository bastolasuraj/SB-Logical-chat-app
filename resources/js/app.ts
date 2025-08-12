import { configureEcho } from '@laravel/echo-vue';
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
});

configureEcho({
    broadcaster: 'reverb',
});
import { createApp } from 'vue'
import './bootstrap'

// Import the main App component (we'll create this next)
import App from './App.vue'

// Create and mount the Vue application
const app = createApp(App)

app.mount('#app')