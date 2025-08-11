interface ToastOptions {
    type?: 'success' | 'error' | 'warning' | 'info'
    title?: string
    duration?: number
}

export const useToast = () => {
    const showToast = (message: string, options: ToastOptions = {}) => {
        // Implémentation simple pour le moment
        // TODO: Intégrer vue-toastification quand les modules seront installés
        console.log(`[${options.type || 'info'}] ${options.title || ''}: ${message}`)
    }

    const success = (message: string, title?: string) => {
        showToast(message, { type: 'success', title })
    }

    const error = (message: string, title?: string) => {
        showToast(message, { type: 'error', title })
    }

    const warning = (message: string, title?: string) => {
        showToast(message, { type: 'warning', title })
    }

    const info = (message: string, title?: string) => {
        showToast(message, { type: 'info', title })
    }

    return {
        showToast,
        success,
        error,
        warning,
        info
    }
}
