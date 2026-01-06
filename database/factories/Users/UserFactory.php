<?php

namespace Database\Factories\Users;

use App\Models\Users\User;
use App\Models\Users\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Users\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->numerify('##########'),
            'is_active' => true,
            'last_password_change' => now(),
            'last_login' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'password' => static::$password ??= Hash::make('password'),
            // 'remember_token' => Str::random(10), // Optional: Uncomment if you use "remember me" functionality on login
        ];
    }

    /**
     * Configure the model factory.
     *
     * This adds the UserSetting automatically whenever a User is created.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->setting()->create(UserSetting::defaults());
        });
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
