<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Admin\AudioAssetController as AdminAudioAssetController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\ReadingPassageController as AdminReadingPassageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamSimulationController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MistakeController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\SpeakingController;
use App\Http\Controllers\StudyPathController;
use App\Http\Controllers\VocabularyController;
use App\Http\Controllers\WritingController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('study-path', [StudyPathController::class, 'index'])->name('study-path.index');
    Route::get('lessons/{studyDay}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('study-days/{studyDay}/complete', [StudyPathController::class, 'complete'])->name('study-days.complete');

    Route::get('practice/setup', [PracticeController::class, 'setup'])->name('practice.setup');
    Route::post('practice/start', [PracticeController::class, 'start'])->name('practice.start');
    Route::get('practice/{practiceSession}', [PracticeController::class, 'show'])->name('practice.show');
    Route::post('practice/{practiceSession}/answer', [PracticeController::class, 'answer'])->name('practice.answer');
    Route::post('practice/{practiceSession}/finish', [PracticeController::class, 'finish'])->name('practice.finish');
    Route::get('practice/{practiceSession}/result', [PracticeController::class, 'result'])->name('practice.result');

    Route::get('exam/setup', [ExamSimulationController::class, 'setup'])->name('exam.setup');
    Route::post('exam/start', [ExamSimulationController::class, 'start'])->name('exam.start');
    Route::get('exam/{examSimulation}', [ExamSimulationController::class, 'show'])->name('exam.show');
    Route::post('exam/{examSimulation}/answer', [ExamSimulationController::class, 'answer'])->name('exam.answer');
    Route::post('exam/{examSimulation}/finish-section', [ExamSimulationController::class, 'finishSection'])->name('exam.finish-section');
    Route::post('exam/{examSimulation}/finish', [ExamSimulationController::class, 'finish'])->name('exam.finish');
    Route::get('exam/{examSimulation}/result', [ExamSimulationController::class, 'result'])->name('exam.result');

    Route::get('vocabulary', [VocabularyController::class, 'index'])->name('vocabulary.index');
    Route::patch('vocabulary/{vocabulary}/mark', [VocabularyController::class, 'mark'])->name('vocabulary.mark');

    Route::get('mistakes', [MistakeController::class, 'index'])->name('mistakes.index');
    Route::patch('mistakes/{mistakeJournal}/review', [MistakeController::class, 'review'])->name('mistakes.review');

    Route::get('speaking', [SpeakingController::class, 'index'])->name('speaking.index');
    Route::post('speaking/attempts', [SpeakingController::class, 'store'])->name('speaking.attempts.store');

    Route::get('writing', [WritingController::class, 'index'])->name('writing.index');
    Route::post('writing/submissions', [WritingController::class, 'store'])->name('writing.submissions.store');

    Route::get('analytics', AnalyticsController::class)->name('analytics.index');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function (): void {
            Route::get('/', AdminDashboardController::class)->name('dashboard');
            Route::get('audio-assets', [AdminAudioAssetController::class, 'index'])->name('audio-assets.index');
            Route::post('audio-assets', [AdminAudioAssetController::class, 'store'])->name('audio-assets.store');
            Route::resource('questions', AdminQuestionController::class);
            Route::resource('reading-passages', AdminReadingPassageController::class)
                ->parameters(['reading-passages' => 'readingPassage']);
        });
});

require __DIR__.'/settings.php';
