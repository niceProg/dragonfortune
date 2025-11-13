/**
 * Basis & Term Structure Controller Entry Point
 * Imports and exposes the modular controller to Alpine.js
 */

import { createBasisController } from './basis/controller.js';

// Expose to Alpine.js
window.basisTermStructureController = createBasisController;

console.log('✅ Basis Term Structure controller loaded');
