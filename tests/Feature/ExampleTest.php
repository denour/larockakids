<?php

test('application redirects to admin', function () {
    $this->get('/')->assertRedirect('/admin');
});
