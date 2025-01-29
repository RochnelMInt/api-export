<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            /*
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),            
            */
            'user_uid' => $this->faker->uuid,
            'username' => $this->faker->userName,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'avatar' => 'user.png',
            'question' => $this->faker->sentence,
            'address' => $this->faker->address,
            'answer' => $this->faker->sentence,
            'is_admin' => $this->faker->boolean(10), // 10% chance of being true
            'city' => $this->faker->city,
            'country' => $this->faker->country,
            'about_me' => $this->faker->sentence(),
            'postal_code' => $this->faker->postcode,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'is_activated' => $this->faker->boolean(90), // 90% chance of being true
            'is_super_admin' => $this->faker->boolean(100), // 100% chance of being true
            'status' => $this->faker->randomElement(['PENDING', 'ACCEPTED', 'REFUSED', 'BANNED']),
            'email_verified_at' => now(),
            'provider' => null,
            'provider_id' => null,
            'password' => bcrypt('Pendadoo94#'),
            'temp_password' => null,
            'banned_until' => null,
            'count_bad_request' => $this->faker->numberBetween(0, 100),
            'is_first_connection' => true,
            'login_well_on' => null,
            'comment_privacy' => $this->faker->randomElement(['EVERYBODY', 'MEMBERS', 'ONLYME']),
            'remember_token' => Str::random(10),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),

        ];
    }
}
