/**
 * Liquidations Controller Entry Point
 * Blueprint: Funding Rate Controller Entry Point
 * 
 * Loads modular controller and initializes Alpine.js component
 * Note: Chart.js readiness is checked inside the controller, not here
 */

import { createLiquidationsController } from './liquidations/controller-coinglass.js';

// Register Alpine.js component immediately (no await)
window.liquidationsController = createLiquidationsController;

console.log('✅ Liquidations controller loaded and ready');
