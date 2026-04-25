<?php

namespace App\Http\Controllers;

use App\Models\StudyDay;
use App\Services\StudyPathService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudyPathController extends Controller
{
    public function index(Request $request, StudyPathService $studyPath): Response
    {
        $path = $studyPath->activePath();
        $completedDayIds = $studyPath->completedDayIds($request->user());
        $assessments = $studyPath->assessmentMap($request->user());

        return Inertia::render('study-path/index', [
            'path' => [
                'id' => $path->id,
                'title' => $path->title,
                'description' => $path->description,
                'duration_days' => $path->duration_days,
                'completed_days' => count($completedDayIds),
                'weeks' => $studyPath->weekGroups($path->studyDays, $completedDayIds, $assessments),
            ],
        ]);
    }

    public function complete(Request $request, StudyDay $studyDay, StudyPathService $studyPath): RedirectResponse
    {
        $studyPath->complete($request->user(), $studyDay);

        return back();
    }
}
