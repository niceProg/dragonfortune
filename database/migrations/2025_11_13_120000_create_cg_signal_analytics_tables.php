<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cg_signal_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('type', 50); // 'history' or 'backtest'
            $table->json('data'); // Store analytics data as JSON
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('parameters')->nullable(); // Store command parameters
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->index(['symbol', 'type', 'generated_at']);
            $table->index(['type', 'generated_at']);
        });

        Schema::create('cg_signal_analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('cache_key', 100);
            $table->json('data');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['symbol', 'cache_key']);
            $table->index(['symbol', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cg_signal_analytics_cache');
        Schema::dropIfExists('cg_signal_analytics');
    }
};