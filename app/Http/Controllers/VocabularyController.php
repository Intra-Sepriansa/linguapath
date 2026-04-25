<?php

namespace App\Http\Controllers;

use App\Enums\VocabularyStatus;
use App\Http\Requests\Learning\MarkVocabularyRequest;
use App\Models\Vocabulary;
use App\Services\VocabularyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VocabularyController extends Controller
{
    public function index(Request $request, VocabularyService $vocabulary): Response
    {
        return Inertia::render('vocabulary/index', [
            'words' => $vocabulary->daily($request->user()),
            'summary' => $vocabulary->summary($request->user()),
        ]);
    }

    public function mark(MarkVocabularyRequest $request, Vocabulary $vocabulary, VocabularyService $service): RedirectResponse
    {
        $service->mark($request->user(), $vocabulary, VocabularyStatus::from($request->validated('status')));

        return back();
    }
}
