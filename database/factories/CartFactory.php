<?php

namespace Database\Factories;
use App\Models\Cart;

use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */

     protected $model= Cart::class; 

    public function definition()
    {
        return [
            //
            'user_id' => null,
            'quantity' => $this->faker->numberBetween(0, 100),
            'amount' => $this->faker->numberBetween(100, 10000),
            'status' => $this->faker->randomElement(['PENDING', 'PURCHASED']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),


        ];
    }
}
