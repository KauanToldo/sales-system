import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../api/http'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const token = ref(localStorage.getItem('token') || null)
    const isLoading = ref(false)
    const error = ref(null)

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
        token.value = null
        user.value = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }

    const checkAuth = async () => {
        if (!token.value) return false

        try {
            const { data } = await http.get('/auth/me')
            user.value = data.user
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
        isLoading,
        error,
        register,
        login,
        logout,
        checkAuth,
        isAuthenticated,
    }
})
