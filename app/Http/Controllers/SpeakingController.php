<?php

namespace App\Http\Controllers;

use App\Http\Requests\Learning\StoreSpeakingAttemptRequest;
use App\Services\SpeakingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpeakingController extends Controller
{
    public function index(Request $request, SpeakingService $speaking): Response
    {
        return Inertia::render('speaking/index', [
            'speaking' => $speaking->payload($request->user()),
        ]);
    }

    public function store(StoreSpeakingAttemptRequest $request, SpeakingService $speaking): RedirectResponse
    {
        $speaking->storeAttempt($request->user(), $request->validated());

        return back();
    }
}
