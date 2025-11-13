<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cg_signal_dataset', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->index();
            $table->string('pair', 30)->nullable()->index();
            $table->string('interval', 10)->default('1h')->index();
            $table->string('run_id', 50)->index();
            $table->timestamp('generated_at')->useCurrent();
            $table->decimal('price_now', 18, 4)->nullable();
            $table->decimal('price_future', 18, 4)->nullable();
            $table->string('label_direction', 16)->nullable()->index();
            $table->decimal('label_magnitude', 10, 4)->nullable();
            $table->string('signal_rule', 16);
            $table->decimal('signal_score', 8, 3)->default(0);
            $table->decimal('signal_confidence', 5, 3)->default(0);
            $table->json('signal_reasons')->nullable();
            $table->json('features_payload');
            $table->boolean('is_missing_data')->default(false);
            $table->timestamps();

            $table->index(['symbol', 'interval', 'generated_at'], 'cg_signal_dataset_symbol_interval_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cg_signal_dataset');
    }
};
