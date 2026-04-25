import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    ArrowRight,
    BarChart3,
    Brain,
    CalendarDays,
    CheckCircle2,
    CirclePlay,
    Clock3,
    Flame,
    Gauge,
    GraduationCap,
    LineChart as LineChartIcon,
    ListChecks,
    PieChart as PieChartIcon,
    Sparkles,
    Target,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ElementType } from 'react';
import {
    Area,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ComposedChart,
    Pie,
    PieChart,
    PolarAngleAxis,
    PolarGrid,
    Radar,
    RadarChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { CountUp } from '@/components/reactbits/count-up';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { index as analyticsIndex } from '@/routes/analytics';
import { index as mistakesIndex } from '@/routes/mistakes';
import { setup as practiceSetup } from '@/routes/practice';
import { index as vocabularyIndex } from '@/routes/vocabulary';

type Tone = 'amber' | 'cyan' | 'emerald' | 'indigo' | 'rose' | 'slate';
type Timeframe = '7d' | '14d' | '30d';
type ChartMetric = 'accuracy' | 'minutes' | 'questions';

type ActivityPoint = {
    date: string;
    day: string;
    iso_date: string;
    accuracy: number;
    minutes: number;
    questions: number;
    lessons: number;
    sessions: number;
    intensity: number;
};

type SkillBreakdown = {
    key: string;
    skill: string;
    score: number;
    attempts: number;
    latest_score: number | null;
    momentum: number;
    questions: number;
    minutes: number;
    mistakes: number;
    mistake_rate: number;
    status: string;
};

type AnalyticsProps = {
    analytics: {
        readiness: {
            score: number;
            level: string;
            trend: number;
            trend_label: string;
        };
        summary: {
            active_days: number;
            total_minutes: number;
            total_questions: number;
            average_accuracy: number;
            weekly_minutes: number;
            weekly_questions: number;
            weekly_accuracy: number;
            weekly_minutes_trend: number;
            weekly_accuracy_trend: number;
            consistency_score: number;
            study_efficiency: number;
            best_day: {
                label: string;
                minutes: number;
                accuracy: number;
                questions: number;
            } | null;
        };
        projection: {
            estimated_toefl: string;
            target_score: number;
            target_gap: number;
            daily_goal_minutes: number;
            exam_date: string | null;
        };
        activity: ActivityPoint[];
        weekly_accuracy: Array<{ date: string; accuracy: number }>;
        skill_breakdown: SkillBreakdown[];
        mistake_types: Array<{
            name: string;
            key: string;
            value: number;
            percentage: number;
            tone: Tone;
        }>;
        mistake_sections: Array<{ key: string; section: string; open: number }>;
        study_minutes: Array<{ date: string; minutes: number }>;
        vocabulary: {
            total: number;
            learning: number;
            mastered: number;
            weak: number;
            review_later: number;
            review_ready: number;
            mastery_rate: number;
            retention_rate: number;
            reviewed_this_week: number;
            average_reviews: number;
        };
        recommendations: Array<{
            kind: string;
            title: string;
            signal: string;
            description: string;
            action: string;
            tone: Tone;
            priority: string;
        }>;
    };
};

const timeframeDays: Record<Timeframe, number> = {
    '7d': 7,
    '14d': 14,
    '30d': 30,
};

const metricConfig: Record<
    ChartMetric,
    {
        label: string;
        dataKey: ChartMetric;
        color: string;
        soft: string;
        suffix: string;
    }
> = {
    accuracy: {
        label: 'Accuracy',
        dataKey: 'accuracy',
        color: '#4f46e5',
        soft: '#e0e7ff',
        suffix: '%',
    },
    minutes: {
        label: 'Study minutes',
        dataKey: 'minutes',
        color: '#0f766e',
        soft: '#ccfbf1',
        suffix: 'm',
    },
    questions: {
        label: 'Questions',
        dataKey: 'questions',
        color: '#d97706',
        soft: '#fef3c7',
        suffix: '',
    },
};

const toneStyles: Record<
    Tone,
    {
        panel: string;
        icon: string;
        soft: string;
        text: string;
        bar: string;
        chart: string;
    }
> = {
    amber: {
        panel: 'border-amber-200 bg-amber-50/80 dark:border-amber-900/70 dark:bg-amber-950/20',
        icon: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        soft: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        text: 'text-amber-700 dark:text-amber-200',
        bar: 'bg-amber-500',
        chart: '#f59e0b',
    },
    cyan: {
        panel: 'border-cyan-200 bg-cyan-50/80 dark:border-cyan-900/70 dark:bg-cyan-950/20',
        icon: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
        soft: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
        text: 'text-cyan-700 dark:text-cyan-200',
        bar: 'bg-cyan-500',
        chart: '#06b6d4',
    },
    emerald: {
        panel: 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900/70 dark:bg-emerald-950/20',
        icon: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        soft: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        text: 'text-emerald-700 dark:text-emerald-200',
        bar: 'bg-emerald-500',
        chart: '#10b981',
    },
    indigo: {
        panel: 'border-indigo-200 bg-indigo-50/80 dark:border-indigo-900/70 dark:bg-indigo-950/20',
        icon: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        soft: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        text: 'text-indigo-700 dark:text-indigo-200',
        bar: 'bg-indigo-500',
        chart: '#4f46e5',
    },
    rose: {
        panel: 'border-rose-200 bg-rose-50/80 dark:border-rose-900/70 dark:bg-rose-950/20',
        icon: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        soft: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        text: 'text-rose-700 dark:text-rose-200',
        bar: 'bg-rose-500',
        chart: '#f43f5e',
    },
    slate: {
        panel: 'border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/40',
        icon: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
        soft: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
        text: 'text-slate-700 dark:text-slate-200',
        bar: 'bg-slate-500',
        chart: '#64748b',
    },
};

export default function AnalyticsIndex({ analytics }: AnalyticsProps) {
    const [timeframe, setTimeframe] = useState<Timeframe>('30d');
    const [chartMetric, setChartMetric] = useState<ChartMetric>('accuracy');
    const [activeSkillKey, setActiveSkillKey] = useState(
        analytics.skill_breakdown[0]?.key ?? 'structure',
    );

    const selectedMetric = metricConfig[chartMetric];
    const visibleActivity = useMemo(
        () => analytics.activity.slice(-timeframeDays[timeframe]),
        [analytics.activity, timeframe],
    );
    const selectedSkill =
        analytics.skill_breakdown.find(
            (skill) => skill.key === activeSkillKey,
        ) ?? analytics.skill_breakdown[0];
    const radarData = analytics.skill_breakdown.map((skill) => ({
        subject: skill.skill,
        score: skill.score,
    }));
    const TrendIcon = analytics.readiness.trend < 0 ? TrendingDown : TrendingUp;

    return (
        <>
            <Head title="Analytics" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
                    <SpotlightCard className="p-6 md:p-8">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-primary dark:bg-indigo-950 dark:text-indigo-200">
                                    <Sparkles className="size-4" />
                                    Analytics Command Center
                                </div>
                                <h1 className="mt-5 text-4xl leading-tight font-semibold text-slate-950 md:text-5xl dark:text-white">
                                    Performance signals with a clear next move.
                                </h1>
                                <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                                    Readiness, workload, accuracy, vocabulary,
                                    and mistake patterns are connected into a
                                    single operating view for TOEFL ITP prep.
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    asChild
                                    className="h-11 bg-primary px-5 shadow-sm shadow-indigo-500/20 hover:bg-primary/90"
                                >
                                    <Link href={practiceSetup()}>
                                        <CirclePlay className="size-4" />
                                        Practice now
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="h-11 px-5"
                                >
                                    <Link href={mistakesIndex()}>
                                        Review signals
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <div className="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <SignalPill
                                icon={Gauge}
                                label="Readiness"
                                value={`${analytics.readiness.score}%`}
                                detail={analytics.readiness.level}
                            />
                            <SignalPill
                                icon={GraduationCap}
                                label="TOEFL band"
                                value={analytics.projection.estimated_toefl}
                                detail={`${analytics.projection.target_gap} pts gap`}
                            />
                            <SignalPill
                                icon={Flame}
                                label="Consistency"
                                value={`${analytics.summary.consistency_score}%`}
                                detail={`${analytics.summary.active_days}/30 active days`}
                            />
                            <SignalPill
                                icon={Target}
                                label="Weekly load"
                                value={`${analytics.summary.weekly_minutes}m`}
                                detail={`${analytics.projection.daily_goal_minutes}m daily goal`}
                            />
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="grid content-between gap-7 p-6 md:p-8">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    Readiness index
                                </p>
                                <h2 className="mt-1 text-2xl font-semibold">
                                    {analytics.readiness.level}
                                </h2>
                            </div>
                            <div
                                className={cn(
                                    'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold',
                                    analytics.readiness.trend < 0
                                        ? toneStyles.rose.soft
                                        : toneStyles.emerald.soft,
                                )}
                            >
                                <TrendIcon className="size-4" />
                                {analytics.readiness.trend_label}
                            </div>
                        </div>

                        <ReadinessGauge score={analytics.readiness.score} />

                        <div className="grid grid-cols-2 gap-3">
                            <MiniMetric
                                label="Accuracy"
                                value={`${analytics.summary.weekly_accuracy}%`}
                                trend={analytics.summary.weekly_accuracy_trend}
                            />
                            <MiniMetric
                                label="Load"
                                value={`${analytics.summary.weekly_minutes}m`}
                                trend={analytics.summary.weekly_minutes_trend}
                            />
                        </div>
                    </SpotlightCard>
                </section>

                <motion.section
                    initial="hidden"
                    animate="show"
                    variants={{
                        hidden: { opacity: 0 },
                        show: {
                            opacity: 1,
                            transition: { staggerChildren: 0.06 },
                        },
                    }}
                    className="grid gap-6 md:grid-cols-2 xl:grid-cols-4"
                >
                    <MetricCard
                        icon={CalendarDays}
                        label="Active Days"
                        value={String(analytics.summary.active_days)}
                        detail="Last 30 days"
                        tone="cyan"
                    />
                    <MetricCard
                        icon={Clock3}
                        label="Total Minutes"
                        value={`${analytics.summary.total_minutes}m`}
                        detail={`${analytics.summary.study_efficiency} questions per 30m`}
                        tone="indigo"
                    />
                    <MetricCard
                        icon={Brain}
                        label="Average Accuracy"
                        value={`${analytics.summary.average_accuracy}%`}
                        detail={`${analytics.summary.total_questions} questions logged`}
                        tone="emerald"
                    />
                    <MetricCard
                        icon={CheckCircle2}
                        label="Best Day"
                        value={analytics.summary.best_day?.label ?? 'No data'}
                        detail={
                            analytics.summary.best_day
                                ? `${analytics.summary.best_day.minutes}m, ${analytics.summary.best_day.accuracy}%`
                                : 'Start a session'
                        }
                        tone="amber"
                    />
                </motion.section>

                <section className="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
                    <SpotlightCard className="p-5 md:p-6">
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    Performance timeline
                                </p>
                                <h2 className="mt-1 text-2xl font-semibold">
                                    {selectedMetric.label} over {timeframe}
                                </h2>
                            </div>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <SegmentedControl
                                    value={timeframe}
                                    options={[
                                        { label: '7d', value: '7d' },
                                        { label: '14d', value: '14d' },
                                        { label: '30d', value: '30d' },
                                    ]}
                                    onChange={setTimeframe}
                                />
                                <SegmentedControl
                                    value={chartMetric}
                                    options={[
                                        {
                                            label: 'Accuracy',
                                            value: 'accuracy',
                                        },
                                        { label: 'Minutes', value: 'minutes' },
                                        {
                                            label: 'Questions',
                                            value: 'questions',
                                        },
                                    ]}
                                    onChange={setChartMetric}
                                />
                            </div>
                        </div>

                        <div className="mt-6 h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <ComposedChart
                                    data={visibleActivity}
                                    margin={{
                                        top: 10,
                                        right: 16,
                                        left: -16,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="#e2e8f0"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="date"
                                        tickLine={false}
                                        axisLine={false}
                                        tick={{ fontSize: 12 }}
                                    />
                                    <YAxis
                                        domain={
                                            chartMetric === 'accuracy'
                                                ? [0, 100]
                                                : undefined
                                        }
                                        tickLine={false}
                                        axisLine={false}
                                        tick={{ fontSize: 12 }}
                                    />
                                    <Tooltip
                                        cursor={{
                                            fill: 'rgba(79,70,229,0.08)',
                                        }}
                                        contentStyle={{
                                            borderRadius: 8,
                                            borderColor: '#e2e8f0',
                                            boxShadow:
                                                '0 16px 40px rgba(15,23,42,0.12)',
                                        }}
                                        formatter={(value) => [
                                            `${value}${selectedMetric.suffix}`,
                                            selectedMetric.label,
                                        ]}
                                    />
                                    <Bar
                                        dataKey="intensity"
                                        fill={selectedMetric.soft}
                                        radius={[6, 6, 0, 0]}
                                        barSize={18}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey={selectedMetric.dataKey}
                                        stroke={selectedMetric.color}
                                        strokeWidth={3}
                                        fill={selectedMetric.color}
                                        fillOpacity={0.12}
                                        dot={{ r: 3 }}
                                        activeDot={{ r: 6 }}
                                    />
                                </ComposedChart>
                            </ResponsiveContainer>
                        </div>
                    </SpotlightCard>

                    <ConsistencyPanel activity={analytics.activity} />
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                    <SpotlightCard className="p-5 md:p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    Skill radar
                                </p>
                                <h2 className="mt-1 text-2xl font-semibold">
                                    Section balance
                                </h2>
                            </div>
                            <div className="rounded-md bg-indigo-100 p-2 text-primary dark:bg-indigo-950 dark:text-indigo-200">
                                <LineChartIcon className="size-5" />
                            </div>
                        </div>
                        <div className="mt-5 h-72">
                            <ResponsiveContainer width="100%" height="100%">
                                <RadarChart data={radarData}>
                                    <PolarGrid stroke="#cbd5e1" />
                                    <PolarAngleAxis
                                        dataKey="subject"
                                        tick={{ fontSize: 12 }}
                                    />
                                    <Radar
                                        dataKey="score"
                                        stroke="#4f46e5"
                                        fill="#4f46e5"
                                        fillOpacity={0.18}
                                    />
                                    <Tooltip />
                                </RadarChart>
                            </ResponsiveContainer>
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-5 md:p-6">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    Skill intelligence
                                </p>
                                <h2 className="mt-1 text-2xl font-semibold">
                                    Diagnose the next section drill
                                </h2>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {analytics.skill_breakdown.map((skill) => (
                                    <button
                                        key={skill.key}
                                        type="button"
                                        onClick={() =>
                                            setActiveSkillKey(skill.key)
                                        }
                                        className={cn(
                                            'rounded-md border px-3 py-2 text-sm font-semibold transition',
                                            activeSkillKey === skill.key
                                                ? 'border-indigo-200 bg-indigo-600 text-white shadow-sm shadow-indigo-500/20'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-200 hover:text-primary dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300',
                                        )}
                                    >
                                        {skill.skill}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {selectedSkill ? (
                            <div className="mt-6 grid gap-5 lg:grid-cols-[0.65fr_1fr]">
                                <div className="grid place-items-center rounded-lg border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900/40">
                                    <div className="text-center">
                                        <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                            {selectedSkill.skill}
                                        </p>
                                        <div className="mt-3 text-5xl font-semibold text-primary">
                                            <CountUp
                                                value={selectedSkill.score}
                                                suffix="%"
                                            />
                                        </div>
                                        <div className="mt-3 inline-flex rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm dark:bg-slate-950 dark:text-slate-200">
                                            {selectedSkill.status}
                                        </div>
                                    </div>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <SkillSignal
                                        label="Latest score"
                                        value={
                                            selectedSkill.latest_score
                                                ? `${selectedSkill.latest_score}%`
                                                : 'No session'
                                        }
                                        detail={signed(selectedSkill.momentum)}
                                    />
                                    <SkillSignal
                                        label="Attempts"
                                        value={String(selectedSkill.attempts)}
                                        detail={`${selectedSkill.questions} questions`}
                                    />
                                    <SkillSignal
                                        label="Study load"
                                        value={`${selectedSkill.minutes}m`}
                                        detail="Finished practice time"
                                    />
                                    <SkillSignal
                                        label="Mistake rate"
                                        value={`${selectedSkill.mistake_rate}%`}
                                        detail={`${selectedSkill.mistakes} logged errors`}
                                    />
                                </div>
                            </div>
                        ) : null}
                    </SpotlightCard>
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <MistakeIntelligence analytics={analytics} />
                    <RecommendationPanel
                        recommendations={analytics.recommendations}
                    />
                </section>

                <section className="grid gap-6 lg:grid-cols-3">
                    <VocabularyPanel vocabulary={analytics.vocabulary} />
                    <StudyMinutePanel minutes={analytics.study_minutes} />
                    <ProjectionPanel projection={analytics.projection} />
                </section>
            </div>
        </>
    );
}

function ReadinessGauge({ score }: { score: number }) {
    return (
        <div className="grid place-items-center">
            <motion.div
                initial={{ rotate: -18, scale: 0.96 }}
                animate={{ rotate: 0, scale: 1 }}
                transition={{ type: 'spring', stiffness: 120, damping: 16 }}
                className="grid size-56 place-items-center rounded-full"
                style={{
                    background: `conic-gradient(#4f46e5 ${score * 3.6}deg, #e2e8f0 0deg)`,
                }}
            >
                <div className="grid size-44 place-items-center rounded-full bg-white shadow-inner dark:bg-slate-950">
                    <div className="text-center">
                        <div className="text-5xl font-semibold text-primary">
                            <CountUp value={score} suffix="%" />
                        </div>
                        <p className="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                            exam readiness
                        </p>
                    </div>
                </div>
            </motion.div>
        </div>
    );
}

function SignalPill({
    icon: Icon,
    label,
    value,
    detail,
}: {
    icon: ElementType;
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <div className="flex items-center gap-3">
                <div className="rounded-md bg-indigo-100 p-2 text-primary dark:bg-indigo-950 dark:text-indigo-200">
                    <Icon className="size-4" />
                </div>
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-500 dark:text-slate-400">
                        {label}
                    </p>
                    <p className="truncate text-lg font-semibold text-slate-950 dark:text-white">
                        {value}
                    </p>
                </div>
            </div>
            <p className="mt-3 truncate text-sm text-slate-500 dark:text-slate-400">
                {detail}
            </p>
        </div>
    );
}

function MiniMetric({
    label,
    value,
    trend,
}: {
    label: string;
    value: string;
    trend: number;
}) {
    const TrendIcon = trend < 0 ? TrendingDown : TrendingUp;

    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <p className="text-sm text-slate-500 dark:text-slate-400">
                {label}
            </p>
            <p className="mt-1 text-2xl font-semibold">{value}</p>
            <div
                className={cn(
                    'mt-3 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold',
                    trend < 0 ? toneStyles.rose.soft : toneStyles.emerald.soft,
                )}
            >
                <TrendIcon className="size-3" />
                {signed(trend)}
            </div>
        </div>
    );
}

function MetricCard({
    icon: Icon,
    label,
    value,
    detail,
    tone,
}: {
    icon: ElementType;
    label: string;
    value: string;
    detail: string;
    tone: Tone;
}) {
    return (
        <motion.div
            variants={{
                hidden: { opacity: 0, y: 14 },
                show: { opacity: 1, y: 0 },
            }}
        >
            <SpotlightCard className="h-full p-5">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            {label}
                        </p>
                        <p className="mt-2 text-3xl font-semibold text-slate-950 dark:text-white">
                            {value}
                        </p>
                    </div>
                    <div
                        className={cn('rounded-md p-2', toneStyles[tone].icon)}
                    >
                        <Icon className="size-5" />
                    </div>
                </div>
                <p className="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {detail}
                </p>
            </SpotlightCard>
        </motion.div>
    );
}

function SegmentedControl<T extends string>({
    value,
    options,
    onChange,
}: {
    value: T;
    options: Array<{ label: string; value: T }>;
    onChange: (value: T) => void;
}) {
    return (
        <div className="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    className={cn(
                        'relative rounded-md px-3 py-2 text-sm font-semibold transition',
                        value === option.value
                            ? 'text-white'
                            : 'text-slate-600 hover:text-primary dark:text-slate-300',
                    )}
                >
                    {value === option.value ? (
                        <motion.span
                            layoutId={`segment-${options.map((item) => item.value).join('-')}`}
                            className="absolute inset-0 rounded-md bg-primary"
                            transition={{
                                type: 'spring',
                                stiffness: 360,
                                damping: 30,
                            }}
                        />
                    ) : null}
                    <span className="relative">{option.label}</span>
                </button>
            ))}
        </div>
    );
}

function ConsistencyPanel({ activity }: { activity: ActivityPoint[] }) {
    const lastSeven = activity.slice(-7);
    const activeDays = activity.filter(
        (day) => day.minutes > 0 || day.questions > 0 || day.sessions > 0,
    ).length;

    return (
        <SpotlightCard className="p-5 md:p-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Consistency heatmap
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        {activeDays} active days
                    </h2>
                </div>
                <div className="rounded-md bg-cyan-100 p-2 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200">
                    <BarChart3 className="size-5" />
                </div>
            </div>

            <div className="mt-6 grid grid-cols-10 gap-2">
                {activity.map((day) => (
                    <motion.div
                        key={day.iso_date}
                        whileHover={{ y: -3, scale: 1.05 }}
                        title={`${day.date}: ${day.minutes}m, ${day.accuracy}%`}
                        className="h-9 rounded-md border border-slate-200 dark:border-slate-800"
                        style={{
                            backgroundColor:
                                day.intensity === 0
                                    ? 'rgba(226,232,240,0.6)'
                                    : `rgba(79,70,229,${0.18 + day.intensity / 130})`,
                        }}
                    />
                ))}
            </div>

            <div className="mt-6 grid gap-3">
                {lastSeven.map((day) => (
                    <div
                        key={day.iso_date}
                        className="grid grid-cols-[3rem_1fr_3.5rem] items-center gap-3 text-sm"
                    >
                        <span className="font-semibold text-slate-500 dark:text-slate-400">
                            {day.day}
                        </span>
                        <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <motion.div
                                initial={{ width: 0 }}
                                animate={{ width: `${day.intensity}%` }}
                                transition={{ duration: 0.5 }}
                                className="h-full rounded-full bg-indigo-500"
                            />
                        </div>
                        <span className="text-right font-semibold">
                            {day.minutes}m
                        </span>
                    </div>
                ))}
            </div>
        </SpotlightCard>
    );
}

function SkillSignal({
    label,
    value,
    detail,
}: {
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
            <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                {label}
            </p>
            <p className="mt-2 text-2xl font-semibold">{value}</p>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {detail}
            </p>
        </div>
    );
}

function MistakeIntelligence({
    analytics,
}: {
    analytics: AnalyticsProps['analytics'];
}) {
    const pieData = analytics.mistake_types.length
        ? analytics.mistake_types
        : [
              {
                  name: 'None',
                  key: 'none',
                  value: 1,
                  percentage: 100,
                  tone: 'slate' as Tone,
              },
          ];

    return (
        <SpotlightCard className="p-5 md:p-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Mistake intelligence
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Pattern priority
                    </h2>
                </div>
                <Button asChild variant="outline" size="sm">
                    <Link href={mistakesIndex()}>
                        Review
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            </div>

            <div className="mt-6 grid gap-5 lg:grid-cols-[0.7fr_1fr]">
                <div className="h-64">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={pieData}
                                dataKey="value"
                                nameKey="name"
                                innerRadius={54}
                                outerRadius={92}
                                paddingAngle={3}
                            >
                                {pieData.map((entry) => (
                                    <Cell
                                        key={entry.key}
                                        fill={toneStyles[entry.tone].chart}
                                    />
                                ))}
                            </Pie>
                            <Tooltip />
                        </PieChart>
                    </ResponsiveContainer>
                </div>

                <div className="grid content-center gap-3">
                    {analytics.mistake_types.length ? (
                        analytics.mistake_types.map((mistake) => (
                            <div
                                key={mistake.key}
                                className="rounded-lg border border-slate-200 p-4 dark:border-slate-800"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {mistake.name}
                                        </p>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">
                                            {mistake.value} logged errors
                                        </p>
                                    </div>
                                    <span
                                        className={cn(
                                            'rounded-md px-2 py-1 text-sm font-semibold',
                                            toneStyles[mistake.tone].soft,
                                        )}
                                    >
                                        {mistake.percentage}%
                                    </span>
                                </div>
                                <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                    <motion.div
                                        initial={{ width: 0 }}
                                        animate={{
                                            width: `${mistake.percentage}%`,
                                        }}
                                        transition={{ duration: 0.45 }}
                                        className={cn(
                                            'h-full rounded-full',
                                            toneStyles[mistake.tone].bar,
                                        )}
                                    />
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-300">
                            No recurring mistake pattern is active yet.
                        </div>
                    )}
                </div>
            </div>

            <div className="mt-6 grid gap-3 sm:grid-cols-5">
                {analytics.mistake_sections.map((section) => (
                    <div
                        key={section.key}
                        className="rounded-lg border border-slate-200 p-3 text-center dark:border-slate-800"
                    >
                        <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            {section.section}
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {section.open}
                        </p>
                    </div>
                ))}
            </div>
        </SpotlightCard>
    );
}

function RecommendationPanel({
    recommendations,
}: {
    recommendations: AnalyticsProps['analytics']['recommendations'];
}) {
    return (
        <SpotlightCard className="p-5 md:p-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Action plan
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Highest impact next steps
                    </h2>
                </div>
                <div className="rounded-md bg-emerald-100 p-2 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                    <ListChecks className="size-5" />
                </div>
            </div>

            <div className="mt-6 grid gap-3">
                {recommendations.map((item, index) => (
                    <motion.div
                        key={`${item.kind}-${item.title}`}
                        initial={{ opacity: 0, x: 16 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: index * 0.06 }}
                        className={cn(
                            'rounded-lg border p-4',
                            toneStyles[item.tone].panel,
                        )}
                    >
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span
                                        className={cn(
                                            'rounded-md px-2 py-1 text-xs font-semibold',
                                            toneStyles[item.tone].soft,
                                        )}
                                    >
                                        {item.priority}
                                    </span>
                                    <span className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                        {item.signal}
                                    </span>
                                </div>
                                <h3 className="mt-3 text-lg font-semibold">
                                    {item.title}
                                </h3>
                                <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {item.description}
                                </p>
                            </div>
                            <Button asChild size="sm" variant="outline">
                                <Link href={hrefForRecommendation(item.kind)}>
                                    {item.action}
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </motion.div>
                ))}
            </div>
        </SpotlightCard>
    );
}

function VocabularyPanel({
    vocabulary,
}: {
    vocabulary: AnalyticsProps['analytics']['vocabulary'];
}) {
    const segments = [
        {
            label: 'Mastered',
            value: vocabulary.mastered,
            tone: 'emerald' as Tone,
        },
        {
            label: 'Learning',
            value: vocabulary.learning,
            tone: 'indigo' as Tone,
        },
        {
            label: 'Review',
            value: vocabulary.review_ready,
            tone: 'amber' as Tone,
        },
    ];

    return (
        <SpotlightCard className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Vocabulary retention
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        {vocabulary.mastery_rate}% mastered
                    </h2>
                </div>
                <Button asChild variant="outline" size="sm">
                    <Link href={vocabularyIndex()}>
                        Drill
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            </div>

            <div className="mt-6 flex h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                {segments.map((segment) => (
                    <motion.div
                        key={segment.label}
                        initial={{ width: 0 }}
                        animate={{
                            width: `${vocabulary.total > 0 ? (segment.value / vocabulary.total) * 100 : 0}%`,
                        }}
                        transition={{ duration: 0.45 }}
                        className={toneStyles[segment.tone].bar}
                    />
                ))}
            </div>

            <div className="mt-5 grid grid-cols-3 gap-3">
                {segments.map((segment) => (
                    <div key={segment.label}>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {segment.label}
                        </p>
                        <p className="mt-1 text-xl font-semibold">
                            {segment.value}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mt-5 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <DetailStat
                    label="Retention"
                    value={`${vocabulary.retention_rate}%`}
                />
                <DetailStat
                    label="This week"
                    value={`${vocabulary.reviewed_this_week} words`}
                />
            </div>
        </SpotlightCard>
    );
}

function StudyMinutePanel({
    minutes,
}: {
    minutes: AnalyticsProps['analytics']['study_minutes'];
}) {
    return (
        <SpotlightCard className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Study load
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">Last 7 days</h2>
                </div>
                <div className="rounded-md bg-cyan-100 p-2 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200">
                    <Clock3 className="size-5" />
                </div>
            </div>

            <div className="mt-5 h-52">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={minutes} margin={{ left: -22 }}>
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                        />
                        <YAxis tickLine={false} axisLine={false} />
                        <Tooltip />
                        <Bar
                            dataKey="minutes"
                            fill="#06b6d4"
                            radius={[6, 6, 0, 0]}
                        />
                    </BarChart>
                </ResponsiveContainer>
            </div>
        </SpotlightCard>
    );
}

function ProjectionPanel({
    projection,
}: {
    projection: AnalyticsProps['analytics']['projection'];
}) {
    return (
        <SpotlightCard className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Exam projection
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        {projection.estimated_toefl}
                    </h2>
                </div>
                <div className="rounded-md bg-amber-100 p-2 text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    <PieChartIcon className="size-5" />
                </div>
            </div>

            <div className="mt-6 grid gap-3">
                <DetailRow
                    label="Target score"
                    value={String(projection.target_score)}
                />
                <DetailRow
                    label="Target gap"
                    value={`${projection.target_gap} pts`}
                />
                <DetailRow
                    label="Daily load"
                    value={`${projection.daily_goal_minutes}m`}
                />
                <DetailRow
                    label="Exam date"
                    value={projection.exam_date ?? 'Not set'}
                />
            </div>
        </SpotlightCard>
    );
}

function DetailStat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-sm text-slate-500 dark:text-slate-400">
                {label}
            </p>
            <p className="mt-1 font-semibold">{value}</p>
        </div>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3 text-sm dark:border-slate-800">
            <span className="text-slate-500 dark:text-slate-400">{label}</span>
            <span className="font-semibold">{value}</span>
        </div>
    );
}

function hrefForRecommendation(kind: string) {
    if (kind === 'mistakes') {
        return mistakesIndex();
    }

    if (kind === 'vocabulary') {
        return vocabularyIndex();
    }

    return practiceSetup();
}

function signed(value: number): string {
    if (value > 0) {
        return `+${value}`;
    }

    return String(value);
}

AnalyticsIndex.layout = {
    breadcrumbs: [{ title: 'Analytics', href: analyticsIndex() }],
};
