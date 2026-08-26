<?php

use App\Models\Contact;

test('the factory creates a company owned by the contact user', function () {
    $contact = Contact::factory()->create();

    expect($contact->company->user->is($contact->user))->toBeTrue();
});

test('a contact belongs to its user and company', function () {
    $contact = Contact::factory()->create();

    expect($contact->user->contacts->contains($contact))->toBeTrue();
    expect($contact->company->contacts->contains($contact))->toBeTrue();
});
