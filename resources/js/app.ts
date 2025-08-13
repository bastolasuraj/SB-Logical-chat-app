import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './bootstrap'
import router from './router'

// Import the main App component
import App from './App.vue'

// Create Pinia instance
const pinia = createPinia()

// Create and mount the Vue application
const app = createApp(App)

app.use(pinia)
app.use(router)

app.mount('#app')