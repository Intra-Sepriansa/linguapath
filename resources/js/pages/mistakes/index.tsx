import { Head, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    AlertTriangle,
    BarChart3,
    Brain,
    CheckCircle2,
    Clock3,
    Filter,
    Flame,
    Gauge,
    Layers3,
    ListChecks,
    PlayCircle,
    RefreshCcw,
    Search,
    Sparkles,
    Target,
    TrendingUp,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { useMemo, useState } from 'react';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    index as mistakesIndex,
    review as reviewMistake,
} from '@/routes/mistakes';

type ReviewStatus = 'new' | 'reviewing' | 'fixed';
type FilterStatus = 'all' | ReviewStatus;
type ViewMode = 'queue' | 'patterns' | 'resolved';
type SortMode = 'newest' | 'oldest' | 'status' | 'type';
type MetricTone = 'amber' | 'cyan' | 'emerald' | 'indigo' | 'rose' | 'slate';

type Mistake = {
    id: number;
    section_type: string;
    mistake_type: string;
    question: string;
    user_answer: string | null;
    correct_answer: string | null;
    note: string | null;
    review_status: ReviewStatus;
    created_at: string;
};

type EnrichedMistake = Mistake & {
    searchText: string;
};

type Bucket = {
    key: string;
    label: string;
    count: number;
    percentage: number;
};

type Summary = {
    total: number;
    open: number;
    new: number;
    reviewing: number;
    fixed: number;
    fixedRate: number;
    topType: Bucket | null;
    topSection: Bucket | null;
    typeBuckets: Bucket[];
    sectionBuckets: Bucket[];
    statusBuckets: Bucket[];
};

const statusFilters: Array<{ value: FilterStatus; label: string }> = [
    { value: 'all', label: 'Semua' },
    { value: 'new', label: 'Baru' },
    { value: 'reviewing', label: 'Review' },
    { value: 'fixed', label: 'Selesai' },
];

const viewModes: Array<{
    value: ViewMode;
    label: string;
    icon: ComponentType<{ className?: string }>;
}> = [
    { value: 'queue', label: 'Queue', icon: ListChecks },
    { value: 'patterns', label: 'Patterns', icon: BarChart3 },
    { value: 'resolved', label: 'Fixed', icon: CheckCircle2 },
];

const sortOptions: Array<{ value: SortMode; label: string }> = [
    { value: 'newest', label: 'Terbaru' },
    { value: 'oldest', label: 'Terlama' },
    { value: 'status', label: 'Status' },
    { value: 'type', label: 'Tipe' },
];

const statusPriority: Record<ReviewStatus, number> = {
    new: 0,
    reviewing: 1,
    fixed: 2,
};

const statusMeta: Record<
    ReviewStatus,
    {
        label: string;
        shortLabel: string;
        icon: ComponentType<{ className?: string }>;
        badgeClass: string;
        dotClass: string;
        stepClass: string;
    }
> = {
    new: {
        label: 'Baru',
        shortLabel: 'New',
        icon: AlertTriangle,
        badgeClass:
            'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200',
        dotClass: 'bg-rose-500',
        stepClass: 'border-rose-200 bg-rose-50 text-rose-700',
    },
    reviewing: {
        label: 'Sedang direview',
        shortLabel: 'Review',
        icon: PlayCircle,
        badgeClass:
            'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
        dotClass: 'bg-amber-500',
        stepClass: 'border-amber-200 bg-amber-50 text-amber-700',
    },
    fixed: {
        label: 'Selesai',
        shortLabel: 'Fixed',
        icon: CheckCircle2,
        badgeClass:
            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
        dotClass: 'bg-emerald-500',
        stepClass: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    },
};

const metricToneClasses: Record<MetricTone, string> = {
    amber: 'border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-200',
    cyan: 'border-cyan-100 bg-cyan-50 text-cyan-700 dark:border-cyan-900 dark:bg-cyan-950/25 dark:text-cyan-200',
    emerald:
        'border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-200',
    indigo: 'border-indigo-100 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/25 dark:text-indigo-200',
    rose: 'border-rose-100 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-200',
    slate: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200',
};

export default function MistakesIndex({ mistakes }: { mistakes: Mistake[] }) {
    const [query, setQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<FilterStatus>('all');
    const [sectionFilter, setSectionFilter] = useState('all');
    const [typeFilter, setTypeFilter] = useState('all');
    const [sortMode, setSortMode] = useState<SortMode>('newest');
    const [viewMode, setViewMode] = useState<ViewMode>('queue');
    const [selectedMistakeId, setSelectedMistakeId] = useState<number | null>(
        mistakes[0]?.id ?? null,
    );

    const enrichedMistakes = useMemo(
        () =>
            mistakes.map((mistake) => ({
                ...mistake,
                searchText: [
                    mistake.section_type,
                    mistake.mistake_type,
                    mistake.question,
                    mistake.user_answer,
                    mistake.correct_answer,
                    mistake.note,
                    mistake.review_status,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase(),
            })),
        [mistakes],
    );

    const summary = useMemo(() => buildSummary(mistakes), [mistakes]);
    const sectionOptions = useMemo(
        () => buildOptions(mistakes.map((mistake) => mistake.section_type)),
        [mistakes],
    );
    const typeOptions = useMemo(
        () => buildOptions(mistakes.map((mistake) => mistake.mistake_type)),
        [mistakes],
    );

    const visibleMistakes = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        return enrichedMistakes
            .filter((mistake) => {
                const matchesQuery =
                    normalizedQuery === '' ||
                    mistake.searchText.includes(normalizedQuery);
                const matchesStatus =
                    statusFilter === 'all' ||
                    mistake.review_status === statusFilter;
                const matchesSection =
                    sectionFilter === 'all' ||
                    mistake.section_type === sectionFilter;
                const matchesType =
                    typeFilter === 'all' || mistake.mistake_type === typeFilter;

                return (
                    matchesQuery &&
                    matchesStatus &&
                    matchesSection &&
                    matchesType
                );
            })
            .sort((first, second) => compareMistakes(first, second, sortMode));
    }, [
        enrichedMistakes,
        query,
        sectionFilter,
        sortMode,
        statusFilter,
        typeFilter,
    ]);

    const activeMistake =
        visibleMistakes.find((mistake) => mistake.id === selectedMistakeId) ??
        visibleMistakes[0] ??
        mistakes[0] ??
        null;
    const resolvedMistakes = useMemo(
        () =>
            mistakes
                .filter((mistake) => mistake.review_status === 'fixed')
                .sort((first, second) => compareByDate(second, first)),
        [mistakes],
    );

    const markMistake = (mistake: Mistake, reviewStatus: ReviewStatus) => {
        setSelectedMistakeId(mistake.id);

        router.patch(
            reviewMistake.url(mistake.id),
            { review_status: reviewStatus },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return (
        <>
            <Head title="Mistake Journal" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="grid gap-6 xl:grid-cols-[1fr_0.38fr]">
                    <SpotlightCard className="p-6 md:p-8">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="inline-flex items-center gap-2 rounded-md bg-rose-100 px-3 py-1.5 text-sm font-semibold text-rose-800 dark:bg-rose-950 dark:text-rose-200">
                                    <Sparkles className="size-4" />
                                    Mistake Command Center
                                </div>
                                <h1 className="mt-5 text-4xl leading-tight font-semibold text-slate-950 md:text-5xl dark:text-white">
                                    {summary.open} mistake terbuka,{' '}
                                    {summary.fixedRate}% sudah terkunci.
                                </h1>
                                <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                                    Review jawaban salah berdasarkan status,
                                    skill, tipe kesalahan, dan pola paling
                                    sering muncul.
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3 lg:min-w-80 lg:grid-cols-1">
                                <Metric
                                    icon={Flame}
                                    label="Open"
                                    value={summary.open}
                                    tone={summary.open > 0 ? 'rose' : 'slate'}
                                />
                                <Metric
                                    icon={PlayCircle}
                                    label="Review"
                                    value={summary.reviewing}
                                    tone={
                                        summary.reviewing > 0
                                            ? 'amber'
                                            : 'slate'
                                    }
                                />
                                <Metric
                                    icon={CheckCircle2}
                                    label="Fixed"
                                    value={summary.fixed}
                                    tone="emerald"
                                />
                            </div>
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-indigo-600">
                                    Priority
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">
                                    {summary.topType?.label ?? 'Clear'}
                                </h2>
                            </div>
                            <Target className="size-6 text-indigo-500" />
                        </div>
                        <div className="mt-6 grid gap-4">
                            <FocusSignal
                                label="Tipe dominan"
                                value={summary.topType?.label ?? 'Tidak ada'}
                                count={summary.topType?.count ?? 0}
                                tone="rose"
                            />
                            <FocusSignal
                                label="Skill dominan"
                                value={summary.topSection?.label ?? 'Tidak ada'}
                                count={summary.topSection?.count ?? 0}
                                tone="indigo"
                            />
                            <FocusSignal
                                label="Closure"
                                value={`${summary.fixedRate}%`}
                                count={summary.fixed}
                                tone="emerald"
                            />
                        </div>
                    </SpotlightCard>
                </section>

                <section className="grid gap-4 lg:grid-cols-[1fr_auto]">
                    <SpotlightCard className="p-4">
                        <div className="grid gap-3 xl:grid-cols-[1fr_auto_auto_auto] xl:items-center">
                            <label className="relative block">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={query}
                                    onChange={(event) =>
                                        setQuery(event.target.value)
                                    }
                                    className="h-11 w-full rounded-md border border-slate-200 bg-white pr-3 pl-10 text-sm transition outline-none focus:border-primary focus:ring-3 focus:ring-primary/15 dark:border-slate-800 dark:bg-slate-950"
                                    placeholder="Cari question, answer, note, skill"
                                />
                            </label>

                            <SelectControl
                                label="Skill"
                                value={sectionFilter}
                                options={sectionOptions}
                                onChange={setSectionFilter}
                            />

                            <SelectControl
                                label="Tipe"
                                value={typeFilter}
                                options={typeOptions}
                                onChange={setTypeFilter}
                            />

                            <SelectControl
                                label="Urutkan"
                                value={sortMode}
                                options={sortOptions}
                                onChange={(value) =>
                                    setSortMode(value as SortMode)
                                }
                            />
                        </div>

                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            <Filter className="size-4 text-slate-400" />
                            {statusFilters.map((filter) => (
                                <FilterPill
                                    key={filter.value}
                                    active={statusFilter === filter.value}
                                    label={filter.label}
                                    onClick={() =>
                                        setStatusFilter(filter.value)
                                    }
                                />
                            ))}
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-2">
                        <div className="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-900">
                            {viewModes.map((item) => {
                                const Icon = item.icon;

                                return (
                                    <button
                                        key={item.value}
                                        type="button"
                                        onClick={() => setViewMode(item.value)}
                                        className={cn(
                                            'relative inline-flex h-11 items-center justify-center gap-2 rounded-md px-3 text-sm font-semibold transition',
                                            viewMode === item.value
                                                ? 'text-primary'
                                                : 'text-slate-500 hover:text-slate-950 dark:hover:text-white',
                                        )}
                                    >
                                        {viewMode === item.value && (
                                            <motion.span
                                                layoutId="mistake-view-mode"
                                                className="absolute inset-0 rounded-md bg-white shadow-sm dark:bg-slate-950"
                                                transition={{
                                                    duration: 0.22,
                                                }}
                                            />
                                        )}
                                        <span className="relative inline-flex items-center gap-2">
                                            <Icon className="size-4" />
                                            {item.label}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </SpotlightCard>
                </section>

                {mistakes.length > 0 ? (
                    <section className="grid gap-6 xl:grid-cols-[0.42fr_0.58fr]">
                        <MistakeQueue
                            mistakes={visibleMistakes}
                            activeMistakeId={activeMistake?.id ?? null}
                            onSelect={(mistake) => {
                                setSelectedMistakeId(mistake.id);
                                setViewMode('queue');
                            }}
                            onReview={markMistake}
                        />

                        <AnimatePresence mode="wait">
                            <motion.div
                                key={`${viewMode}-${activeMistake?.id ?? 'empty'}`}
                                initial={{ opacity: 0, y: 14, scale: 0.98 }}
                                animate={{ opacity: 1, y: 0, scale: 1 }}
                                exit={{ opacity: 0, y: -10, scale: 0.98 }}
                                transition={{ duration: 0.22 }}
                            >
                                {viewMode === 'queue' && activeMistake && (
                                    <MistakeDetail
                                        mistake={activeMistake}
                                        onReview={markMistake}
                                    />
                                )}

                                {viewMode === 'patterns' && (
                                    <PatternPanel summary={summary} />
                                )}

                                {viewMode === 'resolved' && (
                                    <ResolvedPanel
                                        mistakes={resolvedMistakes}
                                        onReview={markMistake}
                                    />
                                )}
                            </motion.div>
                        </AnimatePresence>
                    </section>
                ) : (
                    <EmptyState />
                )}
            </div>
        </>
    );
}

function MistakeQueue({
    mistakes,
    activeMistakeId,
    onSelect,
    onReview,
}: {
    mistakes: EnrichedMistake[];
    activeMistakeId: number | null;
    onSelect: (mistake: EnrichedMistake) => void;
    onReview: (mistake: Mistake, reviewStatus: ReviewStatus) => void;
}) {
    return (
        <SpotlightCard className="p-5">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-indigo-600">
                        Review Queue
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        {mistakes.length} item
                    </h2>
                </div>
                <Gauge className="size-5 text-slate-400" />
            </div>

            {mistakes.length > 0 ? (
                <div className="mt-5 grid max-h-[46rem] gap-3 overflow-y-auto pr-1">
                    {mistakes.map((mistake, index) => (
                        <motion.article
                            key={mistake.id}
                            initial={{ opacity: 0, y: 8 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: Math.min(index * 0.02, 0.18) }}
                            className={cn(
                                'rounded-lg border p-4 transition',
                                activeMistakeId === mistake.id
                                    ? 'border-primary bg-indigo-50 shadow-sm shadow-indigo-500/10 dark:bg-indigo-950/30'
                                    : 'border-slate-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-indigo-950/20',
                            )}
                        >
                            <button
                                type="button"
                                onClick={() => onSelect(mistake)}
                                className="w-full text-left"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                                            {formatLabel(mistake.section_type)}{' '}
                                            /{' '}
                                            {formatLabel(mistake.mistake_type)}
                                        </p>
                                        <h3 className="mt-2 line-clamp-2 font-semibold text-slate-950 dark:text-white">
                                            {mistake.question}
                                        </h3>
                                    </div>
                                    <StatusBadge
                                        status={mistake.review_status}
                                    />
                                </div>
                                <div className="mt-4 grid gap-2 text-sm md:grid-cols-2">
                                    <AnswerSnippet
                                        label="Kamu"
                                        value={mistake.user_answer}
                                        tone="rose"
                                    />
                                    <AnswerSnippet
                                        label="Benar"
                                        value={mistake.correct_answer}
                                        tone="emerald"
                                    />
                                </div>
                            </button>
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <p className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                    <Clock3 className="size-3.5" />
                                    {formatDate(mistake.created_at)}
                                </p>
                                {mistake.review_status !== 'fixed' ? (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            onReview(mistake, 'fixed')
                                        }
                                    >
                                        <CheckCircle2 className="size-4" />
                                        Fixed
                                    </Button>
                                ) : (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => onReview(mistake, 'new')}
                                    >
                                        <RefreshCcw className="size-4" />
                                        Reopen
                                    </Button>
                                )}
                            </div>
                        </motion.article>
                    ))}
                </div>
            ) : (
                <div className="mt-5 grid min-h-64 place-items-center rounded-lg border border-dashed border-slate-200 text-center dark:border-slate-800">
                    <div>
                        <Search className="mx-auto size-8 text-slate-300" />
                        <p className="mt-3 font-semibold">
                            Tidak ada item pada filter ini.
                        </p>
                    </div>
                </div>
            )}
        </SpotlightCard>
    );
}

function MistakeDetail({
    mistake,
    onReview,
}: {
    mistake: Mistake;
    onReview: (mistake: Mistake, reviewStatus: ReviewStatus) => void;
}) {
    return (
        <SpotlightCard className="p-6 md:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="max-w-3xl">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge status={mistake.review_status} />
                        <span className="rounded-md bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-600 capitalize dark:bg-slate-900 dark:text-slate-300">
                            {formatLabel(mistake.section_type)}
                        </span>
                        <span className="rounded-md bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-600 capitalize dark:bg-slate-900 dark:text-slate-300">
                            {formatLabel(mistake.mistake_type)}
                        </span>
                    </div>
                    <h2 className="mt-5 text-3xl leading-tight font-semibold text-slate-950 md:text-4xl dark:text-white">
                        {mistake.question}
                    </h2>
                </div>
                <div className="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/25 dark:text-indigo-200">
                    <Brain className="size-6" />
                </div>
            </div>

            <ReviewProgress status={mistake.review_status} />

            <div className="mt-7 grid gap-4 lg:grid-cols-2">
                <AnswerPanel
                    label="Jawaban kamu"
                    value={mistake.user_answer}
                    tone="rose"
                />
                <AnswerPanel
                    label="Jawaban benar"
                    value={mistake.correct_answer}
                    tone="emerald"
                />
            </div>

            <div className="mt-4 rounded-lg border border-amber-100 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/25">
                <p className="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    Catatan
                </p>
                <p className="mt-2 leading-7 text-amber-950 dark:text-amber-100">
                    {mistake.note ?? 'Belum ada catatan untuk mistake ini.'}
                </p>
            </div>

            <div className="mt-6 grid gap-3 md:grid-cols-3">
                <InfoMetric
                    icon={Layers3}
                    label="Skill"
                    value={formatLabel(mistake.section_type)}
                    tone="indigo"
                />
                <InfoMetric
                    icon={Target}
                    label="Tipe"
                    value={formatLabel(mistake.mistake_type)}
                    tone="rose"
                />
                <InfoMetric
                    icon={Clock3}
                    label="Masuk"
                    value={formatDate(mistake.created_at)}
                    tone="cyan"
                />
            </div>

            <div className="mt-7 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                <p className="text-sm font-medium text-slate-500">
                    Status: {statusMeta[mistake.review_status].label}
                </p>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        disabled={mistake.review_status === 'new'}
                        onClick={() => onReview(mistake, 'new')}
                    >
                        <RefreshCcw className="size-4" />
                        Baru
                    </Button>
                    <Button
                        variant="secondary"
                        disabled={mistake.review_status === 'reviewing'}
                        onClick={() => onReview(mistake, 'reviewing')}
                    >
                        <PlayCircle className="size-4" />
                        Review
                    </Button>
                    <Button
                        disabled={mistake.review_status === 'fixed'}
                        onClick={() => onReview(mistake, 'fixed')}
                    >
                        <CheckCircle2 className="size-4" />
                        Fixed
                    </Button>
                </div>
            </div>
        </SpotlightCard>
    );
}

function PatternPanel({ summary }: { summary: Summary }) {
    const openBuckets = summary.statusBuckets.filter(
        (bucket) => bucket.key !== 'fixed',
    );

    return (
        <div className="grid gap-6">
            <SpotlightCard className="p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-medium text-indigo-600">
                            Error Intelligence
                        </p>
                        <h2 className="mt-2 text-3xl font-semibold">
                            {summary.topType?.label ?? 'Belum ada pola'}
                        </h2>
                    </div>
                    <TrendingUp className="size-6 text-emerald-500" />
                </div>

                <div className="mt-7 grid gap-6 lg:grid-cols-2">
                    <BucketChart
                        title="Mistake Type"
                        buckets={summary.typeBuckets}
                        tone="rose"
                    />
                    <BucketChart
                        title="Skill"
                        buckets={summary.sectionBuckets}
                        tone="indigo"
                    />
                </div>
            </SpotlightCard>

            <SpotlightCard className="p-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <p className="text-sm font-medium text-indigo-600">
                            Review Load
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold">
                            {summary.open} belum selesai
                        </h2>
                    </div>
                    <Flame className="size-6 text-rose-500" />
                </div>

                <div className="mt-6 grid gap-3 md:grid-cols-3">
                    {openBuckets.map((bucket) => (
                        <InfoMetric
                            key={bucket.key}
                            icon={
                                bucket.key === 'new'
                                    ? AlertTriangle
                                    : PlayCircle
                            }
                            label={bucket.label}
                            value={bucket.count}
                            tone={bucket.key === 'new' ? 'rose' : 'amber'}
                        />
                    ))}
                    <InfoMetric
                        icon={CheckCircle2}
                        label="Fixed"
                        value={`${summary.fixedRate}%`}
                        tone="emerald"
                    />
                </div>
            </SpotlightCard>
        </div>
    );
}

function ResolvedPanel({
    mistakes,
    onReview,
}: {
    mistakes: Mistake[];
    onReview: (mistake: Mistake, reviewStatus: ReviewStatus) => void;
}) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-indigo-600">
                        Fixed Archive
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        {mistakes.length} selesai
                    </h2>
                </div>
                <CheckCircle2 className="size-6 text-emerald-500" />
            </div>

            {mistakes.length > 0 ? (
                <div className="mt-6 grid gap-3">
                    {mistakes.map((mistake, index) => (
                        <motion.article
                            key={mistake.id}
                            initial={{ opacity: 0, y: 8 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: Math.min(index * 0.02, 0.18) }}
                            className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <StatusBadge
                                            status={mistake.review_status}
                                        />
                                        <span className="text-xs font-semibold text-slate-500 uppercase">
                                            {formatLabel(mistake.section_type)}{' '}
                                            /{' '}
                                            {formatLabel(mistake.mistake_type)}
                                        </span>
                                    </div>
                                    <h3 className="mt-3 font-semibold text-slate-950 dark:text-white">
                                        {mistake.question}
                                    </h3>
                                    <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        {mistake.correct_answer ??
                                            'Tidak ada jawaban benar.'}
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => onReview(mistake, 'new')}
                                >
                                    <RefreshCcw className="size-4" />
                                    Reopen
                                </Button>
                            </div>
                        </motion.article>
                    ))}
                </div>
            ) : (
                <div className="mt-6 grid min-h-64 place-items-center rounded-lg border border-dashed border-slate-200 text-center dark:border-slate-800">
                    <div>
                        <CheckCircle2 className="mx-auto size-9 text-slate-300" />
                        <p className="mt-3 font-semibold">
                            Belum ada mistake yang fixed.
                        </p>
                    </div>
                </div>
            )}
        </SpotlightCard>
    );
}

function ReviewProgress({ status }: { status: ReviewStatus }) {
    const progress = ((statusPriority[status] + 1) / 3) * 100;

    return (
        <div className="mt-7">
            <div className="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-900">
                <motion.div
                    initial={{ width: 0 }}
                    animate={{ width: `${progress}%` }}
                    transition={{ duration: 0.35 }}
                    className="h-full rounded-full bg-primary"
                />
            </div>
            <div className="mt-4 grid gap-2 md:grid-cols-3">
                {(['new', 'reviewing', 'fixed'] as ReviewStatus[]).map(
                    (item) => {
                        const meta = statusMeta[item];
                        const Icon = meta.icon;
                        const active =
                            statusPriority[item] <= statusPriority[status];

                        return (
                            <div
                                key={item}
                                className={cn(
                                    'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition',
                                    active
                                        ? meta.stepClass
                                        : 'border-slate-200 bg-white text-slate-400 dark:border-slate-800 dark:bg-slate-950',
                                )}
                            >
                                <Icon className="size-4" />
                                {meta.shortLabel}
                            </div>
                        );
                    },
                )}
            </div>
        </div>
    );
}

function BucketChart({
    title,
    buckets,
    tone,
}: {
    title: string;
    buckets: Bucket[];
    tone: MetricTone;
}) {
    return (
        <div>
            <h3 className="font-semibold">{title}</h3>
            <div className="mt-4 grid gap-4">
                {buckets.length > 0 ? (
                    buckets.map((bucket) => (
                        <div key={bucket.key}>
                            <div className="flex items-center justify-between gap-4 text-sm">
                                <span className="font-semibold">
                                    {bucket.label}
                                </span>
                                <span className="text-slate-500">
                                    {bucket.count}
                                </span>
                            </div>
                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-900">
                                <motion.div
                                    initial={{ width: 0 }}
                                    whileInView={{
                                        width: `${bucket.percentage}%`,
                                    }}
                                    viewport={{ once: true }}
                                    transition={{ duration: 0.35 }}
                                    className={cn(
                                        'h-full rounded-full',
                                        tone === 'rose' && 'bg-rose-500',
                                        tone === 'indigo' && 'bg-indigo-500',
                                        tone === 'emerald' && 'bg-emerald-500',
                                        tone === 'amber' && 'bg-amber-500',
                                        tone === 'cyan' && 'bg-cyan-500',
                                        tone === 'slate' && 'bg-slate-500',
                                    )}
                                />
                            </div>
                        </div>
                    ))
                ) : (
                    <p className="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-slate-800">
                        Data belum tersedia.
                    </p>
                )}
            </div>
        </div>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
    value: number | string;
    tone: MetricTone;
}) {
    return (
        <div
            className={cn(
                'flex items-center justify-between gap-4 rounded-lg border p-4',
                metricToneClasses[tone],
            )}
        >
            <div>
                <p className="text-sm font-semibold">{label}</p>
                <p className="mt-1 text-2xl font-semibold">{value}</p>
            </div>
            <Icon className="size-5" />
        </div>
    );
}

function InfoMetric({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
    value: number | string;
    tone: MetricTone;
}) {
    return (
        <div className={cn('rounded-lg border p-4', metricToneClasses[tone])}>
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-semibold">{label}</p>
                <Icon className="size-4" />
            </div>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </div>
    );
}

function FocusSignal({
    label,
    value,
    count,
    tone,
}: {
    label: string;
    value: string;
    count: number;
    tone: MetricTone;
}) {
    return (
        <div className="flex items-center justify-between gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0 dark:border-slate-800">
            <div className="min-w-0">
                <p className="text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                    {label}
                </p>
                <p className="mt-1 truncate font-semibold text-slate-950 dark:text-white">
                    {value}
                </p>
            </div>
            <span
                className={cn(
                    'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-2 text-sm font-semibold',
                    metricToneClasses[tone],
                )}
            >
                {count}
            </span>
        </div>
    );
}

function FilterPill({
    active,
    label,
    onClick,
}: {
    active: boolean;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'h-9 rounded-md border px-3 text-sm font-semibold transition',
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300',
            )}
        >
            {label}
        </button>
    );
}

function SelectControl({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: Array<{ value: string; label: string }>;
    onChange: (value: string) => void;
}) {
    return (
        <label className="grid gap-1.5">
            <span className="text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                {label}
            </span>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="h-11 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium transition outline-none focus:border-primary focus:ring-3 focus:ring-primary/15 dark:border-slate-800 dark:bg-slate-950"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}

function StatusBadge({ status }: { status: ReviewStatus }) {
    const meta = statusMeta[status];
    const Icon = meta.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-semibold',
                meta.badgeClass,
            )}
        >
            <span className={cn('size-1.5 rounded-full', meta.dotClass)} />
            <Icon className="size-3.5" />
            {meta.label}
        </span>
    );
}

function AnswerSnippet({
    label,
    value,
    tone,
}: {
    label: string;
    value: string | null;
    tone: 'emerald' | 'rose';
}) {
    return (
        <div
            className={cn(
                'rounded-lg border px-3 py-2',
                tone === 'rose'
                    ? 'border-rose-100 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-100'
                    : 'border-emerald-100 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100',
            )}
        >
            <p className="text-xs font-semibold tracking-[0.12em] uppercase opacity-70">
                {label}
            </p>
            <p className="mt-1 line-clamp-1 font-semibold">
                {value ?? 'Kosong'}
            </p>
        </div>
    );
}

function AnswerPanel({
    label,
    value,
    tone,
}: {
    label: string;
    value: string | null;
    tone: 'emerald' | 'rose';
}) {
    return (
        <div
            className={cn(
                'min-h-40 rounded-lg border p-5',
                tone === 'rose'
                    ? 'border-rose-100 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/25'
                    : 'border-emerald-100 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/25',
            )}
        >
            <p
                className={cn(
                    'text-sm font-semibold',
                    tone === 'rose'
                        ? 'text-rose-700 dark:text-rose-200'
                        : 'text-emerald-700 dark:text-emerald-200',
                )}
            >
                {label}
            </p>
            <p className="mt-3 text-2xl leading-snug font-semibold text-slate-950 dark:text-white">
                {value ?? 'Kosong'}
            </p>
        </div>
    );
}

function EmptyState() {
    return (
        <SpotlightCard className="grid min-h-96 place-items-center p-8 text-center">
            <div>
                <CheckCircle2 className="mx-auto size-12 text-emerald-400" />
                <h2 className="mt-4 text-2xl font-semibold">
                    Mistake journal masih kosong.
                </h2>
                <p className="mt-2 text-slate-600 dark:text-slate-300">
                    Selesaikan practice untuk membangun daftar review.
                </p>
            </div>
        </SpotlightCard>
    );
}

function buildSummary(mistakes: Mistake[]): Summary {
    const total = mistakes.length;
    const statusBuckets = buildBuckets(
        mistakes,
        (mistake) => mistake.review_status,
        total,
    );
    const typeBuckets = buildBuckets(
        mistakes,
        (mistake) => mistake.mistake_type,
        total,
    );
    const sectionBuckets = buildBuckets(
        mistakes,
        (mistake) => mistake.section_type,
        total,
    );
    const fixed = mistakes.filter(
        (mistake) => mistake.review_status === 'fixed',
    ).length;
    const reviewing = mistakes.filter(
        (mistake) => mistake.review_status === 'reviewing',
    ).length;
    const fresh = mistakes.filter(
        (mistake) => mistake.review_status === 'new',
    ).length;

    return {
        total,
        open: total - fixed,
        new: fresh,
        reviewing,
        fixed,
        fixedRate: total === 0 ? 0 : Math.round((fixed / total) * 100),
        topType: typeBuckets[0] ?? null,
        topSection: sectionBuckets[0] ?? null,
        typeBuckets,
        sectionBuckets,
        statusBuckets,
    };
}

function buildBuckets(
    mistakes: Mistake[],
    selector: (mistake: Mistake) => string,
    total: number,
): Bucket[] {
    const counts = new Map<string, number>();

    mistakes.forEach((mistake) => {
        const key = selector(mistake);
        counts.set(key, (counts.get(key) ?? 0) + 1);
    });

    return [...counts.entries()]
        .map(([key, count]) => ({
            key,
            label: formatLabel(key),
            count,
            percentage: total === 0 ? 0 : Math.round((count / total) * 100),
        }))
        .sort(
            (first, second) =>
                second.count - first.count ||
                first.label.localeCompare(second.label),
        );
}

function buildOptions(
    values: string[],
): Array<{ value: string; label: string }> {
    const uniqueValues = [...new Set(values)].sort((first, second) =>
        formatLabel(first).localeCompare(formatLabel(second)),
    );

    return [
        { value: 'all', label: 'Semua' },
        ...uniqueValues.map((value) => ({
            value,
            label: formatLabel(value),
        })),
    ];
}

function compareMistakes(
    first: Mistake,
    second: Mistake,
    sortMode: SortMode,
): number {
    if (sortMode === 'oldest') {
        return compareByDate(first, second);
    }

    if (sortMode === 'status') {
        return (
            statusPriority[first.review_status] -
                statusPriority[second.review_status] ||
            compareByDate(second, first)
        );
    }

    if (sortMode === 'type') {
        return (
            formatLabel(first.mistake_type).localeCompare(
                formatLabel(second.mistake_type),
            ) || compareByDate(second, first)
        );
    }

    return compareByDate(second, first);
}

function compareByDate(first: Mistake, second: Mistake): number {
    return (
        new Date(first.created_at).getTime() -
        new Date(second.created_at).getTime()
    );
}

function formatLabel(value: string): string {
    return value
        .replace(/[_-]/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDate(value: string): string {
    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

MistakesIndex.layout = {
    breadcrumbs: [{ title: 'Mistakes', href: mistakesIndex() }],
};
