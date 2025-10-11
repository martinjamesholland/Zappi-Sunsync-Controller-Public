<?php

namespace Database\Factories;

use App\Models\EnergyFlowLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnergyFlowLogFactory extends Factory
{
    protected $model = EnergyFlowLog::class;

    public function definition()
    {
        return [
            'pv1_power' => $this->faker->numberBetween(0, 5000),
            'pv2_power' => $this->faker->numberBetween(0, 5000),
            'total_pv_power' => $this->faker->numberBetween(0, 10000),
            'grid_power' => $this->faker->numberBetween(-5000, 5000),
            'grid_power_sunsync' => $this->faker->numberBetween(-5000, 5000),
            'battery_power' => $this->faker->numberBetween(-3000, 3000),
            'battery_soc' => $this->faker->numberBetween(0, 100),
            'ups_load_power' => $this->faker->numberBetween(0, 2000),
            'smart_load_power' => $this->faker->numberBetween(0, 3000),
            'home_load_power' => $this->faker->numberBetween(0, 5000),
            'total_load_power' => $this->faker->numberBetween(0, 10000),
            'sunsync_updated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'zappi_updated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'home_load_sunsync' => $this->faker->numberBetween(0, 5000),
            'combined_load_node_sunsync' => $this->faker->numberBetween(0, 8000),
            'combined_load_node' => $this->faker->numberBetween(0, 10000),
            'zappi_node' => $this->faker->numberBetween(0, 7000),
            'car_node_connection' => $this->faker->randomElement(['A', 'B1', 'B2', 'C1', 'C2', 'F']),
            'car_node_Mode' => $this->faker->numberBetween(1, 4),
            'car_node_sta' => $this->faker->numberBetween(1, 5),
            'last_consumption' => $this->faker->randomFloat(2, 0, 50),
            'created_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }
} 