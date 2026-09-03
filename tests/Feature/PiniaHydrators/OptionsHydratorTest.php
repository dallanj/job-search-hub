<?php

use App\Enums\ApplicationStatus;
use App\Enums\TaskPriority;
use App\Models\Company;
use App\Models\User;
use Dallanj\PiniaHydrate\Contracts\Hydrator;
use Dallanj\PiniaHydrate\ModuleRegistry;

test('options methods build shared option state', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Owned Company']);
    Company::factory()->create(['name' => 'Private Company']);
    request()->setUserResolver(fn () => $user);
    $registry = app(ModuleRegistry::class);

    $payload = app(Hydrator::class)
        ->replace('options', [
            'companies',
            'applicationStatuses',
            'taskPriorities',
        ])
        ->toArray();
    $state = $payload['modules']['options']['state'];

    expect($registry->has('options'))->toBeTrue()
        ->and($payload['modules']['options']['mode'])->toBe('replace')
        ->and($state['companies'])->toHaveCount(1)
        ->and($state['companies'][0]['name'])->toBe('Owned Company')
        ->and($state['applicationStatuses'])->toHaveCount(count(ApplicationStatus::cases()))
        ->and($state['taskPriorities'])->toHaveCount(count(TaskPriority::cases()));
});
