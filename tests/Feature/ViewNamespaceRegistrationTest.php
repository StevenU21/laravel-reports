<?php

it('does not register a package view namespace', function () {
    $hints = app('view')->getFinder()->getHints();

    expect($hints)->not->toHaveKey('laravel-reports');
});
