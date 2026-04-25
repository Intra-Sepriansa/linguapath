<?php

namespace App\Http\Controllers;

use App\Http\Requests\Learning\StoreWritingSubmissionRequest;
use App\Services\WritingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WritingController extends Controller
{
    public function index(Request $request, WritingService $writing): Response
    {
        return Inertia::render('writing/index', [
            'writing' => $writing->payload($request->user()),
        ]);
    }

    public function store(StoreWritingSubmissionRequest $request, WritingService $writing): RedirectResponse
    {
        $writing->storeSubmission($request->user(), $request->validated());

        return back();
    }
}
