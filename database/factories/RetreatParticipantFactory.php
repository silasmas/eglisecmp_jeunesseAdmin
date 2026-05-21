<?php

namespace Database\Factories;

use App\Models\RetreatParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RetreatParticipant>
 */
class RetreatParticipantFactory extends Factory
{
    protected $model = RetreatParticipant::class;

    public function definition(): array
    {
        $ownerId = User::query()->inRandomOrder()->value('id');
        $userId = User::query()->inRandomOrder()->value('id');
        $present = fake()->boolean(80);
        $billetEnvoye = fake()->boolean(70);
        $otpStatus = fake()->randomElement(['pending', 'otp_sent', 'otp_verified', 'completed']);

        return [
            'nom' => fake()->unique()->lastName(),
            'prenom' => fake()->unique()->firstName(),
            'age' => fake()->numberBetween(14, 65),
            'preuve_paiement' => fake()->optional(0.6)->regexify('[A-Z0-9]{8}').'.jpg',
            'paiement_valide' => fake()->boolean(70),
            'atelier_id' => null,
            'chambre_id' => null,
            'user_id' => $userId,
            'email' => fake()->optional(0.9)->safeEmail(),
            'sexe' => fake()->randomElement(['homme', 'femme']),
            'telephone' => fake()->optional(0.9)->numerify('+2438########'),
            'qr_code' => fake()->optional(0.7)->uuid(),
            'adresse' => fake()->optional(0.8)->address(),
            'observation' => fake()->optional(0.3)->sentence(),
            'telephone_urgence' => fake()->optional(0.7)->numerify('+2438########'),
            'date_presence' => $present ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'present' => $present,
            'owner_id' => $ownerId,
            'billet_envoye' => $billetEnvoye,
            'date_billet_envoye' => $billetEnvoye ? fake()->dateTimeBetween('-10 days', 'now') : null,
            'billet_pdf' => $billetEnvoye ? fake()->regexify('[A-Z0-9]{10}').'.pdf' : null,
            'download_token' => Str::upper(Str::random(32)),
            'role_participant' => fake()->randomElement(['participant', 'volontaire', 'serviteur']),
            'participant_type' => fake()->randomElement(['internal', 'external']),
            'exit_allowed' => fake()->boolean(30),
            'curfew_time' => fake()->optional(0.3)->time('H:i:s'),
            'guardian_name' => fake()->optional(0.4)->name(),
            'guardian_phone' => fake()->optional(0.4)->numerify('+2438########'),
            'registration_status' => $otpStatus,
            'registration_otp_code' => in_array($otpStatus, ['otp_sent', 'otp_verified', 'completed'], true) ? fake()->numerify('######') : null,
            'registration_otp_sent_at' => in_array($otpStatus, ['otp_sent', 'otp_verified', 'completed'], true) ? fake()->dateTimeBetween('-2 days', 'now') : null,
            'registration_otp_expires_at' => in_array($otpStatus, ['otp_sent', 'otp_verified'], true) ? fake()->dateTimeBetween('now', '+1 day') : null,
            'registration_otp_verified_at' => in_array($otpStatus, ['otp_verified', 'completed'], true) ? fake()->dateTimeBetween('-1 day', 'now') : null,
            'registration_otp_attempts' => fake()->numberBetween(0, 3),
            'photo' => fake()->optional(0.5)->regexify('[a-z0-9]{12}').'.jpg',
            'is_verified' => in_array($otpStatus, ['otp_verified', 'completed'], true),
            'billet_envoye_email' => $billetEnvoye ? fake()->boolean(80) : false,
            'billet_envoye_whatsapp' => $billetEnvoye ? fake()->boolean(60) : false,
            'is_active' => fake()->boolean(95),
        ];
    }
}
