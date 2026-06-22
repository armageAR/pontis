<?php

namespace Database\Factories;

use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workshop>
 */
class WorkshopFactory extends Factory
{
    private static int $numberSequence = 0;

    public function definition(): array
    {
        return [
            'zone_number' => fake()->numberBetween(1, 20),
            'zone_name' => fake()->sentence(3),
            'name' => fake()->unique()->company(),
            'number' => ++static::$numberSequence,
            'work_day' => fake()->randomElement(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']),
            'work_frequency' => fake()->randomElement(['1ro 3ro 5to', '1ro 3ro', '2do 4to', 'Todos', '2do 4to 5to']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'country' => 'Argentina',
            'status' => 'active',
        ];
    }

    public function disabled(): static
    {
        return $this->state(['status' => 'disabled']);
    }
}
