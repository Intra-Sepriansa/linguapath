<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkillType;
use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\Lesson;
use App\Models\Passage;
use App\Models\Question;
use App\Models\SkillTag;
use App\Models\Vocabulary;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'metrics' => [
                'lessons' => Lesson::query()->count(),
                'questions' => Question::query()->count(),
                'audio' => AudioAsset::query()->count(),
                'passages' => Passage::query()->count(),
                'vocabulary' => Vocabulary::query()->count(),
                'skill_tags' => SkillTag::query()->count(),
                'missing_audio' => Question::query()
                    ->where('section_type', SkillType::Listening)
                    ->where('exam_eligible', true)
                    ->whereIn('status', Question::ACTIVE_STATUSES)
                    ->whereDoesntHave('audioAsset', fn ($query) => $query->where('is_real_audio', true))
                    ->count(),
                'short_passages' => Passage::query()->where('word_count', '<', 300)->count(),
                'missing_explanation' => Question::query()->whereNull('explanation')->orWhere('explanation', '')->count(),
                'missing_skill_tag' => Question::query()->whereNull('skill_tag_id')->count(),
                'missing_difficulty' => Question::query()->whereNull('difficulty')->orWhere('difficulty', '')->count(),
            ],
            'qualityFlags' => [
                'listening_without_real_audio' => Question::query()
                    ->where('section_type', SkillType::Listening)
                    ->where('exam_eligible', true)
                    ->whereIn('status', Question::ACTIVE_STATUSES)
                    ->whereDoesntHave('audioAsset', fn ($query) => $query->where('is_real_audio', true))
                    ->limit(8)
                    ->get(['id', 'question_text'])
                    ->map(fn (Question $question): array => [
                        'id' => $question->id,
                        'label' => $question->question_text,
                    ]),
                'short_passages' => Passage::query()
                    ->where('word_count', '<', 300)
                    ->limit(8)
                    ->get(['id', 'title', 'word_count'])
                    ->map(fn (Passage $passage): array => [
                        'id' => $passage->id,
                        'label' => $passage->title,
                        'detail' => $passage->word_count.' words',
                    ]),
                'question_distribution' => Question::query()
                    ->select('section_type', DB::raw('count(*) as total'))
                    ->groupBy('section_type')
                    ->orderBy('section_type')
                    ->get()
                    ->map(fn ($row): array => [
                        'label' => (string) $row->section_type->value,
                        'total' => (int) $row->total,
                    ]),
            ],
        ]);
    }
}
