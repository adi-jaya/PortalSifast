<?php

test('returns the login screen at the site root', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

test('legacy login url redirects to site root', function () {
    $this->get('/login')->assertRedirect('/');
});
