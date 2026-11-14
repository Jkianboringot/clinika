<?php
pest()->group('skip');

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
