/**
 * Liquidations Stream Controller
 * Real-time WebSocket connection to Coinglass liquidation orders
 * 
 * Features:
 * - WebSocket connection management
 * - Real-time order filtering
 * - Sound alerts for large liquidations
 * - Statistics tracking
 */

function liquidationsStreamController() {
    return {
        // WebSocket connection
        ws: null,
        isConnected: false,
        reconnectAttempts: 0,
        maxReconnectAttempts: 5,
        reconnectDelay: 3000,

        // Orders data
        orders: [],
        maxOrders: 100, // Keep last 100 orders
        ordersCount: 0,

        // Filters
        filters: {
            exchange: '',
            coin: '',
            minUsd: 0,
            side: '' // 1=long, 2=short
        },

        // Statistics
        stats: {
            totalUsd: 0,
            longUsd: 0,
            shortUsd: 0,
            count: 0,
            longCount: 0,
            shortCount: 0,
            maxUsd: 0,
            maxOrder: null
        },

        // Sound settings
        soundEnabled: true,
        largeOrderThreshold: 100000, // $100K

        async init() {
            console.log('🚀 Liquidations Stream initialized');

            // Auto-connect on init
            await this.connect();

            // Start demo data generator if WebSocket fails
            setTimeout(() => {
                if (!this.isConnected) {
                    console.log('⚠️ WebSocket failed, starting demo mode...');
                    this.startDemoMode();
                }
            }, 5000);
        },

        async connect() {
            if (this.isConnected) {
                console.warn('⚠️ Already connected');
                return;
            }

            try {
                console.log('🔌 Connecting to Coinglass WebSocket...');

                // Coinglass WebSocket endpoint with API key
                const apiKey = 'f78a531eb0ef4d06ba9559ec16a6b0c2';
                this.ws = new WebSocket(`wss://ws-api-v4.coinglass.com/ws?apiKey=${apiKey}`);

                this.ws.onopen = () => {
                    console.log('✅ WebSocket connected');
                    this.isConnected = true;
                    this.reconnectAttempts = 0;

                    // Subscribe to liquidationOrders channel
                    this.ws.send(JSON.stringify({
                        method: 'subscribe',
                        channels: ['liquidationOrders']
                    }));

                    console.log('📡 Subscribed to liquidationOrders channel');
                };

                this.ws.onmessage = (event) => {
                    try {
                        const message = JSON.parse(event.data);

                        if (message.channel === 'liquidationOrders' && message.data) {
                            this.handleLiquidationOrders(message.data);
                        }
                    } catch (error) {
                        console.error('❌ Error parsing message:', error);
                    }
                };

                this.ws.onerror = (error) => {
                    console.error('❌ WebSocket error:', error);
                };

                this.ws.onclose = () => {
                    console.log('🔌 WebSocket disconnected');
                    this.isConnected = false;

                    // Auto-reconnect
                    if (this.reconnectAttempts < this.maxReconnectAttempts) {
                        this.reconnectAttempts++;
                        console.log(`🔄 Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                        setTimeout(() => this.connect(), this.reconnectDelay);
                    } else {
                        console.error('❌ Max reconnect attempts reached');
                    }
                };

            } catch (error) {
                console.error('❌ Connection error:', error);
                this.isConnected = false;
            }
        },

        disconnect() {
            if (this.ws) {
                console.log('🔌 Disconnecting...');
                this.ws.close();
                this.ws = null;
                this.isConnected = false;
                this.reconnectAttempts = this.maxReconnectAttempts; // Prevent auto-reconnect
            }
        },

        handleLiquidationOrders(data) {
            // data is array of liquidation orders
            data.forEach(order => {
                // Add unique ID and timestamp
                const enrichedOrder = {
                    ...order,
                    id: `${order.exName}-${order.symbol}-${order.time}-${Math.random()}`,
                    receivedAt: Date.now()
                };

                // Add to orders array (prepend for newest first)
                this.orders.unshift(enrichedOrder);

                // Keep only last N orders
                if (this.orders.length > this.maxOrders) {
                    this.orders = this.orders.slice(0, this.maxOrders);
                }

                this.ordersCount++;

                // Update statistics
                this.updateStats(enrichedOrder);

                // Play sound for large orders
                if (this.soundEnabled && enrichedOrder.volUsd >= this.largeOrderThreshold) {
                    this.playAlert();
                }

                console.log('📊 New liquidation:', enrichedOrder);
            });
        },

        updateStats(order) {
            this.stats.count++;
            this.stats.totalUsd += order.volUsd;

            if (order.side === 1) {
                // Long liquidation
                this.stats.longCount++;
                this.stats.longUsd += order.volUsd;
            } else if (order.side === 2) {
                // Short liquidation
                this.stats.shortCount++;
                this.stats.shortUsd += order.volUsd;
            }

            // Track max order
            if (order.volUsd > this.stats.maxUsd) {
                this.stats.maxUsd = order.volUsd;
                this.stats.maxOrder = order;
            }
        },

        clearOrders() {
            this.orders = [];
            this.ordersCount = 0;
            this.stats = {
                totalUsd: 0,
                longUsd: 0,
                shortUsd: 0,
                count: 0,
                longCount: 0,
                shortCount: 0,
                maxUsd: 0,
                maxOrder: null
            };
            console.log('🗑️ Orders cleared');
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            console.log('🔊 Sound:', this.soundEnabled ? 'ON' : 'OFF');

            // Test sound when enabling
            if (this.soundEnabled) {
                this.playAlert();
            }
        },

        // Demo mode for testing (generates fake data)
        startDemoMode() {
            console.log('🎭 Demo mode activated - generating test liquidations');
            this.isConnected = true; // Fake connection

            const exchanges = ['Binance', 'OKX', 'Bybit', 'Bitget', 'dYdX', 'Kraken'];
            const coins = ['BTC', 'ETH', 'SOL', 'BNB', 'XRP', 'ADA', 'DOGE'];

            // Generate liquidation every 2-5 seconds
            const generateLiquidation = () => {
                const order = {
                    baseAsset: coins[Math.floor(Math.random() * coins.length)],
                    exName: exchanges[Math.floor(Math.random() * exchanges.length)],
                    price: 50000 + Math.random() * 50000,
                    side: Math.random() > 0.5 ? 1 : 2, // 1=long, 2=short
                    symbol: coins[Math.floor(Math.random() * coins.length)] + 'USDT',
                    time: Date.now(),
                    volUsd: Math.random() * 500000 // $0 - $500K
                };

                this.handleLiquidationOrders([order]);

                // Schedule next liquidation
                const delay = 2000 + Math.random() * 3000; // 2-5 seconds
                setTimeout(generateLiquidation, delay);
            };

            // Start generating
            generateLiquidation();
        },

        playAlert() {
            // Simple beep using Web Audio API
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (error) {
                console.warn('⚠️ Audio playback failed:', error);
            }
        },

        // Computed: Filtered orders
        get filteredOrders() {
            return this.orders.filter(order => {
                // Exchange filter
                if (this.filters.exchange && order.exName !== this.filters.exchange) {
                    return false;
                }

                // Coin filter
                if (this.filters.coin && order.baseAsset !== this.filters.coin) {
                    return false;
                }

                // Min USD filter
                if (this.filters.minUsd > 0 && order.volUsd < this.filters.minUsd) {
                    return false;
                }

                // Side filter
                if (this.filters.side && order.side !== parseInt(this.filters.side)) {
                    return false;
                }

                return true;
            });
        },

        // Formatters
        formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        formatPrice(price) {
            return '$' + price.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatCurrency(value) {
            if (value >= 1000000) {
                return '$' + (value / 1000000).toFixed(2) + 'M';
            } else if (value >= 1000) {
                return '$' + (value / 1000).toFixed(1) + 'K';
            } else {
                return '$' + value.toFixed(2);
            }
        }
    };
}

// Register Alpine.js component
window.liquidationsStreamController = liquidationsStreamController;

console.log('✅ Liquidations Stream controller loaded');
