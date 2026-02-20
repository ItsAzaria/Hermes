<?php

namespace App;

use App\Http\Controllers\HomepageController;
use Illuminate\Support\Facades\Route;
use Laracord\Laracord;

class Bot extends Laracord
{
    /**
     * The HTTP routes.
     */
    public function routes(): void
    {
        Route::get('/', HomepageController::class);
    }
}
