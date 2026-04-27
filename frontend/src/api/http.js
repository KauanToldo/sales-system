import axios from 'axios'

const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'

const http = axios.create({
    baseURL: apiUrl,
    headers: {
        'Content-Type': 'application/json',
    },
})

// Interceptor para adicionar JWT automaticamente
http.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Interceptor para tratar erros 401 (token expirado/inválido)
http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')

            if (typeof window !== 'undefined') {
                window.dispatchEvent(new Event('sales-system:unauthorized'))
            }
        }

        return Promise.reject(error)
    }
)

export default http
