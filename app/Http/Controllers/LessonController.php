<?php

namespace App\Http\Controllers;

use App\Models\StudyDay;
use App\Services\StudyPathService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function show(Request $request, StudyDay $studyDay, StudyPathService $studyPath): Response
    {
        $studyDay->load(['lesson.questions']);
        $completedDayIds = $studyPath->completedDayIds($request->user());
        $assessments = $studyPath->assessmentMap($request->user());

        return Inertia::render('lessons/show', [
            'day' => $studyPath->dayPayload($studyDay, in_array($studyDay->id, $completedDayIds, true), $assessments[$studyDay->id] ?? null),
            'lesson' => [
                'id' => $studyDay->lesson?->id,
                'title' => $studyDay->lesson?->title,
                'summary' => $studyDay->lesson?->summary,
                'content' => $studyDay->lesson?->content,
                'skill_type' => $studyDay->lesson?->skill_type->value,
                'question_count' => $studyDay->lesson?->questions->count() ?? 0,
            ],
        ]);
    }
}
