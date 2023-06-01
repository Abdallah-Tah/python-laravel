<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('about', 'about')->name('about');

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('process/prompt', [ChatController::class, 'processPrompt'])->name('process.prompt');
});


// Route::get('/heavy', function () {
//     $file = Storage::disk('public')->path('csv/mlb_players.csv');

//     $response = Http::attach(
//         'file',
//         file_get_contents($file),
//         'mlb_players.csv'
//     )->post('http://127.0.0.1:8098/heavy',[
//         'columnData' => json_encode([
//             'Name' => 'string',
//             'Team' => 'string',
//             'Position' => 'string',
//             'Height(inches)' => 'integer',
//             'Weight(lbs)' => 'integer',
//             'Age' => 'integer',
//         ]),
//         'messages' => json_encode([
//             [
//                 "role" => "system",
//                 "content" => "You are a helpful assistant."
//             ],
//             [
//                 "role" => "user",
//                 "content" => "How many players are there in the dataset?"
//             ]
//         ]),
//         'model' => 'GPT-4',              
//         'lang' => 'python',                
//         'allowLogging' => true,
//     ]);

//     $responseData = json_decode($response->body(), true);

//     dd($responseData);

//     return view('results', ['data' => $responseData]);
// });

// Route::post('/heavy', function () {
//     $file = Storage::disk('public')->path('csv/mlb_players.csv');

//     $messages = [
//         [
//             "role" => "system",
//             "content" => "You are a helpful assistant."
//         ],
//         [
//             "role" => "user",
//             "content" => "How many players are there in the dataset?"
//         ]
//     ];

//     $response = Http::attach(
//         'file',
//         file_get_contents($file),
//         'mlb_players.csv'
//     )->post('http://127.0.0.1:8098/heavy',[
//         'columnData' => json_encode([
//             'Name' => 'string',
//             'Team' => 'string',
//             'Position' => 'string',
//             'Height(inches)' => 'integer',
//             'Weight(lbs)' => 'integer',
//             'Age' => 'integer',
//         ]),
//         'messages' => json_encode($messages),
//         'model' => 'GPT-4',
//         'lang' => 'en',
//         'allowLogging' => true,
//     ]);

//     $responseData = json_decode($response->body(), true);

//     dd($responseData);

//     return response()->json($responseData);
// });




require __DIR__.'/auth.php';
