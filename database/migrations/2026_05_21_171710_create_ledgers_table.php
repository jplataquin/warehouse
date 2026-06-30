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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['IN', 'OUT']);
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
                'DISPOSE', 'LOST', 'RETURN', 'MAINTENANCE', 'CORRECTION',
            ]);
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->enum('status', ['PENDING', 'APPROVED'])->default('PENDING');
            $table->string('po_number')->nullable();
            $table->string('offical_receipt')->nullable();
            $table->string('delivery_receipt')->nullable();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('allocation_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('assigned_to')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
