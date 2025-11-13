/**
 * OnChain Error Handler
 * Provides comprehensive error handling and user-friendly messages
 */

window.OnChainErrorHandler = {
    // Error types
    ERROR_TYPES: {
        NETWORK: 'network',
        API: 'api',
        PARSING: 'parsing',
        CHART: 'chart',
        VALIDATION: 'validation'
    },

    // Retry configuration
    retryConfig: {
        maxRetries: 3,
        baseDelay: 1000,
        maxDelay: 10000
    },

    // Error statistics
    errorStats: {
        total: 0,
        byType: {},
        recent: []
    },

    // Handle different types of errors
    handleError(error, context = {}) {
        const errorInfo = this.analyzeError(error, context);
        this.logError(errorInfo);
        this.updateStats(errorInfo);

        return {
            type: errorInfo.type,
            message: errorInfo.userMessage,
            canRetry: errorInfo.canRetry,
            suggestedAction: errorInfo.suggestedAction,
            originalError: error
        };
    },

    // Analyze error to determine type and appropriate response
    analyzeError(error, context) {
        let type = this.ERROR_TYPES.NETWORK;
        let userMessage = 'An unexpected error occurred';
        let canRetry = true;
        let suggestedAction = 'Please try again';

        // Network errors
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            type = this.ERROR_TYPES.NETWORK;
            userMessage = 'Network connection error';
            suggestedAction = 'Check your internet connection and try again';
        }
        // API errors
        else if (error.message.includes('HTTP')) {
            type = this.ERROR_TYPES.API;
            const statusMatch = error.message.match(/HTTP (\d+)/);
            const status = statusMatch ? parseInt(statusMatch[1]) : 0;

            if (status >= 400 && status < 500) {
                userMessage = 'Invalid request or data not found';
                canRetry = status === 429; // Only retry for rate limiting
                suggestedAction = status === 429
                    ? 'Rate limit exceeded. Please wait a moment and try again'
                    : 'Please check your filters and try again';
            } else if (status >= 500) {
                userMessage = 'Server error occurred';
                suggestedAction = 'Server is experiencing issues. Please try again later';
            }
        }
        // Parsing errors
        else if (error.name === 'SyntaxError' || error.message.includes('JSON')) {
            type = this.ERROR_TYPES.PARSING;
            userMessage = 'Data format error';
            canRetry = false;
            suggestedAction = 'Please refresh the page or contact support';
        }
        // Chart errors
        else if (error.message.includes('Chart') || context.component === 'chart') {
            type = this.ERROR_TYPES.CHART;
            userMessage = 'Chart rendering error';
            suggestedAction = 'Please refresh the page or try a different time window';
        }
        // Validation errors
        else if (context.type === 'validation') {
            type = this.ERROR_TYPES.VALIDATION;
            userMessage = 'Invalid data or parameters';
            canRetry = false;
            suggestedAction = 'Please check your input and try again';
        }

        return {
            type,
            userMessage,
            canRetry,
            suggestedAction,
            context,
            timestamp: new Date().toISOString()
        };
    },

    // Log error with appropriate level
    logError(errorInfo) {
        const logLevel = this.getLogLevel(errorInfo.type);
        const logMessage = `[${errorInfo.type.toUpperCase()}] ${errorInfo.userMessage}`;

        switch (logLevel) {
            case 'error':
                console.error(logMessage, errorInfo);
                break;
            case 'warn':
                console.warn(logMessage, errorInfo);
                break;
            default:
                console.log(logMessage, errorInfo);
        }
    },

    // Get appropriate log level for error type
    getLogLevel(errorType) {
        switch (errorType) {
            case this.ERROR_TYPES.NETWORK:
            case this.ERROR_TYPES.API:
                return 'error';
            case this.ERROR_TYPES.CHART:
            case this.ERROR_TYPES.PARSING:
                return 'warn';
            default:
                return 'log';
        }
    },

    // Update error statistics
    updateStats(errorInfo) {
        this.errorStats.total++;

        if (!this.errorStats.byType[errorInfo.type]) {
            this.errorStats.byType[errorInfo.type] = 0;
        }
        this.errorStats.byType[errorInfo.type]++;

        // Keep recent errors (last 10)
        this.errorStats.recent.unshift(errorInfo);
        if (this.errorStats.recent.length > 10) {
            this.errorStats.recent.pop();
        }
    },

    // Retry with exponential backoff
    async retryWithBackoff(operation, context = {}) {
        let lastError;

        for (let attempt = 0; attempt <= this.retryConfig.maxRetries; attempt++) {
            try {
                return await operation();
            } catch (error) {
                lastError = error;
                const errorInfo = this.handleError(error, { ...context, attempt });

                // Don't retry if error is not retryable
                if (!errorInfo.canRetry || attempt === this.retryConfig.maxRetries) {
                    throw error;
                }

                // Calculate delay with exponential backoff
                const delay = Math.min(
                    this.retryConfig.baseDelay * Math.pow(2, attempt),
                    this.retryConfig.maxDelay
                );

                console.log(`⏳ Retrying in ${delay}ms (attempt ${attempt + 1}/${this.retryConfig.maxRetries})`);
                await this.sleep(delay);
            }
        }

        throw lastError;
    },

    // Sleep utility
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    // Show user-friendly error notification
    showErrorNotification(errorInfo, containerId = null) {
        const notification = this.createErrorNotification(errorInfo);

        if (containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.appendChild(notification);
                return;
            }
        }

        // Fallback to body
        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    },

    // Create error notification element
    createErrorNotification(errorInfo) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';

        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2 mt-1 flex-shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div class="flex-grow-1">
                    <div class="fw-semibold">${errorInfo.message}</div>
                    <small class="text-muted">${errorInfo.suggestedAction}</small>
                    ${errorInfo.canRetry ? '<button class="btn btn-sm btn-outline-danger mt-2" onclick="location.reload()">Retry</button>' : ''}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        return notification;
    },

    // Get error statistics
    getStats() {
        return { ...this.errorStats };
    },

    // Clear error statistics
    clearStats() {
        this.errorStats = {
            total: 0,
            byType: {},
            recent: []
        };
    },

    // Check if system is healthy
    isSystemHealthy() {
        const recentErrors = this.errorStats.recent.filter(error => {
            const errorTime = new Date(error.timestamp);
            const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);
            return errorTime > fiveMinutesAgo;
        });

        return recentErrors.length < 5; // Less than 5 errors in 5 minutes
    },

    // Initialize error handler
    init() {
        // DISABLED: No more error notifications to prevent "unexpected error" messages
        console.log('🛡️ OnChain Error Handler initialized (notifications disabled)');
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    OnChainErrorHandler.init();
});