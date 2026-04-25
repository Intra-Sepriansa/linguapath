import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowRight,
    BarChart3,
    BookOpenCheck,
    Brain,
    CalendarDays,
    CheckCircle2,
    CirclePlay,
    Clock3,
    Flame,
    Gauge,
    GraduationCap,
    LineChart as LineChartIcon,
    Route,
    Sparkles,
    Target,
    TimerReset,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import type { ElementType } from 'react';
import {
    Bar,
    CartesianGrid,
    ComposedChart,
    Line,
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
import { dashboard } from '@/routes';
import { show as lessonShow } from '@/routes/lessons';
import { index as mistakesIndex } from '@/routes/mistakes';
import { setup as practiceSetup } from '@/routes/practice';
import { index as vocabularyIndex } from '@/routes/vocabulary';
import type { StudyDaySummary } from '@/types';

type SkillKey = 'listening' | 'structure' | 'reading';
type FocusKind = 'lesson' | 'practice' | 'mistakes' | 'vocabulary';
type Tone = 'amber' | 'cyan' | 'emerald' | 'indigo' | 'rose' | 'slate';

type WeeklyActivity = {
    date: string;
    iso_date: string;
    accuracy: number;
    minutes: number;
    questions: number;
    lessons: number;
    intensity: number;
};

type SkillDiagnostic = {
    skill: SkillKey;
    label: string;
    score: number;
    attempts: number;
    last_score: number | null;
    momentum: number;
    status: string;
};

type FocusItem = {
    kind: FocusKind;
    title: string;
    signal: string;
    description: string;
    action: string;
    tone: Tone;
};

type DashboardProps = {
    overview: {
        profile: {
            target_score: number;
            daily_goal_minutes: number;
            current_level: string;
            exam_date: string | null;
            preferred_study_time: string | null;
        };
        today: StudyDaySummary | null;
        readiness: {
            score: number;
            level: string;
            trend: number;
            trend_label: string;
            estimated_toefl: string;
            target_gap: number;
        };
        streak: number;
        completed_days: number;
        total_days: number;
        path: {
            title: string;
            duration_days: number;
            completed_days: number;
            completion_percentage: number;
            days_remaining: number;
            pacing_delta: number;
            pacing_label: string;
            started_at: string | null;
        };
        study_load: {
            weekly_minutes: number;
            weekly_goal_minutes: number;
            goal_completion: number;
            active_days: number;
            weekly_questions: number;
            average_accuracy: number;
            minutes_today: number;
        };
        skill_progress: Record<SkillKey, number>;
        skill_diagnostics: SkillDiagnostic[];
        weekly_activity: WeeklyActivity[];
        mistakes_to_review: number;
        mistake_heatmap: Array<{
            skill: string;
            label: string;
            count: number;
        }>;
        vocabulary: {
            total: number;
            learning: number;
            mastered: number;
            weak: number;
            review_later: number;
            mastery_rate: number;
        };
        focus_queue: FocusItem[];
        upcoming_days: StudyDaySummary[];
        recent_sessions: Array<{
            id: number;
            section: string;
            score: number;
            questions: number;
            duration_minutes: number;
            mode: string;
            day: string;
            finished_at: string | null;
        }>;
        next_action: string;
    };
};

const toneStyles: Record<
    Tone,
    {
        panel: string;
        icon: string;
        soft: string;
        text: string;
        bar: string;
    }
> = {
    amber: {
        panel: 'border-amber-200 bg-amber-50/80 dark:border-amber-900/70 dark:bg-amber-950/20',
        icon: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        soft: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        text: 'text-amber-700 dark:text-amber-200',
        bar: 'bg-amber-500',
    },
    cyan: {
        panel: 'border-cyan-200 bg-cyan-50/80 dark:border-cyan-900/70 dark:bg-cyan-950/20',
        icon: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
        soft: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
        text: 'text-cyan-700 dark:text-cyan-200',
        bar: 'bg-cyan-500',
    },
    emerald: {
        panel: 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900/70 dark:bg-emerald-950/20',
        icon: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        soft: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        text: 'text-emerald-700 dark:text-emerald-200',
        bar: 'bg-emerald-500',
    },
    indigo: {
        panel: 'border-indigo-200 bg-indigo-50/80 dark:border-indigo-900/70 dark:bg-indigo-950/20',
        icon: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        soft: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        text: 'text-indigo-700 dark:text-indigo-200',
        bar: 'bg-indigo-500',
    },
    rose: {
        panel: 'border-rose-200 bg-rose-50/80 dark:border-rose-900/70 dark:bg-rose-950/20',
        icon: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        soft: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        text: 'text-rose-700 dark:text-rose-200',
        bar: 'bg-rose-500',
    },
    slate: {
        panel: 'border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/40',
        icon: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
        soft: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
        text: 'text-slate-700 dark:text-slate-200',
        bar: 'bg-slate-500',
    },
};

export default function Dashboard({ overview }: DashboardProps) {
    const readinessScore = clamp(overview.readiness.score);
    const completion = clamp(overview.path.completion_percentage);
    const primaryHref =
        overview.mistakes_to_review > 0
            ? mistakesIndex()
            : overview.today
              ? lessonShow(overview.today.id)
              : practiceSetup();
    const primaryAction =
        overview.mistakes_to_review > 0
            ? 'Review mistakes'
            : overview.today
              ? `Start Day ${overview.today.day_number}`
              : 'Start practice';
    const radarData = overview.skill_diagnostics.map((skill) => ({
        subject: skill.label,
        score: clamp(skill.score),
    }));

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="grid gap-6 xl:grid-cols-[1.45fr_0.75fr]">
                    <SpotlightCard className="p-6 md:p-8">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-primary dark:bg-indigo-950 dark:text-indigo-200">
                                    <Sparkles className="size-4" />
                                    TOEFL ITP Command Center
                                </div>
                                <h1 className="mt-5 text-4xl leading-tight font-semibold text-slate-950 md:text-5xl dark:text-white">
                                    {overview.today
                                        ? `Day ${overview.today.day_number}: ${overview.today.title}`
                                        : 'Your learning system is ready'}
                                </h1>
                                <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                                    {overview.today?.objective ??
                                        'Build today from the strongest signal: lesson progress, open mistakes, skill readiness, and vocabulary retention.'}
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    asChild
                                    className="h-11 bg-primary px-5 shadow-sm shadow-indigo-500/20 hover:bg-primary/90"
                                >
                                    <Link href={primaryHref}>
                                        {primaryAction}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="h-11 px-5"
                                >
                                    <Link href={practiceSetup()}>
                                        <CirclePlay className="size-4" />
                                        Practice
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <div className="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <CompactSignal
                                icon={Route}
                                label="Path"
                                value={`${completion}%`}
                                detail={overview.path.pacing_label}
                            />
                            <CompactSignal
                                icon={TimerReset}
                                label="Today"
                                value={`${overview.study_load.minutes_today}m`}
                                detail={`${overview.profile.daily_goal_minutes}m goal`}
                            />
                            <CompactSignal
                                icon={Target}
                                label="Target"
                                value={String(overview.profile.target_score)}
                                detail={
                                    overview.readiness.target_gap > 0
                                        ? `${overview.readiness.target_gap} pts gap`
                                        : 'Target band reached'
                                }
                            />
                            <CompactSignal
                                icon={Flame}
                                label="Streak"
                                value={`${overview.streak}d`}
                                detail={`${overview.study_load.active_days}/7 active days`}
                            />
                        </div>
                    </SpotlightCard>

                    <ReadinessPanel
                        score={readinessScore}
                        level={overview.readiness.level}
                        estimatedToefl={overview.readiness.estimated_toefl}
                        trend={overview.readiness.trend}
                        trendLabel={overview.readiness.trend_label}
                    />
                </section>

                <section className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        icon={Gauge}
                        label="Weekly Load"
                        value={`${overview.study_load.weekly_minutes}m`}
                        detail={`${overview.study_load.goal_completion}% of ${overview.study_load.weekly_goal_minutes}m`}
                        tone="cyan"
                    />
                    <MetricCard
                        icon={Brain}
                        label="Average Accuracy"
                        value={`${overview.study_load.average_accuracy}%`}
                        detail={`${overview.study_load.weekly_questions} questions logged`}
                        tone="emerald"
                    />
                    <MetricCard
                        icon={AlertCircle}
                        label="Open Mistakes"
                        value={String(overview.mistakes_to_review)}
                        detail="Review queue"
                        tone={
                            overview.mistakes_to_review > 0 ? 'rose' : 'slate'
                        }
                    />
                    <MetricCard
                        icon={GraduationCap}
                        label="Vocabulary Mastery"
                        value={`${overview.vocabulary.mastery_rate}%`}
                        detail={`${overview.vocabulary.mastered}/${overview.vocabulary.total} mastered`}
                        tone="amber"
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                    <TodayPlan overview={overview} />
                    <FocusQueue
                        items={overview.focus_queue}
                        today={overview.today}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                    <WeeklyActivityPanel activity={overview.weekly_activity} />
                    <SkillRadarPanel data={radarData} />
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                    <SkillDiagnostics skills={overview.skill_diagnostics} />
                    <UpcomingTimeline days={overview.upcoming_days} />
                </section>

                <section className="grid gap-6 lg:grid-cols-3">
                    <VocabularyPanel vocabulary={overview.vocabulary} />
                    <MistakeHeatmap mistakes={overview.mistake_heatmap} />
                    <RecentSessions sessions={overview.recent_sessions} />
                </section>
            </div>
        </>
    );
}

function ReadinessPanel({
    score,
    level,
    estimatedToefl,
    trend,
    trendLabel,
}: {
    score: number;
    level: string;
    estimatedToefl: string;
    trend: number;
    trendLabel: string;
}) {
    const TrendIcon = trend < 0 ? TrendingDown : TrendingUp;

    return (
        <SpotlightCard className="grid content-between gap-6 p-6 md:p-8">
            <div>
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Readiness
                        </p>
                        <h2 className="mt-1 text-2xl font-semibold">{level}</h2>
                    </div>
                    <div
                        className={cn(
                            'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold',
                            trend < 0
                                ? toneStyles.rose.soft
                                : toneStyles.emerald.soft,
                        )}
                    >
                        <TrendIcon className="size-4" />
                        {trendLabel}
                    </div>
                </div>

                <div className="mt-7 grid place-items-center">
                    <div
                        className="grid size-52 place-items-center rounded-full"
                        style={{
                            background: `conic-gradient(#4f46e5 ${score * 3.6}deg, #e2e8f0 0deg)`,
                        }}
                    >
                        <div className="grid size-40 place-items-center rounded-full bg-white shadow-inner dark:bg-slate-950">
                            <div className="text-center">
                                <div className="text-5xl font-semibold text-primary">
                                    <CountUp value={score} suffix="%" />
                                </div>
                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    readiness
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
                <div className="rounded-lg border border-indigo-100 bg-indigo-50/70 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        TOEFL band
                    </p>
                    <p className="mt-1 text-2xl font-semibold text-primary">
                        {estimatedToefl}
                    </p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        Signal
                    </p>
                    <p className="mt-1 text-2xl font-semibold">
                        {trend === 0 ? 'Base' : signed(trend)}
                    </p>
                </div>
            </div>
        </SpotlightCard>
    );
}

function TodayPlan({ overview }: { overview: DashboardProps['overview'] }) {
    const todayHref = overview.today
        ? lessonShow(overview.today.id)
        : practiceSetup();

    return (
        <SpotlightCard className="p-6 md:p-7">
            <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="inline-flex items-center gap-2 rounded-md bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                        <BookOpenCheck className="size-4" />
                        Next execution block
                    </div>
                    <h2 className="mt-5 text-3xl font-semibold">
                        {overview.today
                            ? overview.today.title
                            : 'Focused practice'}
                    </h2>
                    <p className="mt-3 max-w-2xl leading-7 text-slate-600 dark:text-slate-300">
                        {overview.today?.objective ??
                            'Use a quick diagnostic practice block to produce fresh readiness signals.'}
                    </p>
                </div>
                <Button asChild className="h-11 bg-primary px-5">
                    <Link href={todayHref}>
                        {overview.today ? 'Open lesson' : 'Start practice'}
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            </div>

            <div className="mt-7 grid gap-4 md:grid-cols-3">
                <ExecutionStat
                    icon={Clock3}
                    label="Estimated"
                    value={
                        overview.today
                            ? `${overview.today.estimated_minutes}m`
                            : `${overview.profile.daily_goal_minutes}m`
                    }
                />
                <ExecutionStat
                    icon={Target}
                    label="Goal"
                    value="25 questions"
                />
                <ExecutionStat
                    icon={CheckCircle2}
                    label="Accuracy"
                    value="80%"
                />
            </div>

            <div className="mt-7">
                <div className="flex items-center justify-between gap-4 text-sm font-semibold">
                    <span>60-day path progress</span>
                    <span className="text-primary">
                        {overview.path.completed_days}/
                        {overview.path.duration_days}
                    </span>
                </div>
                <div className="mt-3 h-3 overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-800">
                    <div
                        className="h-full rounded-full bg-emerald-500"
                        style={{
                            width: `${clamp(overview.path.completion_percentage)}%`,
                        }}
                    />
                </div>
                <div className="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                    <span>{overview.path.days_remaining} days remaining</span>
                    <span>{overview.path.pacing_label}</span>
                    <span>{overview.next_action}</span>
                </div>
            </div>
        </SpotlightCard>
    );
}

function FocusQueue({
    items,
    today,
}: {
    items: FocusItem[];
    today: StudyDaySummary | null;
}) {
    return (
        <SpotlightCard className="p-6 md:p-7">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Priority queue
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">Smart focus</h2>
                </div>
                <div className="flex size-11 items-center justify-center rounded-md bg-indigo-100 text-primary dark:bg-indigo-950">
                    <Brain className="size-5" />
                </div>
            </div>

            <div className="mt-6 grid gap-3">
                {items.map((item, index) => (
                    <FocusRow
                        key={`${item.kind}-${item.title}`}
                        item={item}
                        index={index}
                        href={hrefForFocus(item.kind, today)}
                    />
                ))}
            </div>
        </SpotlightCard>
    );
}

function FocusRow({
    item,
    index,
    href,
}: {
    item: FocusItem;
    index: number;
    href: InertiaLinkProps['href'];
}) {
    const style = toneStyles[item.tone];

    return (
        <Link
            href={href}
            className={cn(
                'grid gap-4 rounded-lg border p-4 transition hover:-translate-y-0.5 hover:shadow-md md:grid-cols-[auto_1fr_auto]',
                style.panel,
            )}
        >
            <div
                className={cn(
                    'flex size-10 items-center justify-center rounded-md font-semibold',
                    style.icon,
                )}
            >
                {index + 1}
            </div>
            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                    <h3 className="font-semibold">{item.title}</h3>
                    <span
                        className={cn(
                            'rounded-md px-2 py-1 text-xs font-semibold',
                            style.soft,
                        )}
                    >
                        {item.signal}
                    </span>
                </div>
                <p className="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {item.description}
                </p>
            </div>
            <span className={cn('self-center font-semibold', style.text)}>
                {item.action}
            </span>
        </Link>
    );
}

function WeeklyActivityPanel({ activity }: { activity: WeeklyActivity[] }) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5 dark:border-slate-800">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Last 7 days
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Activity and accuracy
                    </h2>
                </div>
                <div className="inline-flex items-center gap-2 rounded-md bg-cyan-100 px-3 py-1.5 text-sm font-semibold text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200">
                    <LineChartIcon className="size-4" />
                    Live pace
                </div>
            </div>

            <div className="mt-6 h-72 min-h-72 min-w-0">
                <ResponsiveContainer width="100%" height="100%">
                    <ComposedChart data={activity}>
                        <CartesianGrid vertical={false} stroke="#e2e8f0" />
                        <XAxis
                            dataKey="date"
                            axisLine={false}
                            tickLine={false}
                        />
                        <YAxis
                            yAxisId="left"
                            hide
                            domain={[0, 'dataMax + 20']}
                        />
                        <YAxis
                            yAxisId="right"
                            hide
                            orientation="right"
                            domain={[0, 100]}
                        />
                        <Tooltip
                            cursor={{ fill: '#f8fafc' }}
                            contentStyle={{
                                borderRadius: 8,
                                border: '1px solid #e2e8f0',
                            }}
                        />
                        <Bar
                            yAxisId="left"
                            dataKey="minutes"
                            fill="#0891b2"
                            radius={[4, 4, 0, 0]}
                            maxBarSize={40}
                        />
                        <Line
                            yAxisId="right"
                            type="monotone"
                            dataKey="accuracy"
                            stroke="#10b981"
                            strokeWidth={3}
                            dot={{ r: 4, fill: '#10b981' }}
                        />
                    </ComposedChart>
                </ResponsiveContainer>
            </div>
        </SpotlightCard>
    );
}

function SkillRadarPanel({
    data,
}: {
    data: Array<{ subject: string; score: number }>;
}) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Section balance
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">Skill radar</h2>
                </div>
                <div className="flex size-11 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">
                    <BarChart3 className="size-5" />
                </div>
            </div>

            <div className="mt-6 h-72 min-h-72 min-w-0">
                <ResponsiveContainer width="100%" height="100%">
                    <RadarChart data={data} outerRadius="70%">
                        <PolarGrid stroke="#e2e8f0" />
                        <PolarAngleAxis
                            dataKey="subject"
                            tick={{ fill: '#475569', fontSize: 12 }}
                        />
                        <Radar
                            dataKey="score"
                            fill="#4f46e5"
                            fillOpacity={0.22}
                            stroke="#4f46e5"
                            strokeWidth={2}
                        />
                        <Tooltip
                            contentStyle={{
                                borderRadius: 8,
                                border: '1px solid #e2e8f0',
                            }}
                        />
                    </RadarChart>
                </ResponsiveContainer>
            </div>
        </SpotlightCard>
    );
}

function SkillDiagnostics({ skills }: { skills: SkillDiagnostic[] }) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Diagnostics
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Skill readiness
                    </h2>
                </div>
                <div className="rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-primary dark:bg-indigo-950">
                    {skills.length} sections
                </div>
            </div>

            <div className="mt-6 grid gap-5">
                {skills.map((skill) => (
                    <SkillProgress key={skill.skill} skill={skill} />
                ))}
            </div>
        </SpotlightCard>
    );
}

function SkillProgress({ skill }: { skill: SkillDiagnostic }) {
    const score = clamp(skill.score);
    const tone =
        score >= 80
            ? toneStyles.emerald
            : score >= 60
              ? toneStyles.cyan
              : toneStyles.rose;

    return (
        <div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 className="font-semibold">{skill.label}</h3>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {skill.attempts} attempts · latest{' '}
                        {skill.last_score ?? 'none'}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <span
                        className={cn(
                            'rounded-md px-2.5 py-1 text-xs font-semibold',
                            tone.soft,
                        )}
                    >
                        {skill.status}
                    </span>
                    <span className="text-sm font-semibold text-slate-600 dark:text-slate-300">
                        {skill.momentum === 0 ? '0' : signed(skill.momentum)}
                    </span>
                </div>
            </div>
            <div className="mt-3 h-3 overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-800">
                <div
                    className={cn('h-full rounded-full', tone.bar)}
                    style={{ width: `${score}%` }}
                />
            </div>
        </div>
    );
}

function UpcomingTimeline({ days }: { days: StudyDaySummary[] }) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Upcoming
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Path timeline
                    </h2>
                </div>
                <CalendarDays className="size-6 text-primary" />
            </div>

            <div className="mt-6 grid gap-3">
                {days.map((day) => (
                    <Link
                        key={day.id}
                        href={lessonShow(day.id)}
                        className="grid gap-4 rounded-lg border border-slate-200 bg-slate-50/70 p-4 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white hover:shadow-sm md:grid-cols-[auto_1fr_auto] dark:border-slate-800 dark:bg-slate-900/30 dark:hover:bg-slate-900"
                    >
                        <div className="flex size-11 items-center justify-center rounded-md bg-white font-semibold text-primary dark:bg-slate-950">
                            {day.day_number}
                        </div>
                        <div className="min-w-0">
                            <h3 className="font-semibold">{day.title}</h3>
                            <p className="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                {day.focus_label} · {day.estimated_minutes}m
                            </p>
                        </div>
                        <span className="self-center text-sm font-semibold text-primary">
                            {day.completed ? 'Review' : 'Open'}
                        </span>
                    </Link>
                ))}
            </div>
        </SpotlightCard>
    );
}

function VocabularyPanel({
    vocabulary,
}: {
    vocabulary: DashboardProps['overview']['vocabulary'];
}) {
    const segments = [
        { label: 'Mastered', value: vocabulary.mastered, tone: 'emerald' },
        { label: 'Learning', value: vocabulary.learning, tone: 'cyan' },
        { label: 'Weak', value: vocabulary.weak, tone: 'rose' },
        { label: 'Later', value: vocabulary.review_later, tone: 'amber' },
    ] as const;

    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Vocabulary
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Retention health
                    </h2>
                </div>
                <div className="text-3xl font-semibold text-primary">
                    {vocabulary.mastery_rate}%
                </div>
            </div>

            <div className="mt-6 grid gap-3">
                {segments.map((segment) => (
                    <SegmentRow
                        key={segment.label}
                        label={segment.label}
                        value={segment.value}
                        total={Math.max(1, vocabulary.total)}
                        tone={segment.tone}
                    />
                ))}
            </div>

            <Button asChild variant="outline" className="mt-6 w-full">
                <Link href={vocabularyIndex()}>
                    <BookOpenCheck className="size-4" />
                    Open vocabulary
                </Link>
            </Button>
        </SpotlightCard>
    );
}

function MistakeHeatmap({
    mistakes,
}: {
    mistakes: DashboardProps['overview']['mistake_heatmap'];
}) {
    const maxCount = Math.max(1, ...mistakes.map((mistake) => mistake.count));

    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Mistakes
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Error heatmap
                    </h2>
                </div>
                <AlertCircle className="size-6 text-rose-600" />
            </div>

            <div className="mt-6 grid gap-3">
                {mistakes.map((mistake) => (
                    <SegmentRow
                        key={mistake.skill}
                        label={mistake.label}
                        value={mistake.count}
                        total={maxCount}
                        tone={mistake.count > 0 ? 'rose' : 'slate'}
                    />
                ))}
            </div>

            <Button asChild variant="outline" className="mt-6 w-full">
                <Link href={mistakesIndex()}>
                    <AlertCircle className="size-4" />
                    Review mistakes
                </Link>
            </Button>
        </SpotlightCard>
    );
}

function RecentSessions({
    sessions,
}: {
    sessions: DashboardProps['overview']['recent_sessions'];
}) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Practice
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        Recent sessions
                    </h2>
                </div>
                <CirclePlay className="size-6 text-primary" />
            </div>

            <div className="mt-6 grid gap-3">
                {sessions.length > 0 ? (
                    sessions.map((session) => (
                        <div
                            key={session.id}
                            className="rounded-lg border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/30"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h3 className="font-semibold">
                                        {session.section}
                                    </h3>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {session.day} · {session.questions}{' '}
                                        questions
                                    </p>
                                </div>
                                <div className="text-2xl font-semibold text-primary">
                                    {session.score}%
                                </div>
                            </div>
                            <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                {session.duration_minutes}m ·{' '}
                                {session.finished_at ?? 'recently'}
                            </p>
                        </div>
                    ))
                ) : (
                    <div className="grid min-h-40 place-items-center rounded-lg border border-dashed border-slate-200 text-center dark:border-slate-800">
                        <div>
                            <CirclePlay className="mx-auto size-8 text-slate-400" />
                            <p className="mt-3 text-sm text-slate-500">
                                No finished sessions yet.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </SpotlightCard>
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
    const style = toneStyles[tone];

    return (
        <SpotlightCard className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        {label}
                    </p>
                    <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-white">
                        {value}
                    </p>
                    <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        {detail}
                    </p>
                </div>
                <div
                    className={cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-md',
                        style.icon,
                    )}
                >
                    <Icon className="size-5" />
                </div>
            </div>
        </SpotlightCard>
    );
}

function CompactSignal({
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
        <div className="rounded-lg border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <div className="flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                <Icon className="size-4" />
                {label}
            </div>
            <p className="mt-3 text-2xl font-semibold text-slate-950 dark:text-white">
                {value}
            </p>
            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                {detail}
            </p>
        </div>
    );
}

function ExecutionStat({
    icon: Icon,
    label,
    value,
}: {
    icon: ElementType;
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <div className="flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                <Icon className="size-4" />
                {label}
            </div>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </div>
    );
}

function SegmentRow({
    label,
    value,
    total,
    tone,
}: {
    label: string;
    value: number;
    total: number;
    tone: Tone;
}) {
    const style = toneStyles[tone];
    const width = total > 0 ? clamp(Math.round((value / total) * 100)) : 0;

    return (
        <div>
            <div className="flex items-center justify-between gap-3 text-sm font-semibold">
                <span>{label}</span>
                <span className={style.text}>{value}</span>
            </div>
            <div className="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-800">
                <div
                    className={cn('h-full rounded-full', style.bar)}
                    style={{ width: `${value > 0 ? Math.max(5, width) : 0}%` }}
                />
            </div>
        </div>
    );
}

function hrefForFocus(
    kind: FocusKind,
    today: StudyDaySummary | null,
): InertiaLinkProps['href'] {
    if (kind === 'mistakes') {
        return mistakesIndex();
    }

    if (kind === 'vocabulary') {
        return vocabularyIndex();
    }

    if (kind === 'lesson' && today) {
        return lessonShow(today.id);
    }

    return practiceSetup();
}

function clamp(value: number): number {
    return Math.max(0, Math.min(100, value));
}

function signed(value: number): string {
    return value > 0 ? `+${value}` : String(value);
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
