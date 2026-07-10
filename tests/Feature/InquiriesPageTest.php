<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the inquiries page to the login page', function () {
    $response = $this->get(route('inquiries.index'));

    $response->assertRedirect(route('login'));
});

test('guests are redirected from the create inquiry page to the login page', function () {
    $response = $this->get(route('inquiries.create'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the inquiries page', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inquiries')
        );
});

test('authenticated users can visit the create inquiry page', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('inquiries.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inquiries/Create')
        );
});
