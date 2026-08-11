<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/sitemap.xml', function () {
    $urls = [
        [
            'loc' => url('/'),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ],
    ];

    $xml = view('sitemap', ['urls' => $urls])->render();

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');
