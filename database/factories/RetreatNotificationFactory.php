<?php

namespace Database\Factories;

use App\Models\RetreatNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetreatNotification>
 */
class RetreatNotificationFactory extends Factory
{
    protected $model = RetreatNotification::class;

    public function definition(): array
    {
        return [
            'title' => fake()->optional(0.8)->sentence(4),
            'message' => fake()->sentence(12),
            'link' => fake()->optional(0.5)->url(),
            'is_read' => fake()->boolean(40),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'is_active' => fake()->boolean(95),
            'category' => fake()->randomElement(['info', 'success', 'warning', 'payment', 'participant']),
            'subject_type' => null,
            'subject_id' => null,
            'laravel_notification_id' => fake()->optional(0.4)->uuid(),
        ];
    }
}
