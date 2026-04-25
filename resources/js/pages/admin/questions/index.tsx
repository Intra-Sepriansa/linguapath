import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Archive,
    Eye,
    FileQuestion,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import type { QuestionFormOptions } from './form';
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
    quality_warnings: string[];
    options_count: number;
    correct_options_count: number;
    practice_answers_count: number;
    exam_answers_count: number;
    passage: { id: number; title: string } | null;
    audio_asset: { id: number; title: string; is_real_audio: boolean } | null;
    skill_tag: { id: number; name: string; domain: string } | null;
    options: QuestionOption[];
};

type PaginatedQuestions = {
    data: AdminQuestion[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Filters = {
    search: string;
    section_type: string;
    question_type: string;
    difficulty: string;
    status: string;
    skill_tag_id: string;
    quality: string;
    sort: string;
};

type Stats = {
    total: number;
    ready: number;
    published: number;
    archived: number;
    invalid_options: number;
    missing_audio: number;
    missing_evidence: number;
};

export default function AdminQuestionsIndex({
    questions,
    filters,
    options,
    stats,
}: {
    questions: PaginatedQuestions;
    filters: Filters;
    options: QuestionFormOptions;
    stats: Stats;
}) {
    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        router.get(
            '/admin/questions',
            {
                search: formData.get('search')?.toString() ?? '',
                section_type: formData.get('section_type')?.toString() ?? '',
                question_type: formData.get('question_type')?.toString() ?? '',
                difficulty: formData.get('difficulty')?.toString() ?? '',
                status: formData.get('status')?.toString() ?? '',
                skill_tag_id: formData.get('skill_tag_id')?.toString() ?? '',
                quality: formData.get('quality')?.toString() ?? '',
                sort: formData.get('sort')?.toString() ?? 'newest',
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    const deleteQuestion = (question: AdminQuestion) => {
        const used =
            question.practice_answers_count > 0 ||
            question.exam_answers_count > 0;
        const action = used ? 'Archive' : 'Delete';

        if (!window.confirm(`${action} this question?`)) {
            return;
        }

        router.delete(`/admin/questions/${question.id}`, {
            preserveScroll: true,
        });
    };

    const allQuestionTypes = Object.values(options.questionTypes).flat();

    return (
        <>
            <Head title="Admin Questions" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-indigo-600">
                                Question CMS
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold">
                                Questions
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Build TOEFL-style question sets with four answer
                                options, one correct answer, evidence, and
                                linked media.
                            </p>
                        </div>
                        <Link
                            href="/admin/questions/create"
                            className="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            <Plus className="mr-2 size-4" />
                            New question
                        </Link>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-4 xl:grid-cols-7">
                    <StatCard label="Total" value={stats.total} />
                    <StatCard label="Ready" value={stats.ready} />
                    <StatCard label="Published" value={stats.published} />
                    <StatCard label="Archived" value={stats.archived} />
                    <StatCard
                        label="Invalid options"
                        value={stats.invalid_options}
                    />
                    <StatCard
                        label="Missing audio"
                        value={stats.missing_audio}
                    />
                    <StatCard
                        label="Missing evidence"
                        value={stats.missing_evidence}
                    />
                </section>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[1.2fr_0.7fr_0.8fr_0.7fr_0.7fr_0.8fr_0.8fr_auto] dark:border-slate-800 dark:bg-slate-950"
                >
                    <label className="grid gap-2 text-sm font-medium">
                        Search
                        <input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Prompt or explanation"
                            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                        />
                    </label>
                    <Select
                        label="Section"
                        name="section_type"
                        value={filters.section_type}
                        options={options.sections.map((item) => item.value)}
                    />
                    <Select
                        label="Type"
                        name="question_type"
                        value={filters.question_type}
                        options={allQuestionTypes.map((item) => item.value)}
                    />
                    <Select
                        label="Difficulty"
                        name="difficulty"
                        value={filters.difficulty}
                        options={options.difficulties}
                    />
                    <Select
                        label="Status"
                        name="status"
                        value={filters.status}
                        options={options.statuses}
                    />
                    <label className="grid gap-2 text-sm font-medium">
                        Skill tag
                        <select
                            name="skill_tag_id"
                            defaultValue={filters.skill_tag_id}
                            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                        >
                            <option value="">All</option>
                            {options.skillTags.map((tag) => (
                                <option key={tag.id} value={tag.id}>
                                    {tag.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <Select
                        label="Quality"
                        name="quality"
                        value={filters.quality}
                        options={options.qualityFilters ?? []}
                    />
                    <Button type="submit" className="self-end">
                        <Search className="size-4" />
                        Apply
                    </Button>
                </form>

                <section className="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="hidden grid-cols-[1.35fr_0.5fr_0.55fr_0.65fr_0.8fr_0.45fr] gap-4 border-b border-slate-200 px-5 py-3 text-xs font-semibold tracking-wide text-slate-500 uppercase lg:grid dark:border-slate-800">
                        <span>Question</span>
                        <span>Section</span>
                        <span>Status</span>
                        <span>Linked content</span>
                        <span>Quality</span>
                        <span className="text-right">Actions</span>
                    </div>

                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {questions.data.length === 0 && (
                            <div className="p-6 text-sm text-slate-500">
                                No questions match the current filters.
                            </div>
                        )}

                        {questions.data.map((question) => (
                            <article
                                key={question.id}
                                className="grid gap-4 px-5 py-4 lg:grid-cols-[1.35fr_0.5fr_0.55fr_0.65fr_0.8fr_0.45fr] lg:items-start"
                            >
                                <div>
                                    <div className="flex items-start gap-2">
                                        <FileQuestion className="mt-1 size-4 text-indigo-500" />
                                        <div>
                                            <h2 className="font-semibold">
                                                {question.question_text}
                                            </h2>
                                            <p className="mt-1 text-sm text-slate-500">
                                                {labelize(
                                                    question.question_type,
                                                ) || 'No type'}{' '}
                                                ·{' '}
                                                {labelize(
                                                    question.difficulty,
                                                ) || 'No difficulty'}{' '}
                                                ·{' '}
                                                {question.skill_tag?.name ??
                                                    'No skill tag'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <span className="w-fit rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold capitalize dark:bg-slate-900">
                                    {labelize(question.section_type)}
                                </span>
                                <span className="w-fit rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold capitalize dark:bg-slate-900">
                                    {labelize(question.status)}
                                </span>
                                <LinkedContent question={question} />
                                <QualityBadges
                                    warnings={question.quality_warnings}
                                />
                                <div className="flex justify-start gap-2 lg:justify-end">
                                    <IconLink
                                        href={`/admin/questions/${question.id}`}
                                        label="Preview"
                                    >
                                        <Eye className="size-4" />
                                    </IconLink>
                                    <IconLink
                                        href={`/admin/questions/${question.id}/edit`}
                                        label="Edit"
                                    >
                                        <Pencil className="size-4" />
                                    </IconLink>
                                    <button
                                        type="button"
                                        onClick={() => deleteQuestion(question)}
                                        className="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-red-50 hover:text-red-700 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-red-950/25"
                                        title={
                                            question.practice_answers_count >
                                                0 ||
                                            question.exam_answers_count > 0
                                                ? 'Archive'
                                                : 'Delete'
                                        }
                                    >
                                        {question.practice_answers_count > 0 ||
                                        question.exam_answers_count > 0 ? (
                                            <Archive className="size-4" />
                                        ) : (
                                            <Trash2 className="size-4" />
                                        )}
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>

                <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                    <p>
                        Showing {questions.from ?? 0}-{questions.to ?? 0} of{' '}
                        {questions.total}
                    </p>
                    <div className="flex gap-2">
                        {questions.prev_page_url && (
                            <Link
                                href={questions.prev_page_url}
                                className="rounded-md border border-slate-200 px-3 py-2 font-semibold dark:border-slate-800"
                            >
                                Previous
                            </Link>
                        )}
                        {questions.next_page_url && (
                            <Link
                                href={questions.next_page_url}
                                className="rounded-md border border-slate-200 px-3 py-2 font-semibold dark:border-slate-800"
                            >
                                Next
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function LinkedContent({ question }: { question: AdminQuestion }) {
    if (question.section_type === 'reading') {
        return (
            <p className="text-sm text-slate-500">
                {question.passage?.title ?? 'No passage'}
            </p>
        );
    }

    if (question.section_type === 'listening') {
        return (
            <p className="text-sm text-slate-500">
                {question.audio_asset?.title ?? 'No audio'}
            </p>
        );
    }

    return <p className="text-sm text-slate-500">Structure only</p>;
}

function QualityBadges({ warnings }: { warnings: string[] }) {
    if (warnings.length === 0) {
        return (
            <span className="w-fit rounded-md bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/25 dark:text-emerald-100">
                Ready
            </span>
        );
    }

    return (
        <div className="grid gap-1">
            {warnings.slice(0, 3).map((warning) => (
                <span
                    key={warning}
                    className="inline-flex w-fit items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/25 dark:text-amber-100"
                >
                    <AlertTriangle className="size-3" />
                    {warning}
                </span>
            ))}
            {warnings.length > 3 && (
                <span className="text-xs text-slate-500">
                    +{warnings.length - 3} more
                </span>
            )}
        </div>
    );
}

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <p className="text-2xl font-semibold">{value}</p>
            <p className="mt-2 text-sm text-slate-500">{label}</p>
        </article>
    );
}

function Select({
    label,
    name,
    value,
    options,
}: {
    label: string;
    name: string;
    value: string;
    options: string[];
}) {
    return (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            <select
                name={name}
                defaultValue={value}
                className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
            >
                <option value="">All</option>
                {options.map((option) => (
                    <option key={option} value={option}>
                        {labelize(option)}
                    </option>
                ))}
            </select>
        </label>
    );
}

function IconLink({
    href,
    label,
    children,
}: {
    href: string;
    label: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            title={label}
            className="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
        >
            {children}
        </Link>
    );
}
