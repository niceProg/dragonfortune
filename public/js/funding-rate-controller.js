/**
 * Funding Rate Controller - Entry Point
 * Coinglass-only version with date-range queries
 */

import { createFundingRateController } from './funding-rate/controller-coinglass.js';

// Expose to global scope for Alpine.js
window.fundingRateController = createFundingRateController;