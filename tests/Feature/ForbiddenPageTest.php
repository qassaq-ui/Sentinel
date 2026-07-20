<?php

use App\Models\Inquiry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

test('html authorization failures render the forbidden inertia page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('inquiries.view'));
    $inquiry = Inquiry::factory()->create();

    $this->actingAs($user)
        ->get(route('inquiries.show', $inquiry))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/Forbidden')
            ->where('status', 403));
});

test('json authorization failures remain json responses', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('inquiries.view'));
    $inquiry = Inquiry::factory()->create();

    $this->actingAs($user)
        ->getJson(route('inquiries.show', $inquiry))
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});
