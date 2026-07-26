<?php

it('lets a dev view the database schema documentation page, but forbids other roles', function () {
    $dev = makeUserWithRole('dev');
    $admin = makeUserWithRole('admin');

    $this->actingAs($dev)->get(route('dev.skema-database'))
        ->assertOk()
        ->assertSee('Skema Database');

    $this->actingAs($admin)->get(route('dev.skema-database'))->assertForbidden();
});
