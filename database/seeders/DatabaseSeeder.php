<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Company::factory()
            ->count(6)
            ->for($user)
            ->create()
            ->each(function (Company $company) use ($user): void {
                JobApplication::factory()
                    ->count(2)
                    ->for($user)
                    ->for($company)
                    ->withStatus(fake()->randomElement(ApplicationStatus::cases()))
                    ->create();
            });
    }
}
