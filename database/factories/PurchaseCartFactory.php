<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseCartFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    
    protected $model= Purchasecart::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
            'purchase_id' => null, 
            'Cart_id' => null,

        ];
    }
}
