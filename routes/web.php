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
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/prometheus', [MetricsController::class, 'metrics']);
Route::get('tesdt', function () {
    Product::factory()->count(100)->create();
    
});