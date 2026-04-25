import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Archive,
    ArrowLeft,
    CheckCircle2,
    FileQuestion,
    Pencil,
    Trash2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { labelize } from './form';

type QuestionOption = {
    id: number;
    label: string;
    text: string;
    is_correct: boolean;
};

type AdminQuestion = {
    id: number;
    section_type: string;
    question_type: string | null;
    difficulty: string | null;
    status: string;
    exam_eligible: boolean;
    question_text: string;
    explanation: string | null;
    evidence_sentence: string | null;
    quality_warnings: string[];
    practice_answers_count: number;
    exam_answers_count: number;
    passage: {
        id: number;
        title: string;
        topic: string | null;
        word_count: number;
        status: string;
        body?: string;
    } | null;
    audio_asset: {
        id: number;
        title: string;
        status: string;
        is_real_audio: boolean;
        duration_seconds: number;
        audio_url: string | null;
        transcript?: string;
    } | null;
    skill_tag: { id: number; name: string; domain: string } | null;
    options: QuestionOption[];
};

export default function AdminQuestionsShow({
    question,
}: {
    question: AdminQuestion;
}) {
    const used =
        question.practice_answers_count > 0 || question.exam_answers_count > 0;

    const deleteQuestion = () => {
        const action = used ? 'Archive' : 'Delete';

        if (!window.confirm(`${action} this question?`)) {
            return;
        }

        router.delete(`/admin/questions/${question.id}`);
    };

    return (
        <>
            <Head title="Question Preview" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <Link
                                href="/admin/questions"
                                className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                            >
                                <ArrowLeft className="size-4" />
                                Questions
                            </Link>
                            <p className="mt-5 text-sm font-semibold text-indigo-600">
                                Question preview
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold">
                                {labelize(question.section_type)} question
                            </h1>
                            <p className="mt-2 text-sm text-slate-500">
                                {labelize(question.question_type) || 'No type'}{' '}
                                ·{' '}
                                {labelize(question.difficulty) ||
                                    'No difficulty'}{' '}
                                · {labelize(question.status)}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={`/admin/questions/${question.id}/edit`}
                                className="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Link>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={deleteQuestion}
                            >
                                {used ? (
                                    <Archive className="size-4" />
                                ) : (
                                    <Trash2 className="size-4" />
                                )}
                                {used ? 'Archive' : 'Delete'}
                            </Button>
                        </div>
                    </div>
                </section>

                <section
                    className={`rounded-lg border p-4 text-sm ${
                        question.quality_warnings.length === 0
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100'
                            : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100'
                    }`}
                >
                    {question.quality_warnings.length === 0 ? (
                        <p className="flex items-center gap-2 font-semibold">
                            <CheckCircle2 className="size-4" />
                            Ready for exam selection.
                        </p>
                    ) : (
                        <div className="grid gap-2">
                            {question.quality_warnings.map((warning) => (
                                <p
                                    key={warning}
                                    className="flex items-start gap-2"
                                >
                                    <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                    {warning}
                                </p>
                            ))}
                        </div>
                    )}
                </section>

                <section className="grid gap-6 lg:grid-cols-[1fr_0.42fr]">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex items-center gap-2">
                            <FileQuestion className="size-5 text-indigo-500" />
                            <h2 className="text-xl font-semibold">Prompt</h2>
                        </div>
                        <p className="mt-5 rounded-md bg-slate-50 p-5 text-sm leading-7 whitespace-pre-wrap dark:bg-slate-900">
                            {question.question_text}
                        </p>

                        <div className="mt-6 grid gap-3">
                            {question.options.map((option) => (
                                <div
                                    key={option.id}
                                    className={`rounded-md border p-4 text-sm ${
                                        option.is_correct
                                            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/25'
                                            : 'border-slate-100 dark:border-slate-800'
                                    }`}
                                >
                                    <div className="flex items-start gap-3">
                                        <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-md bg-white font-semibold dark:bg-slate-950">
                                            {option.label}
                                        </span>
                                        <div>
                                            <p>{option.text}</p>
                                            {option.is_correct && (
                                                <p className="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-100">
                                                    Correct answer
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {question.explanation && (
                            <div className="mt-6">
                                <h3 className="font-semibold">Explanation</h3>
                                <p className="mt-2 rounded-md bg-slate-50 p-4 text-sm leading-7 whitespace-pre-wrap dark:bg-slate-900">
                                    {question.explanation}
                                </p>
                            </div>
                        )}

                        {question.evidence_sentence && (
                            <div className="mt-6">
                                <h3 className="font-semibold">
                                    Evidence sentence
                                </h3>
                                <p className="mt-2 rounded-md bg-slate-50 p-4 text-sm leading-7 whitespace-pre-wrap dark:bg-slate-900">
                                    {question.evidence_sentence}
                                </p>
                            </div>
                        )}
                    </article>

                    <aside className="grid gap-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <h2 className="text-xl font-semibold">Metadata</h2>
                            <dl className="mt-4 grid gap-3 text-sm">
                                <Meta label="Status" value={question.status} />
                                <Meta
                                    label="Skill tag"
                                    value={question.skill_tag?.name ?? 'None'}
                                />
                                <Meta
                                    label="Exam eligible"
                                    value={
                                        question.exam_eligible ? 'Yes' : 'No'
                                    }
                                />
                                <Meta
                                    label="Practice history"
                                    value={`${question.practice_answers_count} answers`}
                                />
                                <Meta
                                    label="Exam history"
                                    value={`${question.exam_answers_count} answers`}
                                />
                            </dl>
                        </section>

                        {question.passage && (
                            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                <h2 className="text-xl font-semibold">
                                    Reading passage
                                </h2>
                                <p className="mt-2 text-sm text-slate-500">
                                    {question.passage.title} ·{' '}
                                    {question.passage.word_count} words
                                </p>
                                {question.passage.body && (
                                    <p className="mt-4 max-h-72 overflow-auto rounded-md bg-slate-50 p-4 text-sm leading-7 whitespace-pre-wrap dark:bg-slate-900">
                                        {question.passage.body}
                                    </p>
                                )}
                            </section>
                        )}

                        {question.audio_asset && (
                            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                <h2 className="text-xl font-semibold">
                                    Listening audio
                                </h2>
                                <p className="mt-2 text-sm text-slate-500">
                                    {question.audio_asset.title} ·{' '}
                                    {question.audio_asset.is_real_audio
                                        ? 'Real audio'
                                        : 'Transcript fallback'}
                                </p>
                                {question.audio_asset.audio_url && (
                                    <audio
                                        className="mt-4 w-full"
                                        controls
                                        src={question.audio_asset.audio_url}
                                    >
                                        <track kind="captions" />
                                    </audio>
                                )}
                                {question.audio_asset.transcript && (
                                    <p className="mt-4 max-h-52 overflow-auto rounded-md bg-slate-50 p-4 text-sm leading-7 whitespace-pre-wrap dark:bg-slate-900">
                                        {question.audio_asset.transcript}
                                    </p>
                                )}
                            </section>
                        )}
                    </aside>
                </section>
            </div>
        </>
    );
}

function Meta({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4">
            <dt className="text-slate-500">{label}</dt>
            <dd className="font-semibold capitalize">{labelize(value)}</dd>
        </div>
    );
}
