<?php

namespace Database\Factories;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(ReportCategory::cases());

        return [
            'user_id' => User::factory(),
            'description' => 'Tumpukan sampah di pinggir jalan belum diangkut.',
            'photo_path' => 'reports/'.fake()->uuid().'.jpg',
            // Area around Wonosobo, Central Java.
            'latitude' => fake()->randomFloat(7, -7.42, -7.30),
            'longitude' => fake()->randomFloat(7, 109.85, 109.96),
            'category' => $category,
            'ai_label' => $category->value,
            'ai_confidence' => fake()->randomFloat(4, 0.6, 0.99),
            'status' => ReportStatus::Pending,
        ];
    }

    public function status(ReportStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
