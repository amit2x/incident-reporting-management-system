<?php

// database/factories/AirportFactory.php

namespace Database\Factories;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;

class AirportFactory extends Factory
{
    protected $model = Airport::class;

    public function definition(): array
    {
        $cities = [
            'Kolkata' => ['code' => 'CCU', 'name' => 'Netaji Subhash Chandra Bose International Airport'],
            'Delhi' => ['code' => 'DEL', 'name' => 'Indira Gandhi International Airport'],
            'Mumbai' => ['code' => 'BOM', 'name' => 'Chhatrapati Shivaji Maharaj International Airport'],
            'Chennai' => ['code' => 'MAA', 'name' => 'Chennai International Airport'],
            'Bangalore' => ['code' => 'BLR', 'name' => 'Kempegowda International Airport'],
            'Hyderabad' => ['code' => 'HYD', 'name' => 'Rajiv Gandhi International Airport'],
            'Ahmedabad' => ['code' => 'AMD', 'name' => 'Sardar Vallabhbhai Patel International Airport'],
            'Goa' => ['code' => 'GOI', 'name' => 'Dabolim Airport'],
        ];

        $city = $this->faker->randomElement(array_keys($cities));
        $airport = $cities[$city];

        return [
            'code' => $airport['code'],
            'name' => $airport['name'],
            'icao_code' => 'V' . $this->faker->randomLetter() . $this->faker->randomLetter() . $this->faker->randomLetter(),
            'iata_code' => $airport['code'],
            'description' => $this->faker->sentence(),
            'address' => $this->faker->streetAddress(),
            'city' => $city,
            'state' => $this->faker->state(),
            'country' => 'India',
            'timezone' => 'Asia/Kolkata',
            'latitude' => $this->faker->latitude(8, 37),
            'longitude' => $this->faker->longitude(68, 97),
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'settings' => [
                'incident_id_prefix' => $airport['code'],
                'default_sla_hours' => 24,
                'allow_anonymous_reporting' => true,
            ],
        ];
    }

    /**
     * Indicate that the airport is inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
