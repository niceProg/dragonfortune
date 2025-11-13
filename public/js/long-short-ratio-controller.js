/**
 * Long-Short Ratio Controller Entry Point
 * Initializes Alpine.js component for Long-Short Ratio page
 * 
 * Blueprint: Open Interest Controller Entry Point (proven stable)
 */

import { createLongShortRatioController } from './long-short-ratio/controller-coinglass.js';

// Expose to global scope for Alpine.js (SAME AS OPEN INTEREST)
window.longShortRatioController = createLongShortRatioController;
