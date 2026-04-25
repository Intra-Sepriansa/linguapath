import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    AudioLines,
    BookOpenCheck,
    Database,
    FileText,
    Gauge,
    LibraryBig,
    Tags,
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

type DashboardProps = {
    metrics: Metrics;
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

export default function AdminDashboard({
    metrics,
    qualityFlags,
}: DashboardProps) {
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
