<?php
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\V1\MetricsController;
use App\Models\Address;
use App\Models\Logistic;

use Illuminate\Support\Facades\Route;

Route::get('/prometheus', [MetricsController::class, 'metrics']);

Route::get('/',function(){
    Address::create(['user_id'=>3]) ;
}) ;