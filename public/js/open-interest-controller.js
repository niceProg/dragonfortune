/**
 * Open Interest Controller - Entry Point
 * Coinglass-only version with date-range queries
 */

import { createOpenInterestController } from './open-interest/controller-coinglass.js';

// Expose to global scope for Alpine.js
window.openInterestController = createOpenInterestController;

