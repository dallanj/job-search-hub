<?php

use App\Enums\ApplicationStatus;
use App\Models\User;
use Dallanj\PiniaHydrate\Contracts\Hydrator;
use Dallanj\PiniaHydrate\ModuleRegistry;

test('pipeline methods build registered module state', function () {
    $user = User::factory()->create();
    request()->setUserResolver(fn () => $user);
    $registry = app(ModuleRegistry::class);
    $payload = app(Hydrator::class)
        ->replace('pipeline', ['columns'])
        ->toArray();

    expect($registry->has('pipeline'))->toBeTrue()
        ->and($payload['version'])->toBe(1)
        ->and($payload['modules']['pipeline']['mode'])->toBe('replace')
        ->and($payload['modules']['pipeline']['state']['columns'])->toHaveCount(count(ApplicationStatus::cases()))
        ->and($payload['modules']['pipeline']['state'])->not->toHaveKey('companies')
        ->and($payload['modules']['pipeline']['state'])->not->toHaveKey('filters');
});
