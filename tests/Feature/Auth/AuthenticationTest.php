<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login screen uses the compact apple style without the legacy heading', function () {
    $login = file_get_contents(resource_path('js/pages/auth/Login.vue'));
    $layout = file_get_contents(resource_path('js/layouts/auth/AuthSimpleLayout.vue'));

    expect($login)
        ->not->toContain('Log in to your account')
        ->toContain(':label="t(\'Email address\')"')
        ->toContain('bg-[#f5f5f7]')
        ->toContain('bg-[#0071e3]')
        ->and($layout)
        ->toContain("font-[ui-sans-serif,-apple-system,BlinkMacSystemFont,'SF_Pro_Display'")
        ->toContain('rounded-3xl bg-white')
        ->toContain('ring-[#e3e5e8]')
        ->toContain('/images/auth/speakup-login-background.webp')
        ->toContain('<SpeakUpLogo compact />');

    expect(public_path('images/auth/speakup-login-background.webp'))->toBeFile();

    foreach (['en', 'ru', 'kk'] as $locale) {
        expect(file_get_contents(lang_path("{$locale}.json")))
            ->not->toContain('Log in to your account');
    }
});

test('all authentication forms use the same apple style', function () {
    foreach (['ConfirmPassword', 'ForgotPassword', 'Login', 'ResetPassword', 'VerifyEmail'] as $page) {
        $contents = file_get_contents(resource_path("js/pages/auth/{$page}.vue"));

        expect($contents)
            ->toContain('h-12 rounded-xl')
            ->toContain('bg-[#0071e3]');
    }

    foreach (['ConfirmPassword', 'ForgotPassword', 'ResetPassword', 'VerifyEmail'] as $page) {
        expect(file_get_contents(resource_path("js/pages/auth/{$page}.vue")))
            ->toContain('disable-while-processing');
    }
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('blocked employees cannot authenticate', function () {
    $user = User::factory()->create(['status' => 'blocked']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
