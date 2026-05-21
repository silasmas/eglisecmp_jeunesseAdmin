<?php

namespace Database\Factories;

use App\Models\ChurchEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChurchEvent>
 */
class ChurchEventFactory extends Factory
{
    protected $model = ChurchEvent::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('-30 days', '+30 days');
        $endAt = (clone $startAt)->modify('+3 days');
        $accessAuthMode = fake()->randomElement(['password', 'otp']);

        return [
            'name' => 'Evenement '.fake()->unique()->numerify('###'),
            'type' => fake()->randomElement(['retraite', 'culte', 'conference']),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location' => fake()->city(),
            'capacity' => fake()->numberBetween(50, 2500),
            'price_to_pay' => fake()->randomFloat(2, 0, 200),
            'currency' => fake()->randomElement(['USD', 'CDF']),
            'access_auth_mode' => $accessAuthMode,
            'access_otp_channel' => $accessAuthMode === 'otp' ? fake()->randomElement(['sms', 'email']) : null,
            'is_active' => fake()->boolean(90),
        ];
    }
}
