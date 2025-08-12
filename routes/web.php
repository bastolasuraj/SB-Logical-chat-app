<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('app');
});

// Email verification route
Route::get('/verify-email', function (Request $request) {
    // This will redirect to the frontend with the verification parameters
    $email = $request->query('email');
    $token = $request->query('token');
    
    if (!$email || !$token) {
        return redirect('/?error=invalid_verification_link');
    }
    
    return redirect("/?verify_email=true&email={$email}&token={$token}");
})->name('verification.verify');

// Catch-all route for Vue.js SPA routing
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
