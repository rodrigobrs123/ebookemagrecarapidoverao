<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders MODIFY customer_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE orders MODIFY email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE orders MODIFY cpf_cnpj VARCHAR(14) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE orders SET customer_name = '' WHERE customer_name IS NULL");
        DB::statement("UPDATE orders SET email = '' WHERE email IS NULL");
        DB::statement("UPDATE orders SET cpf_cnpj = '' WHERE cpf_cnpj IS NULL");
        DB::statement('ALTER TABLE orders MODIFY customer_name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY cpf_cnpj VARCHAR(14) NOT NULL');
    }
};
