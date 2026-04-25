<?php

namespace App\Http\Controllers;

use App\Http\Requests\Learning\AnswerExamRequest;
use App\Models\ExamSimulation;
use App\Services\ExamSimulationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamSimulationController extends Controller
{
    public function setup(Request $request, ExamSimulationService $exams): Response
    {
        return Inertia::render('exam/setup', [
            'sections' => collect($exams->sectionSpecs())
                ->map(fn (array $spec, string $section): array => [
                    'section_type' => $section,
                    ...$spec,
                ])
                ->values()
                ->all(),
            'history' => $exams->history($request->user()),
            'scoreDisclaimer' => ExamSimulationService::SCORE_DISCLAIMER,
        ]);
    }

    public function start(Request $request, ExamSimulationService $exams): RedirectResponse
    {
        $simulation = $exams->start($request->user());

        return to_route('exam.show', $simulation);
    }

    public function show(Request $request, ExamSimulation $examSimulation, ExamSimulationService $exams): Response
    {
        return Inertia::render('exam/show', [
            'exam' => $exams->payload($request->user(), $examSimulation),
        ]);
    }

    public function answer(AnswerExamRequest $request, ExamSimulation $examSimulation, ExamSimulationService $exams): RedirectResponse
    {
        $exams->answer(
            $request->user(),
            $examSimulation,
            (int) $request->validated('answer_id'),
            (int) $request->validated('selected_option_id'),
            (int) ($request->validated('time_spent_seconds') ?? 0)
        );

        return to_route('exam.show', $examSimulation);
    }

    public function finishSection(Request $request, ExamSimulation $examSimulation, ExamSimulationService $exams): RedirectResponse
    {
        $simulation = $exams->finishSection($request->user(), $examSimulation);

        return $simulation->status === 'completed'
            ? to_route('exam.result', $simulation)
            : to_route('exam.show', $simulation);
    }

    public function finish(Request $request, ExamSimulation $examSimulation, ExamSimulationService $exams): RedirectResponse
    {
        $simulation = $exams->finish($request->user(), $examSimulation);

        return to_route('exam.result', $simulation);
    }

    public function result(Request $request, ExamSimulation $examSimulation, ExamSimulationService $exams): Response
    {
        return Inertia::render('exam/result', [
            'result' => $exams->resultPayload($request->user(), $examSimulation),
        ]);
    }
}
