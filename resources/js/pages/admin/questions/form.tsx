import { Link, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Save } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export type SelectOption = {
    value: string;
    label: string;
};

export type PassageOption = {
    id: number;
    title: string;
    topic: string | null;
    difficulty: string;
    status: string;
    word_count: number;
};

export type AudioAssetOption = {
    id: number;
    title: string;
    status: string;
    is_real_audio: boolean;
    is_approved_for_exam: boolean;
    quality_badges: string[];
    duration_seconds: number;
    accent: string;
    audio_url: string | null;
    transcript_reviewed_at: string | null;
    approved_at: string | null;
};

export type SkillTagOption = {
    id: number;
    code: string;
    name: string;
    domain: string;
    difficulty: string;
};

export type QuestionFormOptions = {
    sections: SelectOption[];
    questionTypes: Record<string, SelectOption[]>;
    difficulties: string[];
    statuses: string[];
    qualityFilters: string[];
    passages: PassageOption[];
    audioAssets: AudioAssetOption[];
    skillTags: SkillTagOption[];
};

export type QuestionOptionInput = {
    label: string;
    text: string;
    is_correct: boolean;
};

export type QuestionFormData = {
    section_type: string;
    question_type: string;
    difficulty: string;
    status: string;
    passage_id: string;
    audio_asset_id: string;
    skill_tag_id: string;
    question_text: string;
    explanation: string;
    evidence_sentence: string;
    options: QuestionOptionInput[];
};

type QuestionFormProps = {
    mode: 'create' | 'edit';
    action: string;
    options: QuestionFormOptions;
    initialValues?: Partial<QuestionFormData>;
    cancelHref: string;
};

const optionLabels = ['A', 'B', 'C', 'D'];

const defaultValues: QuestionFormData = {
    section_type: 'reading',
    question_type: 'main_idea',
    difficulty: 'intermediate',
    status: 'draft',
    passage_id: '',
    audio_asset_id: '',
    skill_tag_id: '',
    question_text: '',
    explanation: '',
    evidence_sentence: '',
    options: optionLabels.map((label, index) => ({
        label,
        text: '',
        is_correct: index === 0,
    })),
};

export function QuestionForm({
    mode,
    action,
    options,
    initialValues,
    cancelHref,
}: QuestionFormProps) {
    const form = useForm<QuestionFormData>({
        ...defaultValues,
        ...initialValues,
        options: normalizeOptions(initialValues?.options),
    });
    const errors = form.errors as Record<string, string | undefined>;
    const sectionTypes = options.questionTypes[form.data.section_type] ?? [];
    const selectedAudio = options.audioAssets.find(
        (asset) => String(asset.id) === form.data.audio_asset_id,
    );
    const selectedPassage = options.passages.find(
        (passage) => String(passage.id) === form.data.passage_id,
    );
    const warnings = qualityWarnings(form.data, selectedAudio);
    const isActive = ['ready', 'published'].includes(form.data.status);
    const isReady = isActive && warnings.length === 0;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (mode === 'create') {
            form.post(action, { preserveScroll: true });

            return;
        }

        form.put(action, { preserveScroll: true });
    };

    const setSection = (section: string) => {
        const nextTypes = options.questionTypes[section] ?? [];

        form.setData({
            ...form.data,
            section_type: section,
            question_type: nextTypes[0]?.value ?? '',
            passage_id: section === 'reading' ? form.data.passage_id : '',
            audio_asset_id:
                section === 'listening' ? form.data.audio_asset_id : '',
            evidence_sentence:
                section === 'reading' ? form.data.evidence_sentence : '',
        });
    };

    const updateOption = (
        index: number,
        key: keyof QuestionOptionInput,
        value: string | boolean,
    ) => {
        form.setData(
            'options',
            form.data.options.map((option, optionIndex) =>
                optionIndex === index ? { ...option, [key]: value } : option,
            ),
        );
    };

    const setCorrectAnswer = (label: string) => {
        form.setData(
            'options',
            form.data.options.map((option) => ({
                ...option,
                is_correct: option.label === label,
            })),
        );
    };

    return (
        <form onSubmit={submit} className="grid gap-6">
            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">
                            Question metadata
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Drafts can be incomplete, but ready or published
                            questions must pass TOEFL content checks.
                        </p>
                    </div>
                    <span
                        className={`w-fit rounded-md px-3 py-1 text-sm font-semibold ${
                            isReady
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/25 dark:text-emerald-100'
                                : 'bg-amber-50 text-amber-700 dark:bg-amber-950/25 dark:text-amber-100'
                        }`}
                    >
                        {isReady ? 'Ready for exam' : 'Draft incomplete'}
                    </span>
                </div>

                <div className="mt-6 grid gap-5">
                    <div className="grid gap-4 md:grid-cols-4">
                        <Field label="Section" error={errors.section_type}>
                            <select
                                value={form.data.section_type}
                                onChange={(event) =>
                                    setSection(event.target.value)
                                }
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
                            >
                                {options.sections.map((section) => (
                                    <option
                                        key={section.value}
                                        value={section.value}
                                    >
                                        {section.label}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field
                            label="Question type"
                            error={errors.question_type}
                        >
                            <select
                                value={form.data.question_type}
                                onChange={(event) =>
                                    form.setData(
                                        'question_type',
                                        event.target.value,
                                    )
                                }
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                            >
                                <option value="">Select type</option>
                                {sectionTypes.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Difficulty" error={errors.difficulty}>
                            <select
                                value={form.data.difficulty}
                                onChange={(event) =>
                                    form.setData(
                                        'difficulty',
                                        event.target.value,
                                    )
                                }
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
                            >
                                <option value="">Select difficulty</option>
                                {options.difficulties.map((difficulty) => (
                                    <option key={difficulty} value={difficulty}>
                                        {labelize(difficulty)}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Status" error={errors.status}>
                            <select
                                value={form.data.status}
                                onChange={(event) =>
                                    form.setData('status', event.target.value)
                                }
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
                            >
                                {options.statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {labelize(status)}
                                    </option>
                                ))}
                            </select>
                        </Field>
                    </div>

                    <Field label="Skill tag" error={errors.skill_tag_id}>
                        <select
                            value={form.data.skill_tag_id}
                            onChange={(event) =>
                                form.setData('skill_tag_id', event.target.value)
                            }
                            className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                        >
                            <option value="">Select skill tag</option>
                            {options.skillTags.map((tag) => (
                                <option key={tag.id} value={tag.id}>
                                    {tag.domain} · {tag.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    {form.data.section_type === 'reading' && (
                        <div className="grid gap-4 md:grid-cols-[1fr_0.75fr]">
                            <Field
                                label="Reading passage"
                                error={errors.passage_id}
                            >
                                <select
                                    value={form.data.passage_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'passage_id',
                                            event.target.value,
                                        )
                                    }
                                    className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                >
                                    <option value="">Select passage</option>
                                    {options.passages.map((passage) => (
                                        <option
                                            key={passage.id}
                                            value={passage.id}
                                        >
                                            {passage.title} ·{' '}
                                            {passage.word_count} words
                                        </option>
                                    ))}
                                </select>
                            </Field>

                            {selectedPassage && (
                                <div className="rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-900">
                                    <p className="font-semibold">
                                        {selectedPassage.title}
                                    </p>
                                    <p className="mt-1 text-slate-500">
                                        {selectedPassage.topic ?? 'No topic'} ·{' '}
                                        {labelize(selectedPassage.status)}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    {form.data.section_type === 'listening' && (
                        <div className="grid gap-4 md:grid-cols-[1fr_0.75fr]">
                            <Field
                                label="Audio asset"
                                error={errors.audio_asset_id}
                            >
                                <select
                                    value={form.data.audio_asset_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'audio_asset_id',
                                            event.target.value,
                                        )
                                    }
                                    className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                >
                                    <option value="">Select audio</option>
                                    {options.audioAssets.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.title} · {asset.status}
                                        </option>
                                    ))}
                                </select>
                            </Field>

                            {selectedAudio && (
                                <div className="rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-900">
                                    <p className="font-semibold">
                                        {selectedAudio.title}
                                    </p>
                                    <p className="mt-1 text-slate-500">
                                        {selectedAudio.duration_seconds}s ·{' '}
                                        {selectedAudio.is_approved_for_exam
                                            ? 'Approved for exam'
                                            : 'Needs review'}
                                    </p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {selectedAudio.quality_badges.map(
                                            (badge) => (
                                                <span
                                                    key={badge}
                                                    className={`rounded-md px-2 py-1 text-xs font-semibold ${badgeClass(badge)}`}
                                                >
                                                    {badge}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                    {selectedAudio.audio_url && (
                                        <audio
                                            className="mt-3 w-full"
                                            controls
                                            src={selectedAudio.audio_url}
                                        >
                                            <track kind="captions" />
                                        </audio>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </section>

            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div className="grid gap-5">
                    <Field label="Question prompt" error={errors.question_text}>
                        <textarea
                            value={form.data.question_text}
                            onChange={(event) =>
                                form.setData(
                                    'question_text',
                                    event.target.value,
                                )
                            }
                            className="min-h-28 w-full rounded-md border border-slate-200 bg-white p-4 text-sm leading-7 dark:border-slate-800 dark:bg-slate-950"
                        />
                    </Field>

                    <Field label="Explanation" error={errors.explanation}>
                        <textarea
                            value={form.data.explanation}
                            onChange={(event) =>
                                form.setData('explanation', event.target.value)
                            }
                            className="min-h-28 w-full rounded-md border border-slate-200 bg-white p-4 text-sm leading-7 dark:border-slate-800 dark:bg-slate-950"
                        />
                    </Field>

                    {form.data.section_type === 'reading' && (
                        <Field
                            label="Evidence sentence"
                            error={errors.evidence_sentence}
                        >
                            <textarea
                                value={form.data.evidence_sentence}
                                onChange={(event) =>
                                    form.setData(
                                        'evidence_sentence',
                                        event.target.value,
                                    )
                                }
                                className="min-h-24 w-full rounded-md border border-slate-200 bg-white p-4 text-sm leading-7 dark:border-slate-800 dark:bg-slate-950"
                            />
                        </Field>
                    )}
                </div>
            </section>

            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-semibold">Options</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Use exactly four choices and one correct answer.
                        </p>
                    </div>
                    {errors.options && (
                        <p className="text-sm text-red-600">{errors.options}</p>
                    )}
                </div>

                <div className="mt-5 grid gap-4">
                    {form.data.options.map((option, index) => (
                        <div
                            key={option.label}
                            className="grid gap-3 rounded-md border border-slate-100 p-4 md:grid-cols-[auto_1fr_auto] md:items-start dark:border-slate-800"
                        >
                            <span className="inline-flex size-9 items-center justify-center rounded-md bg-slate-100 text-sm font-semibold dark:bg-slate-900">
                                {option.label}
                            </span>
                            <div>
                                <textarea
                                    value={option.text}
                                    onChange={(event) =>
                                        updateOption(
                                            index,
                                            'text',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-20 w-full rounded-md border border-slate-200 bg-white p-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                />
                                {errors[`options.${index}.text`] && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {errors[`options.${index}.text`]}
                                    </p>
                                )}
                            </div>
                            <label className="inline-flex items-center gap-2 text-sm font-medium">
                                <input
                                    type="radio"
                                    name="correct_answer"
                                    checked={option.is_correct}
                                    onChange={() =>
                                        setCorrectAnswer(option.label)
                                    }
                                />
                                Correct
                            </label>
                        </div>
                    ))}
                </div>
            </section>

            {warnings.length > 0 && (
                <section className="grid gap-2 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100">
                    {warnings.map((warning) => (
                        <p key={warning} className="flex items-start gap-2">
                            <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                            {warning}
                        </p>
                    ))}
                </section>
            )}

            {isReady && (
                <section className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100">
                    <span className="flex items-center gap-2">
                        <CheckCircle2 className="size-4" />
                        This question is ready for exam selection.
                    </span>
                </section>
            )}

            <div className="flex flex-wrap justify-end gap-3">
                <Link
                    href={cancelHref}
                    className="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                >
                    Cancel
                </Link>
                <Button type="submit" disabled={form.processing}>
                    <Save className="size-4" />
                    {mode === 'create' ? 'Create question' : 'Save changes'}
                </Button>
            </div>
        </form>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            {children}
            {error && <span className="text-sm text-red-600">{error}</span>}
        </label>
    );
}

function normalizeOptions(
    options: QuestionOptionInput[] | undefined,
): QuestionOptionInput[] {
    if (!options || options.length === 0) {
        return defaultValues.options;
    }

    return optionLabels.map((label, index) => {
        const option = options.find((item) => item.label === label);

        return {
            label,
            text: option?.text ?? '',
            is_correct: option?.is_correct ?? index === 0,
        };
    });
}

function qualityWarnings(
    data: QuestionFormData,
    selectedAudio?: AudioAssetOption,
): string[] {
    const warnings: string[] = [];
    const isActive = ['ready', 'published'].includes(data.status);
    const correctCount = data.options.filter(
        (option) => option.is_correct,
    ).length;

    if (!isActive) {
        return warnings;
    }

    if (!data.question_type) {
        warnings.push('Missing question type');
    }

    if (!data.difficulty) {
        warnings.push('Missing difficulty');
    }

    if (!data.skill_tag_id) {
        warnings.push('Missing skill tag');
    }

    if (!data.explanation.trim()) {
        warnings.push('Missing explanation');
    }

    if (data.section_type === 'reading' && !data.passage_id) {
        warnings.push('Reading question without passage');
    }

    if (data.section_type === 'reading' && !data.evidence_sentence.trim()) {
        warnings.push('Missing evidence sentence');
    }

    if (data.section_type === 'listening' && !data.audio_asset_id) {
        warnings.push('Listening question without audio');
    }

    if (
        data.section_type === 'listening' &&
        data.audio_asset_id &&
        !selectedAudio?.is_approved_for_exam
    ) {
        warnings.push(
            'Listening audio must be real, transcript reviewed, and approved',
        );
    }

    if (data.options.some((option) => !option.text.trim())) {
        warnings.push('Every option needs text');
    }

    if (correctCount !== 1) {
        warnings.push('Exactly one option must be correct');
    }

    return warnings;
}

export function labelize(value: string | null | undefined): string {
    return (value ?? '').replaceAll('_', ' ');
}

function badgeClass(badge: string): string {
    if (badge === 'Approved' || badge === 'Real Audio') {
        return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/25 dark:text-emerald-100';
    }

    if (badge === 'Transcript Reviewed') {
        return 'bg-blue-50 text-blue-700 dark:bg-blue-950/25 dark:text-blue-100';
    }

    return 'bg-amber-50 text-amber-700 dark:bg-amber-950/25 dark:text-amber-100';
}
