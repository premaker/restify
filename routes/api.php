<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

/**
 * Register routes of the enabled modules.
 */
if (! app()->routesAreCached()) {
    $modules = app('modules')->enabled();
    foreach ($modules as $module) {
        if (file_exists($routePath = $module->getPath().'/Http/routes.php')) {
            require $routePath;
        }
    }
}
