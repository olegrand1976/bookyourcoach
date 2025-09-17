interface ToastOptions {
    type?: 'success' | 'error' | 'warning' | 'info'
    title?: string
    duration?: number
}

export const useToast = () => {
    const showToast = (message: string, options: ToastOptions = {}) => {
        // Implémentation avec notification native du navigateur
        const type = options.type || 'info'
        const title = options.title || type.charAt(0).toUpperCase() + type.slice(1)
        
        // Log pour le développement
        console.log(`[${type}] ${title}: ${message}`)
        
        // Notification native du navigateur si disponible
        if (typeof window !== 'undefined' && 'Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body: message,
                    icon: '/favicon.ico'
                })
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification(title, {
                            body: message,
                            icon: '/favicon.ico'
                        })
                    }
                })
            }
        }
        
        // Fallback avec alert pour les erreurs importantes
        if (type === 'error') {
            alert(`❌ ${title}: ${message}`)
        }
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
