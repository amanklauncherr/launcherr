<?php

use App\Http\Controllers\PhonePay;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Contracts\Role;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return response()->json(['success'=>0,'message'=>'Unauthorized. Token Not available'],400);
})->name('login');


Route::post('/phonePay',[PhonePay::class,'PhonePay'])->name('phonepay');

Route::get('/payment', function() {
    return view('phonepay');
});
