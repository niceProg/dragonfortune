🔄 Complete Signal Analytics Workflow:

Step 1: Generate Signal Snapshots

# First, you need to create signal snapshots

php artisan signal:collect --symbol=BTC --interval=1h --count=1000 --batch

# Or generate for a specific time period

php artisan signal:collect --symbol=BTC --interval=1h --start=2024-10-01 --end=2024-11-01 --batch

Step 2: Label Signal Outcomes

# Label the snapshots with actual price movements

php artisan signal:label --symbol=BTC --start=2024-10-01 --end=2024-11-01 --limit=1000

# You can use different labeling strategies

php artisan signal:label --symbol=BTC --strategies=basic,breakout,momentum --horizon=24h --limit=1000

Step 3: Generate Analytics (Now it will work!)

# Generate signal history analytics

php artisan signal:history --symbol=BTC --overview --days=30 --save

# Generate backtest analytics

php artisan backtest:overview --symbol=BTC --strategies=rule,ai,ensemble --compare --save

# For specific date range

php artisan backtest:overview --symbol=BTC --start=2024-10-01 --end=2024-11-01 --detailed --save

Step 4: View Dashboard

Visit /signal-analytics to see your analytics data!

---

🎯 Quick Start Commands:

For Immediate Testing:

# 1. Collect signals for recent period

php artisan signal:collect --symbol=BTC --interval=1h --days=7 --batch

# 2. Label them (wait for completion)

php artisan signal:label --symbol=BTC --days=7 --limit=500

# 3. Generate analytics

php artisan signal:history --symbol=BTC --overview --save &
php artisan backtest:overview --symbol=BTC --compare --save

🔄 Complete Working Sequence:

Step 1: Try the Fixed Commands

# Test the fixed signal collection command

php artisan signal:collect --symbol=BTC --interval=1h --days=7

# If your snapshots are already labeled, use --force

php artisan signal:label --symbol=BTC --start=2024-10-02 --end=2024-11-01 --limit=1000 --force

# Generate analytics (now should work!)

php artisan backtest:overview --symbol=BTC --start=2024-10-02 --end=2024-11-01 --strategies=rule,ai,ensemble --compare --save

Step 2: Alternative - Collect Fresh Data

If the existing data is problematic, create fresh data:

# Collect fresh signals for recent period

php artisan signal:collect --symbol=BTC --interval=1h --days=30

# Label them

php artisan signal:label --symbol=BTC --days=30 --limit=1000

# Generate analytics

php artisan signal:history --symbol=BTC --overview --save &
php artisan backtest:overview --symbol=BTC --compare --save

Step 3: Check Your Data

# Check what data you have

php artisan signal:history --symbol=BTC --limit=10

# Check labeling status

php artisan debug:price-data --symbol=BTC

🎯 Quick Test:

# Test with fresh recent data first

php artisan signal:collect --symbol=BTC --interval=1h --days=7
php artisan signal:label --symbol=BTC --days=7 --limit=200
php artisan backtest:overview --symbol=BTC --days=7 --save
