<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('products')->where('quantity', '<', 0)->update(['quantity' => 0]);
        DB::table('product_variations')->where('quantity', '<', 0)->update(['quantity' => 0]);

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_quantity_not_negative CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE product_variations ADD CONSTRAINT product_variations_quantity_not_negative CHECK (quantity >= 0)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE products DROP CHECK products_quantity_not_negative');
        DB::statement('ALTER TABLE product_variations DROP CHECK product_variations_quantity_not_negative');
    }
};
