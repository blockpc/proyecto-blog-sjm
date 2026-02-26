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
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['permissions'], static function (Blueprint $table) {
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->string('key')->nullable()->unique();
        });

        Schema::table($tableNames['roles'], static function (Blueprint $table) {
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_editable')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['permissions'], static function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description', 'key']);
        });

        Schema::table($tableNames['roles'], static function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description', 'is_editable']);
        });
    }
};
