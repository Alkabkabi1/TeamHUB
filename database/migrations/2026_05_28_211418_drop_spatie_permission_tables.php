<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the unused spatie/laravel-permission tables. Authorization now uses
     * a typed users.role tier plus club-scoped capability roles.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'role_has_permissions',
            'model_has_roles',
            'model_has_permissions',
            'roles',
            'permissions',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally irreversible — the spatie/laravel-permission package has
     * been removed, so its schema cannot be restored here. Forward-fix if the
     * package is ever reintroduced.
     */
    public function down(): void
    {
        //
    }
};
