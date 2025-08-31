<?php

namespace Database\Factories;

use App\Models\Daparto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Daparto>
 */
class DapartoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Daparto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = ['BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Opel'];
        $brand = $this->faker->randomElement($brands);

        return [
            'tiltle' => $this->faker->sentence(3, true),
            'teilemarke_teilenummer' => $brand . $this->faker->numberBetween(100000, 999999),
            'preis' => $this->faker->randomFloat(2, 10, 1000),
            'interne_artikelnummer' => 'INT' . $this->faker->numberBetween(10000000, 99999999),
            'zustand' => $this->faker->numberBetween(1, 5),
            'pfand' => $this->faker->numberBetween(0, 500),
            'versandklasse' => $this->faker->numberBetween(1, 5),
            'lieferzeit' => $this->faker->numberBetween(1, 30),
        ];
    }

    /**
     * Indicate that the daparto is in excellent condition.
     */
    public function excellent(): static
    {
        return $this->state(fn(array $attributes) => [
            'zustand' => 5,
        ]);
    }

    /**
     * Indicate that the daparto is in poor condition.
     */
    public function poor(): static
    {
        return $this->state(fn(array $attributes) => [
            'zustand' => 1,
        ]);
    }

    /**
     * Indicate that the daparto is expensive.
     */
    public function expensive(): static
    {
        return $this->state(fn(array $attributes) => [
            'preis' => $this->faker->randomFloat(2, 500, 2000),
        ]);
    }

    /**
     * Indicate that the daparto is cheap.
     */
    public function cheap(): static
    {
        return $this->state(fn(array $attributes) => [
            'preis' => $this->faker->randomFloat(2, 10, 100),
        ]);
    }

    /**
     * Indicate that the daparto is for a specific brand.
     */
    public function forBrand(string $brand): static
    {
        return $this->state(fn(array $attributes) => [
            'teilemarke_teilenummer' => $brand . $this->faker->numberBetween(100000, 999999),
        ]);
    }

    /**
     * Indicate that the daparto has no deposit.
     */
    public function noDeposit(): static
    {
        return $this->state(fn(array $attributes) => [
            'pfand' => 0,
        ]);
    }

    /**
     * Indicate that the daparto has fast delivery.
     */
    public function fastDelivery(): static
    {
        return $this->state(fn(array $attributes) => [
            'lieferzeit' => $this->faker->numberBetween(1, 3),
        ]);
    }
}
