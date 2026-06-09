<?php

use App\Models\RegistrationFormConfigSet;
use App\Support\RegistrationFormUiSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reg_form_config_sets', function (Blueprint $table) {
            $table->json('ui_settings')->nullable()->after('published_at');
        });

        RegistrationFormConfigSet::query()->each(function (RegistrationFormConfigSet $set): void {
            $set->forceFill([
                'ui_settings' => RegistrationFormUiSettings::merge($set->ui_settings),
            ])->save();
        });
    }

    public function down(): void
    {
        Schema::table('reg_form_config_sets', function (Blueprint $table) {
            $table->dropColumn('ui_settings');
        });
    }
};
