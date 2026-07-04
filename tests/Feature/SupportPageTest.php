<?php

test('support page returns 200', function () {
    $this->get(route('support'))->assertOk();
});

test('contact form accepts a valid submission', function () {
    $this->from(route('support'))
        ->post(route('support.contact'), [
            'name' => 'Sara Ahmed',
            'email' => 'sara@teamhub.test',
            'message' => 'I need help joining a club.',
        ])
        ->assertRedirect(route('support'));
});

test('contact form validates required fields', function () {
    $this->from(route('support'))
        ->post(route('support.contact'), [
            'name' => '',
            'email' => 'not-an-email',
            'message' => '',
        ])
        ->assertSessionHasErrors(['name', 'email', 'message']);
});
