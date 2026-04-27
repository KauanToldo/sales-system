import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import http from '../api/http'

const toCents = (value) => {
    const normalized = String(value ?? '').trim().replace(',', '.')

    if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
        return null
    }

    const [integerPart, decimalPart = '0'] = normalized.split('.')
    const cents = Number(integerPart) * 100 + Number(decimalPart.padEnd(2, '0').slice(0, 2))
    return Number.isFinite(cents) ? cents : null
}

const centsToMoney = (cents) => {
    const integerPart = Math.floor(cents / 100)
    const decimalPart = String(cents % 100).padStart(2, '0')
    return `${integerPart}.${decimalPart}`
}

const parseMoney = (value) => {
    const cents = toCents(value)
    return cents === null ? 0 : cents / 100
}

const createUiKey = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID()
    }

    return `ui-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

export const useSalesStore = defineStore('sales', () => {
    const currentSale = ref(null)
    const customerId = ref(null)
    const items = ref([])
    const payments = ref([])

    const customers = ref([])
    const products = ref([])
    const paymentMethods = ref([])

    const salesList = ref([])

    const loading = ref(false)
    const loadingList = ref(false)
    const errors = ref(null)
    const itemFieldErrors = ref({})
    const paymentFieldErrors = ref({})

    const isPersisted = computed(() => Boolean(currentSale.value?.id))
    const isFinalized = computed(() => currentSale.value?.status === 'FINALIZED')
    const hasValidationErrors = computed(() => {
        return Object.keys(itemFieldErrors.value).length > 0 || Object.keys(paymentFieldErrors.value).length > 0
    })

    const paymentMethodMap = computed(() => {
        const map = new Map()

        for (const method of paymentMethods.value) {
            map.set(Number(method.id), method)
        }

        return map
    })

    const hasCashMethod = computed(() => {
        return paymentMethods.value.some((method) => String(method.type).toUpperCase() === 'CASH' && Boolean(method.status))
    })

    const totals = computed(() => {
        const total = items.value.reduce((sum, item) => {
            return sum + Number(item.quantity || 0) * Number(item.unit_price || 0)
        }, 0)

        const totalPaid = payments.value.reduce((sum, payment) => {
            return sum + Number(payment.amount || 0)
        }, 0)

        const hasCashPayment = payments.value.some((payment) => {
            const method = paymentMethodMap.value.get(Number(payment.payment_method_id))
            return String(method?.type || '').toUpperCase() === 'CASH'
        })

        const overpaid = totalPaid > total
        const change = overpaid && hasCashPayment ? totalPaid - total : 0
        const missing = totalPaid < total ? total - totalPaid : 0
        const overpayWithoutCash = overpaid && !hasCashPayment

        return {
            total,
            totalPaid,
            change,
            missing,
            hasCashPayment,
            overpayWithoutCash,
            isPaidInFull: total > 0 && totalPaid >= total,
        }
    })

    const getApiErrorMessage = (error, fallback) => {
        return error?.response?.data?.error || fallback
    }

    const setItemError = (productId, message) => {
        itemFieldErrors.value = {
            ...itemFieldErrors.value,
            [String(productId)]: message,
        }
    }

    const clearItemError = (productId) => {
        const key = String(productId)
        if (!Object.prototype.hasOwnProperty.call(itemFieldErrors.value, key)) {
            return
        }

        const next = { ...itemFieldErrors.value }
        delete next[key]
        itemFieldErrors.value = next
    }

    const setPaymentError = (index, message) => {
        paymentFieldErrors.value = {
            ...paymentFieldErrors.value,
            [String(index)]: message,
        }
    }

    const clearPaymentError = (index) => {
        const key = String(index)
        if (!Object.prototype.hasOwnProperty.call(paymentFieldErrors.value, key)) {
            return
        }

        const next = { ...paymentFieldErrors.value }
        delete next[key]
        paymentFieldErrors.value = next
    }

    const normalizeSalePayload = (sale) => {
        currentSale.value = sale
        customerId.value = sale.customer_id ?? null
        items.value = Array.isArray(sale.items)
            ? sale.items.map((item) => ({
                id: item.id ?? null,
                sale_id: item.sale_id ?? null,
                product_id: Number(item.product_id),
                quantity: Number(item.quantity),
                unit_price: Number(item.unit_price),
                uiKey: item.uiKey ?? `item-${item.product_id}`,
            }))
            : []

        payments.value = Array.isArray(sale.payments)
            ? sale.payments.map((payment) => ({
                id: payment.id ?? null,
                sale_id: payment.sale_id ?? null,
                payment_method_id: Number(payment.payment_method_id),
                amount: Number(payment.amount),
                uiKey: payment.uiKey ?? createUiKey(),
            }))
            : []
    }

    const initializeDraft = () => {
        currentSale.value = null
        customerId.value = null
        items.value = []
        payments.value = []
        errors.value = null
        itemFieldErrors.value = {}
        paymentFieldErrors.value = {}
    }

    const ensureEditable = () => {
        if (isFinalized.value) {
            throw new Error('Finalized sales are read-only')
        }
    }

    const ensureNotPersistedForDraftChanges = () => {
        if (isPersisted.value) {
            throw new Error('Items and existing payments can only be edited before saving the sale')
        }
    }

    const createOpenSale = async () => {
        ensureEditable()

        if (items.value.length === 0) {
            throw new Error('Add at least one item before saving the sale')
        }

        if (totals.value.overpayWithoutCash) {
            throw new Error('Overpayment is allowed only when at least one CASH payment method is used')
        }

        if (hasValidationErrors.value) {
            throw new Error('Fix the highlighted fields before saving the sale')
        }

        const payload = {
            customer_id: customerId.value,
            items: items.value.map((item) => ({
                product_id: Number(item.product_id),
                quantity: Number(item.quantity),
            })),
            payments: payments.value.map((payment) => ({
                payment_method_id: Number(payment.payment_method_id),
                amount: Number(payment.amount).toFixed(2),
            })),
        }

        loading.value = true
        errors.value = null

        try {
            const { data } = await http.post('/sales', payload)
            normalizeSalePayload(data)
            return data
        } catch (error) {
            errors.value = getApiErrorMessage(error, 'Unable to create sale')
            throw new Error(errors.value)
        } finally {
            loading.value = false
        }
    }

    const fetchSale = async (id) => {
        loading.value = true
        errors.value = null

        try {
            const { data } = await http.get(`/sales/${id}`)
            normalizeSalePayload(data)
            return data
        } catch (error) {
            errors.value = getApiErrorMessage(error, 'Unable to load sale')
            throw new Error(errors.value)
        } finally {
            loading.value = false
        }
    }

    const fetchSalesList = async () => {
        loadingList.value = true

        try {
            const { data } = await http.get('/sales')
            salesList.value = Array.isArray(data) ? data : []
            return salesList.value
        } catch (error) {
            throw new Error(getApiErrorMessage(error, 'Unable to list sales'))
        } finally {
            loadingList.value = false
        }
    }

    const fetchLookups = async () => {
        loading.value = true

        try {
            const [customersRes, productsRes, methodsRes] = await Promise.all([
                http.get('/customers'),
                http.get('/products'),
                http.get('/payment-methods'),
            ])

            customers.value = Array.isArray(customersRes.data) ? customersRes.data : []
            products.value = Array.isArray(productsRes.data) ? productsRes.data : []
            paymentMethods.value = Array.isArray(methodsRes.data) ? methodsRes.data : []
        } catch (error) {
            throw new Error(getApiErrorMessage(error, 'Unable to load checkout data'))
        } finally {
            loading.value = false
        }
    }

    const addItem = (product, qty = 1) => {
        ensureEditable()
        ensureNotPersistedForDraftChanges()

        const productId = Number(product.id)
        const quantity = Number(qty)

        if (!productId || quantity < 1) {
            throw new Error('Invalid item data')
        }

        const existing = items.value.find((item) => Number(item.product_id) === productId)

        if (existing) {
            existing.quantity += quantity
            clearItemError(productId)
            return
        }

        items.value.push({
            id: null,
            sale_id: null,
            product_id: productId,
            quantity,
            unit_price: Number(product.price),
        })

        clearItemError(productId)
    }

    const updateItemQty = (productId, qty) => {
        ensureEditable()
        ensureNotPersistedForDraftChanges()

        const item = items.value.find((entry) => Number(entry.product_id) === Number(productId))
        if (!item) return

        const normalized = String(qty ?? '').trim()

        if (normalized === '') {
            setItemError(productId, 'Quantity is required')
            return
        }

        if (!/^\d+$/.test(normalized)) {
            setItemError(productId, 'Quantity must be an integer value')
            return
        }

        const quantity = Number(normalized)

        if (quantity <= 0) {
            setItemError(productId, 'Quantity must be greater than zero')
            return
        }

        item.quantity = quantity
        clearItemError(productId)
    }

    const removeItem = (productId) => {
        ensureEditable()
        ensureNotPersistedForDraftChanges()

        items.value = items.value.filter((entry) => Number(entry.product_id) !== Number(productId))
        clearItemError(productId)
    }

    const addPayment = async (payment) => {
        ensureEditable()

        const methodId = Number(payment.payment_method_id)
        const amountValue = payment.amount
        const amountCents = toCents(amountValue)

        if (!methodId || amountCents === null || amountCents <= 0) {
            throw new Error('Provide a valid payment method and amount')
        }

        if (!isPersisted.value) {
            payments.value.push({
                id: null,
                sale_id: null,
                payment_method_id: methodId,
                amount: parseMoney(amountValue),
                uiKey: createUiKey(),
            })

            if (totals.value.overpayWithoutCash) {
                payments.value.pop()
                throw new Error('Overpayment is allowed only when at least one CASH payment method is used')
            }

            return null
        }

        const method = paymentMethodMap.value.get(methodId)
        const isCash = String(method?.type || '').toUpperCase() === 'CASH'
        const projectedTotalPaid = totals.value.totalPaid + amountCents / 100

        if (projectedTotalPaid > totals.value.total && !isCash && !totals.value.hasCashPayment) {
            throw new Error('Overpayment is allowed only when at least one CASH payment method is used')
        }

        try {
            const { data } = await http.post(`/sales/${currentSale.value.id}/payments`, {
                payment_method_id: methodId,
                amount: centsToMoney(amountCents),
            })

            normalizeSalePayload(data)
            return data
        } catch (error) {
            throw new Error(getApiErrorMessage(error, 'Unable to add payment'))
        }
    }

    const updatePayment = (index, payment) => {
        ensureEditable()
        ensureNotPersistedForDraftChanges()

        if (index < 0 || index >= payments.value.length) {
            return
        }

        const methodId = Number(payment.payment_method_id)
        const rawAmount = String(payment.amount ?? '').trim()
        const amountCents = toCents(rawAmount)

        if (!methodId) {
            setPaymentError(index, 'Select a payment method')
            return
        }

        if (rawAmount === '') {
            setPaymentError(index, 'Amount is required')
            return
        }

        if (amountCents === null) {
            setPaymentError(index, 'Enter a valid amount (e.g. 10.00)')
            return
        }

        if (amountCents <= 0) {
            setPaymentError(index, 'Amount must be greater than zero')
            return
        }

        payments.value[index] = {
            ...payments.value[index],
            payment_method_id: methodId,
            amount: amountCents / 100,
        }

        clearPaymentError(index)

        if (totals.value.overpayWithoutCash) {
            setPaymentError(index, 'Overpayment is allowed only when at least one CASH payment method is used')
            return
        }
    }

    const removePayment = (index) => {
        ensureEditable()
        ensureNotPersistedForDraftChanges()

        if (index < 0 || index >= payments.value.length) {
            return
        }

        payments.value.splice(index, 1)

        const nextErrors = {}
        for (const [key, value] of Object.entries(paymentFieldErrors.value)) {
            const currentIndex = Number(key)

            if (currentIndex < index) {
                nextErrors[String(currentIndex)] = value
            } else if (currentIndex > index) {
                nextErrors[String(currentIndex - 1)] = value
            }
        }
        paymentFieldErrors.value = nextErrors
    }

    const finalizeSale = async () => {
        ensureEditable()

        if (hasValidationErrors.value) {
            throw new Error('Fix the highlighted fields before finalizing the sale')
        }

        if (!totals.value.isPaidInFull) {
            throw new Error('Total paid must be greater than or equal to total to finalize')
        }

        if (totals.value.overpayWithoutCash) {
            throw new Error('Overpayment is allowed only when at least one CASH payment method is used')
        }

        loading.value = true

        try {
            if (!isPersisted.value) {
                await createOpenSale()
            }

            const { data } = await http.post(`/sales/${currentSale.value.id}/finalize`)
            normalizeSalePayload(data)
            return data
        } catch (error) {
            throw new Error(getApiErrorMessage(error, 'Unable to finalize sale'))
        } finally {
            loading.value = false
        }
    }

    const downloadSalePdf = async (saleId) => {
        const response = await http.get(`/sales/${saleId}/pdf`, {
            responseType: 'blob',
        })

        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        const fallbackName = `sale-${saleId}.pdf`
        const disposition = String(response.headers?.['content-disposition'] || '')
        const matched = disposition.match(/filename="?([^";]+)"?/i)

        link.href = url
        link.download = matched?.[1] || fallbackName
        link.target = '_self'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        setTimeout(() => URL.revokeObjectURL(url), 2000)
    }

    return {
        currentSale,
        customerId,
        items,
        payments,
        customers,
        products,
        paymentMethods,
        salesList,
        loading,
        loadingList,
        errors,
        itemFieldErrors,
        paymentFieldErrors,
        hasValidationErrors,
        isPersisted,
        isFinalized,
        hasCashMethod,
        totals,
        initializeDraft,
        createOpenSale,
        fetchSale,
        fetchSalesList,
        fetchLookups,
        addItem,
        updateItemQty,
        removeItem,
        addPayment,
        updatePayment,
        removePayment,
        finalizeSale,
        downloadSalePdf,
    }
})
