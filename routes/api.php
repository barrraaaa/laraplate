<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (PRD 5.8) — protected by Sanctum bearer tokens.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        $abilities = $request->user()?->currentAccessToken()?->abilities;

        return [
            'id' => $request->user()?->id,
            'name' => $request->user()?->name,
            'email' => $request->user()?->email,
            'abilities' => is_array($abilities) ? $abilities : [],
        ];
    });
});
