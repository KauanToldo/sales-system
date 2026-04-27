import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../api/http'

let unauthorizedListenerRegistered = false

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const token = ref(localStorage.getItem('token') || null)
    const sessionValidated = ref(false)
    const isLoading = ref(false)
    const error = ref(null)

    const clearSessionState = () => {
        token.value = null
        user.value = null
        sessionValidated.value = false
    }

    if (!unauthorizedListenerRegistered && typeof window !== 'undefined') {
        window.addEventListener('sales-system:unauthorized', clearSessionState)
        unauthorizedListenerRegistered = true
    }

    const register = async (name, email, password, confirmPassword) => {
        isLoading.value = true
        error.value = null

        try {
            const { data } = await http.post('/auth/register', {
                name,
                email,
                password,
                confirm_password: confirmPassword
            })

            token.value = data.token
            user.value = data.user
            sessionValidated.value = true

            localStorage.setItem('token', data.token)
            localStorage.setItem('user', JSON.stringify(data.user))

            return true
        } catch (err) {
            error.value = err.response?.data?.error || 'Erro ao registrar'
            return false
        } finally {
            isLoading.value = false
        }
    }

    const login = async (email, password) => {
        isLoading.value = true
        error.value = null

        try {
            const { data } = await http.post('/auth/login', {
                email,
                password,
            })

            token.value = data.token
            user.value = data.user
            sessionValidated.value = true

            localStorage.setItem('token', data.token)
            localStorage.setItem('user', JSON.stringify(data.user))

            return true
        } catch (err) {
            error.value = err.response?.data?.error || 'Email ou senha inválidos'
            return false
        } finally {
            isLoading.value = false
        }
    }

    const logout = () => {
        clearSessionState()
        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }

    const checkAuth = async () => {
        if (!token.value) return false

        try {
            const { data } = await http.get('/auth/me')
            user.value = data.user
            sessionValidated.value = true
            localStorage.setItem('user', JSON.stringify(data.user))
            return true
        } catch (err) {
            logout()
            return false
        }
    }

    const isAuthenticated = () => !!token.value && !!user.value

    return {
        user,
        token,
        sessionValidated,
        isLoading,
        error,
        register,
        login,
        logout,
        checkAuth,
        isAuthenticated,
    }
})
