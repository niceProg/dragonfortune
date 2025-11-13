/**
 * Funding Rate Controller - Entry Point
 * 
 * Modular implementation with:
 * - Direct API calls to internal API
 * - Smart auto-refresh (5 seconds)
 * - Production-ready error handling
 * - Clean separation of concerns
 * 
 * Architecture:
 * - api-service.js: Data fetching
 * - chart-manager.js: Chart operations
 * - utils.js: Helper functions
 * - controller.js: Main logic
 */

import { createFundingRateController } from './funding-rate/controller.js';

// Create controller function for Alpine.js
function fundingRateController() {
    return createFundingRateController();
}

// Export for Alpine.js
window.fundingRateController = fundingRateController;

console.log('✅ Funding Rate Controller loaded (Modular ES6)');
