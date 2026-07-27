<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->boolean('is_franchise')->default(false)->after('ice_taxpayer_role');
        });

        DB::table('companies')->update([
            'taxpayer_type' => 'natural_person',
            'rimpe_category' => 'popular_business',
            'accounting_required' => false,
            'withholding_agent' => false,
            'withholding_agent_resolution' => null,
            'special_taxpayer' => false,
            'special_taxpayer_resolution' => null,
            'ice_taxpayer_role' => 'retailer',
        ]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('is_franchise');
        });
    }
};
