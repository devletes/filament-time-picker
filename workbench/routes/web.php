<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

Route::get('/devletes.png', function () {
    return response()->file(__DIR__.'/../public/devletes.png', [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/avatar.webp', function () {
    return response()->file(__DIR__.'/../public/salman-hijazi.webp', [
        'Content-Type' => 'image/webp',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

// Workbench-only: the capture script logs in several users, which trips Filament's login throttle.
Route::get('/dev-login/{email}', function (string $email) {
    abort_unless(app()->environment(['local', 'testing']), 404);

    auth()->login(User::query()->where('email', $email)->firstOrFail());

    return redirect('/admin');
});
