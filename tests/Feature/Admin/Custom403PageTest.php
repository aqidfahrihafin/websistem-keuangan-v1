<?php

it('renders the custom modern 403 page instead of the default one when a role check fails', function () {
    $bendahara = makeUserWithRole('bendahara');

    $response = $this->actingAs($bendahara)->get(route('admin.users.index'));

    $response->assertForbidden();
    $response->assertSee('Akses Ditolak');
    $response->assertSee('Error 403');
});
