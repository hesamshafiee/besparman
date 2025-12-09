<?php

namespace Database\Factories;

use App\Models\ProfitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfitGroup>
 */
class ProfitGroupFactory extends Factory
{
    protected $model = ProfitGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate random but valid percentages (sum = 100)
        $designer = $this->faker->numberBetween(10, 60);
        $site     = $this->faker->numberBetween(10, 80 - $designer);
        $referrer = 100 - ($designer + $site);

        return [
            'title'           => $this->faker->words(3, true),
            'designer_profit' => $designer,
            'site_profit'     => $site,
            'referrer_profit' => $referrer,
        ];
    }
}
