<?php

use App\Models\RegistrationFormConfigSet;
use App\Services\RegistrationFormConfigService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_form_config_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_event_id')
                ->nullable()
                ->unique()
                ->constrained('events_event')
                ->nullOnDelete();
            $table->string('name', 160);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reg_form_field_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reg_form_config_set_id')
                ->constrained('reg_form_config_sets')
                ->cascadeOnDelete();
            $table->string('field_key', 60);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(false);
            $table->string('column_span', 20)->default('default');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['reg_form_config_set_id', 'field_key'], 'reg_form_field_unique');
        });

        $defaultSet = RegistrationFormConfigSet::query()->create([
            'church_event_id' => null,
            'name' => 'Modèle par défaut',
            'is_published' => true,
            'published_at' => now(),
        ]);

        app(RegistrationFormConfigService::class)->seedFieldItems($defaultSet);
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_form_field_items');
        Schema::dropIfExists('reg_form_config_sets');
    }
};
