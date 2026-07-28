<?php

test('the mentoring page can be displayed', function () {
    $this->get(route('pages.mentoring'))
        ->assertOk()
        ->assertSee('Mentorat individuel')
        ->assertSee('Réserver une séance');
});
