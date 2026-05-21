<?php

namespace Database\Seeders;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatNotification;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPolicy;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $guard = 'web';
        $resourcePermissions = [
            'ChurchEvent',
            'RetreatParticipant',
            'RetreatPayment',
            'RetreatNotification',
            'RetreatChambre',
            'RetreatParticipantMovement',
            'RetreatRetreatDetail',
            'RetreatPolicy',
            'RetreatPolicyAcknowledgement',
            'RetreatSession',
            'RetreatAtelier',
            'RetreatActivityPlan',
            'RetreatActivityAttendance',
            'User',
        ];
        $policyMethods = [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'Restore',
            'ForceDelete',
            'ForceDeleteAny',
            'RestoreAny',
            'Replicate',
            'Reorder',
        ];

        foreach ($resourcePermissions as $resourceName) {
            foreach ($policyMethods as $method) {
                Permission::query()->firstOrCreate([
                    'name' => "{$method}:{$resourceName}",
                    'guard_name' => $guard,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(
            ['name' => config('filament-shield.super_admin.name', 'super_admin'), 'guard_name' => $guard],
        );

        Role::firstOrCreate(
            ['name' => config('filament-shield.panel_user.name', 'panel_user'), 'guard_name' => $guard],
        );

        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'policies_and_permissions',
            '--no-interaction' => true,
        ]);

        $superAdmin = Role::findByName(config('filament-shield.super_admin.name', 'super_admin'), $guard);
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            User::factory()->make([
                'name' => 'Administrateur',
                'email' => 'admin@example.com',
                'is_active' => true,
            ])->toArray(),
        );

        $admin->assignRole($superAdmin);

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make([
                'name' => 'Utilisateur test',
                'email' => 'test@example.com',
                'is_active' => true,
            ])->toArray(),
        );

        User::factory()->count(12)->create();

        $events = ChurchEvent::factory()->count(6)->create();
        $users = User::query()->pluck('id')->all();

        $chambres = collect([
            ['nom' => 'A', 'sexe' => 'homme', 'capacite' => 8],
            ['nom' => 'B', 'sexe' => 'homme', 'capacite' => 10],
            ['nom' => 'C', 'sexe' => 'femme', 'capacite' => 8],
            ['nom' => 'D', 'sexe' => 'femme', 'capacite' => 10],
            ['nom' => 'E', 'sexe' => 'mixte', 'capacite' => 6],
        ])->map(function (array $chambre, int $index) use ($users): RetreatChambre {
            $responsableId = $users[$index % count($users)];
            $payload = RetreatChambre::factory()->make([
                ...$chambre,
                'responsable_user_id' => $responsableId,
                'is_active' => true,
            ])->toArray();

            return RetreatChambre::query()->updateOrCreate(
                [
                    'nom' => $chambre['nom'],
                    'sexe' => $chambre['sexe'],
                    'responsable_user_id' => $responsableId,
                ],
                $payload,
            );
        });

        $ateliers = collect(range(1, 6))->map(function (int $numero) use ($users): RetreatAtelier {
            $responsableId = $users[($numero + 4) % count($users)];
            $payload = RetreatAtelier::factory()->make([
                'numero' => $numero,
                'responsable_user_id' => $responsableId,
                'is_active' => true,
            ])->toArray();

            return RetreatAtelier::query()->updateOrCreate(
                [
                    'numero' => $numero,
                    'responsable_user_id' => $responsableId,
                ],
                $payload,
            );
        });

        $policyTitles = [
            'Respect des horaires de la retraite',
            'Consignes de securite sur le site',
            'Utilisation des telephones pendant les activites',
            'Gestion des sorties et retours',
            'Vie commune dans les chambres',
            'Participation aux ateliers',
        ];

        foreach ($policyTitles as $index => $title) {
            $event = $events[$index % $events->count()];
            $payload = RetreatPolicy::factory()->make([
                'event_id' => $event->id,
                'title' => $title,
                'created_by' => $users[$index % count($users)],
                'is_active' => true,
            ])->toArray();

            RetreatPolicy::query()->updateOrCreate(
                [
                    'title' => $title,
                ],
                $payload,
            );
        }

        for ($i = 1; $i <= 40; $i++) {
            $nom = "Participant{$i}";
            $prenom = "Demo{$i}";

            $participantPayload = RetreatParticipant::factory()->make([
                'nom' => $nom,
                'prenom' => $prenom,
                'owner_id' => fake()->randomElement($users),
                'user_id' => fake()->randomElement($users),
            ])->toArray();

            RetreatParticipant::query()->firstOrCreate(
                ['nom' => $nom, 'prenom' => $prenom],
                $participantPayload,
            );
        }

        $participants = RetreatParticipant::query()->inRandomOrder()->get();

        foreach ($participants as $participant) {
            $availableChambres = $chambres->filter(
                fn (RetreatChambre $chambre): bool => in_array($chambre->sexe, [$participant->sexe, 'mixte'], true),
            );

            $participant->update([
                'chambre_id' => $participant->chambre_id ?? ($availableChambres->isNotEmpty() ? $availableChambres->random()->id : $chambres->random()->id),
                'atelier_id' => $participant->atelier_id ?? $ateliers->random()->id,
            ]);
        }

        foreach ($participants as $participant) {
            $event = $events->random();
            $paymentPayload = RetreatPayment::factory()->make([
                'participant_id' => $participant->id,
                'event_id' => $event->id,
                'access_granted_by' => fake()->randomElement($users),
            ])->toArray();

            RetreatPayment::query()->updateOrCreate(
                [
                    'participant_id' => $participant->id,
                    'event_id' => $event->id,
                ],
                $paymentPayload,
            );
        }

        foreach (range(1, 80) as $index) {
            $event = $events->random();
            $participant = $participants->random();
            $subjectType = fake()->randomElement([ChurchEvent::class, RetreatParticipant::class, null]);
            $subjectId = match ($subjectType) {
                ChurchEvent::class => $event->id,
                RetreatParticipant::class => $participant->id,
                default => null,
            };

            RetreatNotification::factory()->create([
                'title' => "Notification {$index}",
                'user_id' => fake()->randomElement($users),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
            ]);
        }
    }
}
