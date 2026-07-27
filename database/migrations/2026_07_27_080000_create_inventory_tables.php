<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->bigInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index(['product_id', 'quantity']);
        });

        Schema::create('inventory_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('type', 24);
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->string('reference', 60)->nullable();
            $table->string('reason', 255);
            $table->dateTime('occurred_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type', 'occurred_at']);
            $table->index(['source_warehouse_id', 'occurred_at']);
            $table->index(['destination_warehouse_id', 'occurred_at']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_operation_id')->constrained('inventory_operations')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_package_id')->nullable()->constrained('product_packages')->restrictOnDelete();
            $table->bigInteger('quantity_delta');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->unsignedBigInteger('package_quantity')->nullable();
            $table->unsignedInteger('units_per_package')->default(1);
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'id']);
            $table->index(['product_id', 'id']);
        });

        $this->createImmutabilityTriggers();
    }

    public function down(): void
    {
        $this->dropImmutabilityTriggers();
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_operations');
        Schema::dropIfExists('inventory_stocks');
    }

    private function createImmutabilityTriggers(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared("CREATE TRIGGER inventory_operations_no_update BEFORE UPDATE ON inventory_operations FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El kardex es inmutable'");
            DB::unprepared("CREATE TRIGGER inventory_operations_no_delete BEFORE DELETE ON inventory_operations FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El kardex es inmutable'");
            DB::unprepared("CREATE TRIGGER inventory_movements_no_update BEFORE UPDATE ON inventory_movements FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El kardex es inmutable'");
            DB::unprepared("CREATE TRIGGER inventory_movements_no_delete BEFORE DELETE ON inventory_movements FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El kardex es inmutable'");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER inventory_operations_no_update BEFORE UPDATE ON inventory_operations BEGIN SELECT RAISE(ABORT, 'El kardex es inmutable'); END");
            DB::unprepared("CREATE TRIGGER inventory_operations_no_delete BEFORE DELETE ON inventory_operations BEGIN SELECT RAISE(ABORT, 'El kardex es inmutable'); END");
            DB::unprepared("CREATE TRIGGER inventory_movements_no_update BEFORE UPDATE ON inventory_movements BEGIN SELECT RAISE(ABORT, 'El kardex es inmutable'); END");
            DB::unprepared("CREATE TRIGGER inventory_movements_no_delete BEFORE DELETE ON inventory_movements BEGIN SELECT RAISE(ABORT, 'El kardex es inmutable'); END");
        }
    }

    private function dropImmutabilityTriggers(): void
    {
        foreach ([
            'inventory_operations_no_update',
            'inventory_operations_no_delete',
            'inventory_movements_no_update',
            'inventory_movements_no_delete',
        ] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }
    }
};
