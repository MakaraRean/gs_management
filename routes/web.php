<?php

use App\Http\Controllers\StationSwitchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale');
    $supported = ['km', 'en'];

    if (! in_array($locale, $supported)) {
        abort(422);
    }

    return back()->withCookie(
        cookie()->forever('locale', $locale, '/', null, false, false, false, 'lax')
    );
})->name('locale.set');

Route::post('/station', [StationSwitchController::class, 'switch'])
    ->middleware(['auth', 'verified'])
    ->name('station.switch');

require __DIR__.'/app.php';
require __DIR__.'/settings.php';
