/**
 * Options Metrics Controller - Entry Point
 * Blueprint duplicated from Open Interest structure
 */

import { createOpenInterestController } from './options-metrics/controller-coinglass.js';

// Expose to global scope for Alpine.js
window.optionsMetricsController = createOpenInterestController;

