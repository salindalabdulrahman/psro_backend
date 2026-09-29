<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vue SPA (single-domain deploy)
|--------------------------------------------------------------------------
| API routes are registered before web routes, so /api/* is never handled here.
| Run `npm run build` from the project root to write index.html + spa-assets/ into public/.
*/

$serveSpa = static function () {
    $index = public_path('index.html');

    if (! is_file($index)) {
        return response(
            "Frontend not built. From the repository root run: npm install && npm run build\n",
            503,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    return response()->file($index, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'no-cache, private',
    ]);
};

Route::get('/', $serveSpa);

Route::get('/{any}', $serveSpa)->where('any', '.*');
