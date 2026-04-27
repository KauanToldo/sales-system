import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import AppShell from '../components/layout/AppShell.vue'
import DashboardView from '../views/DashboardView.vue'
import ProductsView from '../views/ProductsView.vue'
import CustomersView from '../views/CustomersView.vue'
import PaymentsView from '../views/PaymentsView.vue'
import PointOfSaleView from '../views/PointOfSaleView.vue'
import ReportsView from '../views/ReportsView.vue'

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
        component: AppShell,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: { name: 'dashboard' },
            },
            {
                path: 'dashboard',
                name: 'dashboard',
                component: DashboardView,
                meta: { requiresAuth: true, title: 'Dashboard' },
            },
            {
                path: 'products',
                name: 'products',
                component: ProductsView,
                meta: { requiresAuth: true, title: 'Products' },
            },
            {
                path: 'customers',
                name: 'customers',
                component: CustomersView,
                meta: { requiresAuth: true, title: 'Customers' },
            },
            {
                path: 'payments',
                name: 'payments',
                component: PaymentsView,
                meta: { requiresAuth: true, title: 'Payments' },
            },
            {
                path: 'pos/sales/:id?',
                name: 'point-of-sale',
                component: PointOfSaleView,
                meta: { requiresAuth: true, title: 'Point of Sale' },
            },
            {
                path: 'reports',
                name: 'reports',
                component: ReportsView,
                meta: { requiresAuth: true, title: 'Reports' },
            },
        ],
    },
    {
        path: '/',
        redirect: (to) => {
            const authStore = useAuthStore()
            if (authStore.isAuthenticated()) {
                return '/home/dashboard'
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
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)

    // Se requer autenticação e não está autenticado
    if (requiresAuth && !isAuthenticated) {
        next({ name: 'login' })
        return
    }

    // Se está autenticado e tenta acessar login/register
    if (isAuthenticated && (to.name === 'login' || to.name === 'register')) {
        next({ name: 'dashboard' })
        return
    }

    next()
})

export default router
