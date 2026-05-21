<?php

namespace Database\Factories;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetreatPayment>
 */
class RetreatPaymentFactory extends Factory
{
    protected $model = RetreatPayment::class;

    public function definition(): array
    {
        $amountExpected = fake()->randomFloat(2, 20, 250);
        $paid = fake()->boolean(70);
        $amountPaid = $paid ? $amountExpected : fake()->randomFloat(2, 0, $amountExpected);
        $status = $paid ? 'payee' : fake()->randomElement(['init', 'en_cours', 'echouee', 'annulee']);
        $accessGranted = $paid && fake()->boolean(90);

        return [
            'participant_id' => RetreatParticipant::factory(),
            'event_id' => ChurchEvent::factory(),
            'reference' => 'PAY-'.fake()->unique()->numerify('########'),
            'amount_expected' => $amountExpected,
            'amount_paid' => $amountPaid,
            'currency' => fake()->randomElement(['USD', 'CDF']),
            'channel' => fake()->randomElement(['mobile_money', 'card']),
            'phone' => fake()->optional(0.8)->numerify('+2438########'),
            'provider_reference' => fake()->optional(0.7)->regexify('[A-Z0-9]{12}'),
            'provider_status_code' => fake()->optional(0.7)->randomElement(['00', '01', '05', '12']),
            'provider_message' => fake()->optional(0.8)->sentence(),
            'etat' => $status,
            'access_granted' => $accessGranted,
            'access_granted_at' => $accessGranted ? fake()->dateTimeBetween('-10 days', 'now') : null,
            'access_granted_by' => $accessGranted ? User::query()->inRandomOrder()->value('id') : null,
            'paid_at' => $paid ? fake()->dateTimeBetween('-15 days', 'now') : null,
            'is_active' => fake()->boolean(95),
        ];
    }
}
