<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login')); // the site root sends visitors to the login page
});
