<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Http\Requests\Learning\ReviewMistakeRequest;
use App\Models\MistakeJournal;
use App\Services\MistakeJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MistakeController extends Controller
{
    public function index(Request $request): Response
    {
        $mistakes = MistakeJournal::query()
            ->whereBelongsTo($request->user())
            ->with('question')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (MistakeJournal $mistake): array => [
                'id' => $mistake->id,
                'section_type' => $mistake->section_type->value,
                'mistake_type' => $mistake->mistake_type->value,
                'question' => $mistake->question->question_text,
                'user_answer' => $mistake->user_answer,
                'correct_answer' => $mistake->correct_answer,
                'note' => $mistake->note,
                'why_wrong' => $mistake->why_wrong,
                'why_correct' => $mistake->why_correct,
                'personal_note' => $mistake->personal_note,
                'frequency' => $mistake->frequency,
                'next_review_at' => $mistake->next_review_at?->toDateString(),
                'review_status' => $mistake->review_status->value,
                'created_at' => $mistake->created_at->toDateString(),
            ]);

        return Inertia::render('mistakes/index', [
            'mistakes' => $mistakes,
        ]);
    }

    public function review(ReviewMistakeRequest $request, MistakeJournal $mistakeJournal, MistakeJournalService $mistakes): RedirectResponse
    {
        abort_unless($mistakeJournal->user_id === $request->user()->id, 403);

        $mistakes->mark($mistakeJournal, ReviewStatus::from($request->validated('review_status')));

        return back();
    }
}
