/**
 * Liquidations Aggregated Controller Entry Point
 * Blueprint: Open Interest Controller Entry Point
 */

import { createLiquidationsAggregatedController } from './liquidations-aggregated/controller-coinglass.js';

// Register Alpine.js component immediately (no await)
window.liquidationsAggregatedController = createLiquidationsAggregatedController;

console.log('✅ Liquidations Aggregated controller loaded and ready');
