import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import HomeView from '../views/HomeView.vue'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { requiresAuth: false },
    },
    {
        path: '/register',
        name: 'register',
        component: RegisterView,
        meta: { requiresAuth: false },
    },
    {
        path: '/home',
        name: 'home',
        component: HomeView,
        meta: { requiresAuth: true },
    },
    {
        path: '/',
        redirect: (to) => {
            const authStore = useAuthStore()
            if (authStore.isAuthenticated()) {
                return '/home'
            }
            return '/login'
        },
    },
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
})

// Navigation Guard
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()
    const isAuthenticated = authStore.isAuthenticated()
    const requiresAuth = to.meta.requiresAuth

    // Se requer autenticação e não está autenticado
    if (requiresAuth && !isAuthenticated) {
        next({ name: 'login' })
        return
    }

    // Se está autenticado e tenta acessar login/register
    if (isAuthenticated && (to.name === 'login' || to.name === 'register')) {
        next({ name: 'home' })
        return
    }

    next()
})

export default router
