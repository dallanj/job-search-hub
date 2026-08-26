<?php

use App\Models\ApplicationNote;
use App\Models\ApplicationStatusEvent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;

test('it creates a connected demo job search', function () {
    $this->seed(DemoDataSeeder::class);

    $user = User::query()->where('email', DemoDataSeeder::EMAIL)->sole();

    expect(Company::query()->where('user_id', $user->id)->count())->toBe(6)
        ->and(JobApplication::query()->where('user_id', $user->id)->count())->toBe(12)
        ->and(Contact::query()->where('user_id', $user->id)->count())->toBe(12)
        ->and(Interview::query()->count())->toBe(5)
        ->and(Task::query()->count())->toBe(18)
        ->and(ApplicationNote::query()->count())->toBe(24)
        ->and(ApplicationStatusEvent::query()->count())->toBeGreaterThan(12);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('rerunning the demo seeder replaces rather than duplicates demo data', function () {
    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(User::query()->where('email', DemoDataSeeder::EMAIL)->count())->toBe(1)
        ->and(JobApplication::query()->count())->toBe(12)
        ->and(Company::query()->count())->toBe(6);
});
