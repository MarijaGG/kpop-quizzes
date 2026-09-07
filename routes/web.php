<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\AlbumController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Api\StaticApiController;
use App\Http\Controllers\Admin\GuessIdolImageController;
use App\Http\Controllers\GuessIdolController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['verified'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/history', [ProfileController::class, 'history'])->name('profile.history');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/favourites', [ProfileController::class, 'updateFavorites'])->name('profile.favourites.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () { return view('admin.index'); })->name('index');

        Route::resource('groups', GroupController::class);
        Route::resource('members', MemberController::class);
        Route::resource('albums', AlbumController::class);
        Route::resource('quizzes', QuizController::class);
        Route::resource('guess-idol-images', GuessIdolImageController::class)
            ->parameters(['guess-idol-images' => 'guessIdolImage'])
            ->except(['show']);
        // question/answer management for quizzes
        Route::get('quizzes/{quiz}/questions', [App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('quizzes.questions.index');
        Route::get('quizzes/{quiz}/questions/create', [App\Http\Controllers\Admin\QuestionController::class, 'create'])->name('quizzes.questions.create');
        Route::get('quizzes/{quiz}/questions/{question}/edit', [App\Http\Controllers\Admin\QuestionController::class, 'edit'])->name('quizzes.questions.edit');
        Route::post('quizzes/{quiz}/questions/{question}', [App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('quizzes.questions.update');
    });

    // Quizzes (now protected)
    Route::get('/quizzes', [App\Http\Controllers\QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{id}', [App\Http\Controllers\QuizController::class, 'show'])->name('quizzes.show');
    Route::get('/quizzes/{id}/start', [App\Http\Controllers\QuizController::class, 'start'])->name('quizzes.start');
    Route::get('/quizzes/{id}/take', [App\Http\Controllers\QuizController::class, 'take'])->name('quizzes.take');
    Route::post('/quizzes/{id}/answer', [App\Http\Controllers\QuizController::class, 'answer'])->name('quizzes.answer');
    Route::get('/quizzes/{id}/result', [App\Http\Controllers\QuizController::class, 'result'])->name('quizzes.result');

    Route::get('/guess-idol', [GuessIdolController::class, 'index'])->name('guess-idol.index');
    Route::post('/guess-idol/start', [GuessIdolController::class, 'start'])->name('guess-idol.start');
    Route::get('/guess-idol/take', [GuessIdolController::class, 'take'])->name('guess-idol.take');
    Route::post('/guess-idol/answer', [GuessIdolController::class, 'answer'])->name('guess-idol.answer');
    Route::get('/guess-idol/result', [GuessIdolController::class, 'result'])->name('guess-idol.result');

    // Static JSON API (no DB required) — protected
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/', function () { return response()->json(['ok' => true, 'static' => true]); });

        Route::get('{resource?}', [StaticApiController::class, 'list']);
        Route::get('{resource}/{id}', [StaticApiController::class, 'show']);
        Route::post('{resource}', [StaticApiController::class, 'store']);
        Route::put('{resource}/{id}', [StaticApiController::class, 'update']);
        Route::patch('{resource}/{id}', [StaticApiController::class, 'update']);
        Route::delete('{resource}/{id}', [StaticApiController::class, 'destroy']);
    });
});
