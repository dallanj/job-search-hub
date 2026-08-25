<?php

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;

test('a company belongs to a user and contains job applications', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $application = JobApplication::factory()->for($user)->for($company)->create();

    expect($company->user->is($user))->toBeTrue();
    expect($company->jobApplications->modelKeys())->toBe([$application->getKey()]);
    expect($user->companies->modelKeys())->toBe([$company->getKey()]);
});

test('deleting a user removes their companies and job applications', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $application = JobApplication::factory()->for($user)->for($company)->create();

    $user->delete();

    $this->assertModelMissing($company);
    $this->assertModelMissing($application);
});
