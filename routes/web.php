<?php

use Illuminate\Support\Facades\Route;
use App\Models\AppSetting;

Route::get('/', function () {
    $logoUrl = \App\Models\AppSetting::where('key', 'general.logo_url')->value('value') ?? '/logo-activibe.svg';

    return view('welcome', compact('logoUrl'));
});
