<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add new columns to the configured permissions and roles tables.
     *
     * Adds nullable string columns `display_name` and `description` and a nullable unique string `key` to the permissions table,
     * and adds nullable string columns `display_name` and `description` and a boolean `is_editable` (default false) to the roles table.
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
     * Reverts the migration by dropping added columns from the permissions and roles tables.
     *
     * Drops `display_name`, `description`, and `key` from the permissions table, and
     * drops `display_name`, `description`, and `is_editable` from the roles table.
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
