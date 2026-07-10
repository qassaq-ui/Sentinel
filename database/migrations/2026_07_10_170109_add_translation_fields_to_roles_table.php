<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid()->nullable()->unique()->after('id');
            $table->string('label_key')->nullable()->unique()->after('guard_name');
            $table->string('fallback_label')->nullable()->after('label_key');
            $table->boolean('is_protected')->default(false)->after('fallback_label');
            $table->index('is_protected');
        });

        DB::table('roles')
            ->orderBy('id')
            ->get()
            ->each(function (object $role): void {
                $uuid = (string) Str::uuid();
                $fallbackLabel = $role->name === 'admin' ? 'Administrator' : Str::headline($role->name);

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update([
                        'uuid' => $uuid,
                        'label_key' => "roles.{$uuid}.label",
                        'fallback_label' => $fallbackLabel,
                        'is_protected' => $role->name === 'admin',
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['is_protected']);
            $table->dropColumn([
                'uuid',
                'label_key',
                'fallback_label',
                'is_protected',
            ]);
        });
    }
};
