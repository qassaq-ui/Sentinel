<?php

use Inertia\Testing\AssertableInertia as Assert;

test('locale data is shared with inertia pages', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('locale.current', 'en')
            ->has('locale.available', 2)
            ->where('locale.available.0.code', 'en')
            ->where('locale.available.1.code', 'ru')
            ->etc()
        );
});

test('users can change the site locale', function () {
    $response = $this->from(route('home'))->post(route('locale.update'), [
        'locale' => 'ru',
    ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('locale', 'ru');

    $this
        ->withSession(['locale' => 'ru'])
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale.current', 'ru')
            ->etc()
        );
});

test('users cannot choose an unsupported locale', function () {
    $response = $this->from(route('home'))->post(route('locale.update'), [
        'locale' => 'zz',
    ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('locale');
});
