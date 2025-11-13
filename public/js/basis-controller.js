/**
 * Basis & Term Structure Controller Entry Point
 * Initializes Alpine.js component for Basis page
 * 
 * Blueprint: Open Interest Controller Entry Point (proven stable)
 */

import { createBasisController } from './basis/controller-coinglass.js';

// Expose to global scope for Alpine.js (SAME AS OPEN INTEREST)
window.basisController = createBasisController;
