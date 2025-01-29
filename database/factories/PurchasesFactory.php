<?php

namespace Database\Factories;

use App\Models\Purchases;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchasesFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */

    protected $model= Purchases::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */ 
    public function definition()
    {
        return [
            //
            'purchase_uid' => $this->faker->uuid,
            'user_id' => null,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'city' => $this->faker->city,
            'country' => $this->faker->country,
            'address' => $this->faker->address,
            'payment_method' => $this->faker->randomElement(['Credit Card', 'PayPal', 'Cash']),
            'quantity' => $this->faker->numberBetween(1, 10),
            'postal_code' => $this->faker->postcode,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'transaction_id' => $this->faker->uuid,
            'is_shipped' => false,
            'status' => $this->faker->randomElement(['PENDING', 'SUCCESS', 'FAILED', 'SENT', 'RECEIVED', 'RETURNED', 'CANCELLED', 'NOT RECEIVED']),

        ];
    }
}
