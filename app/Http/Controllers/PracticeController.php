<?php

namespace App\Http\Controllers;

use App\Enums\PracticeMode;
use App\Enums\SkillType;
use App\Http\Requests\Learning\AnswerPracticeRequest;
use App\Http\Requests\Learning\StartPracticeRequest;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Services\PracticeService;
use App\Services\StudyPathService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeController extends Controller
{
    public function setup(Request $request, StudyPathService $studyPath): Response
    {
        $currentDay = $studyPath->currentDay($request->user());
        $practiceSections = [SkillType::Structure, SkillType::Listening, SkillType::Reading, SkillType::Mixed];
        $questionCounts = Question::query()
            ->selectRaw('section_type, count(*) as total')
            ->whereIn('section_type', [SkillType::Listening->value, SkillType::Structure->value, SkillType::Reading->value])
            ->groupBy('section_type')
            ->pluck('total', 'section_type');

        return Inertia::render('practice/setup', [
            'currentDay' => $currentDay ? [
                'id' => $currentDay->id,
                'day_number' => $currentDay->day_number,
                'title' => $currentDay->title,
                'focus_skill' => $currentDay->focus_skill->value,
            ] : null,
            'sections' => collect($practiceSections)
                ->map(fn (SkillType $section): array => [
                    'value' => $section->value,
                    'label' => $section->label(),
                    'total_questions' => $section === SkillType::Mixed
                        ? (int) $questionCounts->sum()
                        : (int) ($questionCounts[$section->value] ?? 0),
                ])
                ->all(),
            'modes' => collect(PracticeMode::cases())
                ->map(fn (PracticeMode $mode): array => [
                    'value' => $mode->value,
                    'label' => ucfirst($mode->value),
                    'description' => $this->modeDescription($mode),
                ])
                ->all(),
        ]);
    }

    public function start(StartPracticeRequest $request, PracticeService $practice): RedirectResponse
    {
        $session = $practice->start($request->user(), $request->validated());

        return to_route('practice.show', $session);
    }

    public function show(Request $request, PracticeSession $practiceSession, PracticeService $practice): Response
    {
        abort_unless($practiceSession->user_id === $request->user()->id, 403);

        return Inertia::render('practice/show', [
            'session' => $practice->payload($practiceSession),
        ]);
    }

    public function answer(AnswerPracticeRequest $request, PracticeSession $practiceSession, PracticeService $practice): RedirectResponse
    {
        $practice->answer(
            $request->user(),
            $practiceSession,
            (int) $request->validated('question_id'),
            (int) $request->validated('selected_option_id'),
            (int) ($request->validated('time_spent_seconds') ?? 0)
        );

        return to_route('practice.show', $practiceSession);
    }

    public function finish(Request $request, PracticeSession $practiceSession, PracticeService $practice): RedirectResponse
    {
        $practice->finish($request->user(), $practiceSession);

        return to_route('practice.result', $practiceSession);
    }

    public function result(Request $request, PracticeSession $practiceSession, PracticeService $practice): Response
    {
        abort_unless($practiceSession->user_id === $request->user()->id, 403);

        return Inertia::render('practice/result', [
            'result' => $practice->resultPayload($practiceSession),
        ]);
    }

    private function modeDescription(PracticeMode $mode): string
    {
        return match ($mode) {
            PracticeMode::Quick => 'Sesi cepat dengan soal acak untuk menjaga ritme.',
            PracticeMode::Focus => 'Latihan fokus untuk satu skill utama hari ini.',
            PracticeMode::Weakness => 'Drill pemulihan untuk pola yang sering salah.',
            PracticeMode::Review => 'Ulangi konsep lama dengan tempo lebih tenang.',
            PracticeMode::Lesson => 'Mini-test wajib dari lesson aktif.',
        };
    }
}
