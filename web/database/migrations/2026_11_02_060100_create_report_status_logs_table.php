<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $statuses = ['pending', 'in_progress', 'completed', 'rejected'];

        Schema::create('report_status_logs', function (Blueprint $table) use ($statuses) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            // Nullable so deleting a user keeps the report history instead of erasing it.
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('from_status', $statuses)->nullable();
            $table->enum('to_status', $statuses);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_status_logs');
    }
};
