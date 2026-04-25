import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    AudioLines,
    BookOpenCheck,
    CheckCircle2,
    ClipboardList,
    Database,
    FileText,
    Gauge,
    LibraryBig,
    ShieldCheck,
    Tags,
    XCircle,
} from 'lucide-react';

type Metrics = {
    lessons: number;
    questions: number;
    audio: number;
    passages: number;
    vocabulary: number;
    skill_tags: number;
    missing_audio: number;
    short_passages: number;
    missing_explanation: number;
    missing_skill_tag: number;
    missing_difficulty: number;
};

type QualityItem = {
    id: number;
    label: string;
    detail?: string;
};

type ReadinessSection = {
    label: string;
    ready_count: number;
    raw_ready_count: number;
    capped_ready_count: number;
    target: number;
    percent: number;
    ready: boolean;
    href: string;
    issue_count: number;
};

type ReadinessIssue = {
    label: string;
    count: number;
    href: string;
};

type ExamReadiness = {
    sections: {
        listening: ReadinessSection;
        structure: ReadinessSection;
        reading: ReadinessSection;
    };
    full_exam_ready: boolean;
    total_ready: number;
    total_capped_ready: number;
    total_raw_ready: number;
    total_target: number;
    blocked_sections: ReadinessSection[];
    primary_blocker_message: string | null;
    issues: Record<string, ReadinessIssue>;
};

type DashboardProps = {
    metrics: Metrics;
    examReadiness: ExamReadiness;
    qualityFlags: {
        listening_without_real_audio: QualityItem[];
        short_passages: QualityItem[];
        question_distribution: Array<{ label: string; total: number }>;
    };
};

const metricCards = [
    ['Lessons', 'lessons', BookOpenCheck],
    ['Questions', 'questions', FileText],
    ['Audio assets', 'audio', AudioLines],
    ['Passages', 'passages', LibraryBig],
    ['Vocabulary', 'vocabulary', Database],
    ['Skill tags', 'skill_tags', Tags],
] as const;

const readinessCards = [
    ['listening', AudioLines],
    ['structure', ShieldCheck],
    ['reading', LibraryBig],
] as const;

export default function AdminDashboard({
    metrics,
    examReadiness,
    qualityFlags,
}: DashboardProps) {
    const fullExamPercent = Math.min(
        100,
        Math.round(
            (examReadiness.total_capped_ready /
                Math.max(examReadiness.total_target, 1)) *
                100,
        ),
    );

    return (
        <>
            <Head title="Admin Dashboard" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-indigo-600">
                                Content CMS
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold">
                                Admin dashboard
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Monitor TOEFL content quality before expanding
                                the full CRUD surface.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href="/admin/questions"
                                className="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                <FileText className="mr-2 size-4" />
                                Manage questions
                            </Link>
                            <Link
                                href="/admin/reading-passages"
                                className="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                            >
                                <LibraryBig className="mr-2 size-4" />
                                Manage passages
                            </Link>
                            <Link
                                href="/admin/audio-assets"
                                className="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                            >
                                <AudioLines className="mr-2 size-4" />
                                Upload audio
                            </Link>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                    {metricCards.map(([label, key, Icon]) => (
                        <article
                            key={key}
                            className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                        >
                            <div className="flex items-center justify-between">
                                <Icon className="size-5 text-indigo-500" />
                                <span className="text-2xl font-semibold">
                                    {metrics[key]}
                                </span>
                            </div>
                            <p className="mt-3 text-sm text-slate-500">
                                {label}
                            </p>
                        </article>
                    ))}
                </section>

                <section className="flex flex-col gap-4">
                    <div className="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-indigo-600">
                                Exam readiness
                            </p>
                            <h2 className="text-2xl font-semibold">
                                TOEFL ITP full simulation gate
                            </h2>
                        </div>
                        <Link
                            href="/admin/questions"
                            className="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                        >
                            <ClipboardList className="mr-2 size-4" />
                            Review question bank
                        </Link>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {readinessCards.map(([key, Icon]) => (
                            <ReadinessCard
                                key={key}
                                section={examReadiness.sections[key]}
                                Icon={Icon}
                            />
                        ))}
                        <article
                            className={`rounded-lg border p-4 shadow-sm ${
                                examReadiness.full_exam_ready
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100'
                                    : 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-100'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-3">
                                {examReadiness.full_exam_ready ? (
                                    <CheckCircle2 className="size-5" />
                                ) : (
                                    <XCircle className="size-5" />
                                )}
                                <span className="rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold dark:bg-black/20">
                                    {examReadiness.full_exam_ready
                                        ? 'Ready'
                                        : 'Blocked'}
                                </span>
                            </div>
                            <div className="mt-4">
                                <p className="text-sm font-medium">Full Exam</p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {examReadiness.total_capped_ready}/
                                    {examReadiness.total_target}
                                </p>
                                <p className="mt-1 text-xs opacity-80">
                                    {examReadiness.total_raw_ready} available in
                                    ready pool
                                </p>
                            </div>
                            <div className="mt-4 h-2 rounded-full bg-white/70 dark:bg-black/20">
                                <div
                                    className={`h-2 rounded-full ${
                                        examReadiness.full_exam_ready
                                            ? 'bg-emerald-500'
                                            : 'bg-rose-500'
                                    }`}
                                    style={{ width: `${fullExamPercent}%` }}
                                />
                            </div>
                            {examReadiness.primary_blocker_message && (
                                <p className="mt-3 text-xs font-medium">
                                    {examReadiness.primary_blocker_message}
                                </p>
                            )}
                            <Link
                                href="/admin/questions"
                                className="mt-4 inline-flex text-sm font-semibold underline-offset-4 hover:underline"
                            >
                                Open CMS
                            </Link>
                        </article>
                    </div>
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="size-5 text-amber-500" />
                        <h2 className="text-xl font-semibold">
                            Readiness issue summary
                        </h2>
                    </div>
                    <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {Object.entries(examReadiness.issues).map(
                            ([key, issue]) => (
                                <Link
                                    key={key}
                                    href={issue.href}
                                    className="rounded-md border border-slate-200 p-4 text-sm transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-semibold">
                                            {issue.label}
                                        </span>
                                        <span
                                            className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                issue.count > 0
                                                    ? 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100'
                                                    : 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100'
                                            }`}
                                        >
                                            {issue.count}
                                        </span>
                                    </div>
                                </Link>
                            ),
                        )}
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <QualityMetric
                        label="Missing real audio"
                        value={metrics.missing_audio}
                    />
                    <QualityMetric
                        label="Short passages"
                        value={metrics.short_passages}
                    />
                    <QualityMetric
                        label="Missing explanations"
                        value={metrics.missing_explanation}
                    />
                    <QualityMetric
                        label="Missing skill tags"
                        value={metrics.missing_skill_tag}
                    />
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <QualityList
                        title="Listening without uploaded audio"
                        items={qualityFlags.listening_without_real_audio}
                    />
                    <QualityList
                        title="Short reading passages"
                        items={qualityFlags.short_passages}
                    />
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-center gap-2">
                        <Gauge className="size-5 text-indigo-500" />
                        <h2 className="text-xl font-semibold">
                            Question distribution
                        </h2>
                    </div>
                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        {qualityFlags.question_distribution.map((item) => (
                            <div
                                key={item.label}
                                className="rounded-md bg-slate-50 p-4 text-sm dark:bg-slate-900"
                            >
                                <p className="font-semibold capitalize">
                                    {item.label}
                                </p>
                                <p className="mt-1 text-slate-500">
                                    {item.total} questions
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

function ReadinessCard({
    section,
    Icon,
}: {
    section: ReadinessSection;
    Icon: typeof AudioLines;
}) {
    return (
        <Link
            href={section.href}
            className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-900"
        >
            <div className="flex items-center justify-between gap-3">
                <Icon className="size-5 text-indigo-500" />
                <span
                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                        section.ready
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100'
                            : 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100'
                    }`}
                >
                    {section.ready ? 'Ready' : 'Blocked'}
                </span>
            </div>
            <div className="mt-4">
                <p className="text-sm font-medium text-slate-500">
                    {section.label} ready
                </p>
                <p className="mt-1 text-2xl font-semibold">
                    {section.capped_ready_count}/{section.target}
                </p>
                <p className="mt-1 text-xs text-slate-500">
                    {section.raw_ready_count} available
                </p>
            </div>
            <div className="mt-4 h-2 rounded-full bg-slate-100 dark:bg-slate-800">
                <div
                    className={`h-2 rounded-full ${
                        section.ready ? 'bg-emerald-500' : 'bg-indigo-500'
                    }`}
                    style={{ width: `${section.percent}%` }}
                />
            </div>
            <p className="mt-3 text-xs text-slate-500">
                {section.issue_count} readiness blockers
            </p>
        </Link>
    );
}

function QualityMetric({ label, value }: { label: string; value: number }) {
    return (
        <article className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100">
            <div className="flex items-center justify-between">
                <AlertTriangle className="size-5" />
                <span className="text-2xl font-semibold">{value}</span>
            </div>
            <p className="mt-3 text-sm font-medium">{label}</p>
        </article>
    );
}

function QualityList({
    title,
    items,
}: {
    title: string;
    items: QualityItem[];
}) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <h2 className="text-xl font-semibold">{title}</h2>
            <div className="mt-4 grid gap-3">
                {items.length === 0 && (
                    <div className="rounded-md bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950/25 dark:text-emerald-100">
                        No active flags.
                    </div>
                )}
                {items.map((item) => (
                    <div
                        key={item.id}
                        className="rounded-md bg-slate-50 p-4 text-sm dark:bg-slate-900"
                    >
                        <p className="font-semibold">{item.label}</p>
                        {item.detail && (
                            <p className="mt-1 text-slate-500">{item.detail}</p>
                        )}
                    </div>
                ))}
            </div>
        </article>
    );
}
