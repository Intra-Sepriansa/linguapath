import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    BookOpenCheck,
    CheckCircle2,
    CirclePlay,
    Clock,
    Flame,
    Folder,
    GraduationCap,
    Lock,
    RotateCcw,
    Route,
    Sparkles,
    XCircle,
} from 'lucide-react';
import { StaggeredText } from '@/components/reactbits/staggered-text';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as lessonShow } from '@/routes/lessons';
import { index as studyPathIndex } from '@/routes/study-path';
import type { StudyDaySummary } from '@/types';

type StudyPathProps = {
    path: {
        title: string;
        description: string;
        duration_days: number;
        completed_days: number;
        weeks: Array<{ week: number; title: string; days: StudyDaySummary[] }>;
    };
};

type Phase = {
    title: string;
    subtitle: string;
    days: StudyDaySummary[];
    firstWeek: number;
    lastWeek: number;
};

export default function StudyPath({ path }: StudyPathProps) {
    const progress = Math.round(
        (path.completed_days / path.duration_days) * 100,
    );
    const allDays = path.weeks.flatMap((week) => week.days);
    const currentDay =
        allDays.find((day) => !day.completed) ?? allDays[allDays.length - 1];
    const currentDayNumber = currentDay?.day_number ?? 1;
    const phases = createPhases(path.weeks);
    const passedDays = allDays.filter(
        (day) => day.assessment.status === 'passed',
    ).length;
    const failedDays = allDays.filter(
        (day) => day.assessment.status === 'failed',
    ).length;
    const pendingDays = Math.max(0, allDays.length - passedDays - failedDays);

    return (
        <>
            <Head title="60-Day Study Path" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-8 p-4 md:p-8">
                <section className="relative overflow-hidden rounded-lg border border-violet-100 bg-white p-7 shadow-sm md:p-9 dark:border-indigo-950 dark:bg-slate-950">
                    <div className="absolute top-0 right-0 h-44 w-44 rounded-full bg-indigo-200/40 blur-3xl dark:bg-indigo-900/20" />
                    <div className="relative grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-4 py-2 text-sm font-semibold text-primary dark:bg-indigo-950">
                                <Sparkles className="size-4" />
                                Grammar Foundations sedang diprioritaskan
                            </div>
                            <h1 className="mt-6 max-w-3xl text-4xl leading-tight font-semibold tracking-normal text-slate-950 md:text-6xl dark:text-white">
                                <StaggeredText text="Your 60-Day Path" />
                            </h1>
                            <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                                Minggu 1 dan 2 dibuat sebagai jalur belajar
                                pelan tetapi dalam: pahami konsep, lihat contoh,
                                coba latihan langsung, lalu kerjakan mini-test
                                dengan koreksi detail.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <PathMetric
                                icon={Route}
                                label="Lulus"
                                value={`${passedDays} hari`}
                            />
                            <PathMetric
                                icon={GraduationCap}
                                label="Tidak lulus"
                                value={`${failedDays} retake`}
                            />
                            <PathMetric
                                icon={Flame}
                                label="Syarat"
                                value="Benar semua"
                            />
                        </div>
                    </div>
                </section>

                <section className="rounded-lg border border-violet-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-semibold">
                                Overall Progress
                            </h2>
                            <p className="mt-1 text-lg text-slate-700 dark:text-slate-300">
                                Day {path.completed_days} / {path.duration_days}{' '}
                                completed
                            </p>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                {passedDays} lulus, {failedDays} tidak lulus,{' '}
                                {pendingDays} belum tes
                            </p>
                        </div>
                        <div className="text-3xl font-semibold text-primary">
                            {progress}%
                        </div>
                    </div>
                    <div className="mt-6 h-2.5 rounded-full bg-violet-100 dark:bg-slate-800">
                        <motion.div
                            initial={{ width: 0 }}
                            animate={{ width: `${progress}%` }}
                            transition={{ duration: 0.7, ease: 'easeOut' }}
                            className="h-2.5 rounded-full bg-emerald-600"
                        />
                    </div>
                </section>

                <section className="grid grid-cols-1 gap-6">
                    {phases.map((phase) => {
                        const unlocked =
                            (phase.days[0]?.day_number ?? Infinity) <=
                            currentDayNumber;
                        const complete = phase.days.every(
                            (day) => day.completed,
                        );

                        return (
                            <article
                                key={phase.title}
                                className={cn(
                                    'rounded-lg border p-6 transition hover:-translate-y-0.5',
                                    unlocked
                                        ? 'border-violet-100 bg-white shadow-sm dark:border-indigo-950 dark:bg-slate-950'
                                        : 'border-violet-100 bg-violet-50/60 text-slate-400 dark:border-indigo-950 dark:bg-indigo-950/20',
                                )}
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex items-start gap-4">
                                        <div
                                            className={cn(
                                                'flex size-12 shrink-0 items-center justify-center rounded-md',
                                                unlocked
                                                    ? 'bg-indigo-100 text-indigo-800'
                                                    : 'bg-slate-100 text-slate-400',
                                            )}
                                        >
                                            <Folder className="size-5 fill-current" />
                                        </div>
                                        <div>
                                            <div className="flex flex-wrap items-baseline gap-3">
                                                <h2 className="text-2xl font-semibold">
                                                    {phase.title}
                                                </h2>
                                                <p className="text-sm font-semibold tracking-[0.14em] text-slate-500 uppercase">
                                                    {phase.subtitle}
                                                </p>
                                            </div>
                                            {phase.firstWeek === 1 && (
                                                <p className="mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">
                                                    Pondasi wajib: subject,
                                                    verb, tense, agreement,
                                                    missing parts, gerund,
                                                    infinitive, parallelism, dan
                                                    preposition.
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    {complete && (
                                        <CheckCircle2 className="size-6 text-emerald-600" />
                                    )}
                                </div>

                                {unlocked ? (
                                    <div className="mt-7 grid gap-3">
                                        {phase.days.map((day) => (
                                            <DayRow
                                                key={day.id}
                                                day={day}
                                                current={
                                                    day.id === currentDay?.id
                                                }
                                                locked={
                                                    day.day_number >
                                                    currentDayNumber
                                                }
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <div className="grid min-h-72 place-items-center text-center">
                                        <div>
                                            <Lock className="mx-auto size-10 text-slate-300" />
                                            <p className="mt-4 text-lg text-slate-500">
                                                Complete earlier weeks to unlock
                                                this section.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </article>
                        );
                    })}
                </section>
            </div>
        </>
    );
}

function PathMetric({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof Route;
    label: string;
    value: string;
}) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25 }}
            className="rounded-lg border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/30"
        >
            <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-md bg-white text-primary dark:bg-slate-950">
                    <Icon className="size-4" />
                </div>
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    {label}
                </p>
            </div>
            <p className="mt-3 leading-6 font-semibold text-slate-950 dark:text-white">
                {value}
            </p>
        </motion.div>
    );
}

function DayRow({
    day,
    current,
    locked,
}: {
    day: StudyDaySummary;
    current: boolean;
    locked: boolean;
}) {
    const status = day.assessment.status;
    const statusTone = assessmentTone(status);
    const StatusIcon =
        status === 'passed'
            ? CheckCircle2
            : status === 'failed'
              ? XCircle
              : CirclePlay;

    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.2 }}
            className={cn(
                'grid gap-4 rounded-lg border p-4 md:grid-cols-[auto_1fr_auto]',
                current &&
                    'border-primary bg-white shadow-sm shadow-indigo-500/10 dark:bg-slate-950',
                day.completed && 'border-violet-100 bg-violet-50/40',
                status === 'failed' &&
                    'border-rose-200 bg-rose-50/70 dark:border-rose-900 dark:bg-rose-950/20',
                locked &&
                    'border-dashed border-violet-200 bg-violet-50/40 text-slate-500',
                !current &&
                    !day.completed &&
                    !locked &&
                    'border-violet-100 bg-white dark:border-indigo-950 dark:bg-slate-950',
            )}
        >
            <div
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-full',
                    day.completed && 'bg-emerald-100 text-emerald-700',
                    status === 'failed' && 'bg-rose-100 text-rose-700',
                    current && 'bg-primary text-primary-foreground',
                    locked && 'bg-slate-100 text-slate-500',
                )}
            >
                {status === 'passed' ? (
                    <CheckCircle2 className="size-5" />
                ) : status === 'failed' ? (
                    <RotateCcw className="size-5" />
                ) : locked ? (
                    <Lock className="size-4" />
                ) : (
                    <CirclePlay className="size-5" />
                )}
            </div>

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h3
                        className={cn(
                            'font-semibold',
                            day.completed && 'text-slate-600 line-through',
                        )}
                    >
                        Day {day.day_number}: {day.title}
                    </h3>
                    {current && (
                        <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-primary">
                            Current
                        </span>
                    )}
                    <span
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold',
                            statusTone,
                        )}
                    >
                        <StatusIcon className="size-3.5" />
                        {day.assessment.label}
                    </span>
                </div>
                <div className="mt-1 flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                    <span>{day.focus_label}</span>
                    <span className="inline-flex items-center gap-1">
                        <Clock className="size-3.5" />
                        {day.estimated_minutes} mins
                    </span>
                    {day.assessment.score !== null && (
                        <span>
                            {day.assessment.correct_answers}/
                            {day.assessment.total_questions} benar ·{' '}
                            {day.assessment.score}%
                        </span>
                    )}
                </div>
                <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {dayStudyDescription(day)}
                </p>
                <div className="mt-3 flex flex-wrap gap-2">
                    {dayStudyFlow(day).map((step) => (
                        <span
                            key={step}
                            className="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-primary dark:bg-indigo-950 dark:text-indigo-200"
                        >
                            {step}
                        </span>
                    ))}
                    <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">
                        Lulus = 100%
                    </span>
                </div>
            </div>

            {locked ? (
                <span className="hidden text-sm font-medium text-slate-500 sm:block">
                    Locked
                </span>
            ) : (
                <Button
                    asChild
                    size="sm"
                    className={cn(
                        'bg-primary hover:bg-primary/90',
                        day.completed &&
                            'bg-violet-100 text-primary hover:bg-violet-200',
                    )}
                >
                    <Link href={lessonShow(day.id)}>
                        <BookOpenCheck className="size-4" />
                        {day.completed
                            ? 'Review'
                            : status === 'failed'
                              ? 'Retake'
                              : 'Start'}
                    </Link>
                </Button>
            )}
        </motion.div>
    );
}

function assessmentTone(
    status: StudyDaySummary['assessment']['status'],
): string {
    if (status === 'passed') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200';
    }

    if (status === 'failed') {
        return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200';
    }

    return 'bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300';
}

function createPhases(weeks: StudyPathProps['path']['weeks']): Phase[] {
    const phaseMap = new Map<number, Phase>();

    weeks.forEach((week) => {
        const phaseIndex = Math.floor((week.week - 1) / 2);
        const firstWeek = phaseIndex * 2 + 1;
        const lastWeek = Math.min(firstWeek + 1, weeks[weeks.length - 1].week);
        const title =
            firstWeek === lastWeek
                ? `Week ${firstWeek}`
                : `Weeks ${firstWeek} & ${lastWeek}`;

        if (!phaseMap.has(phaseIndex)) {
            phaseMap.set(phaseIndex, {
                title,
                subtitle: phaseSubtitle(phaseIndex),
                days: [],
                firstWeek,
                lastWeek,
            });
        }

        phaseMap.get(phaseIndex)?.days.push(...week.days);
    });

    return Array.from(phaseMap.values());
}

function dayStudyDescription(day: StudyDaySummary): string {
    const descriptions: Record<string, string> = {
        'Subject + Verb':
            'Mulai dari inti kalimat. Kamu belajar membedakan subject, main verb, dan modifier agar tidak tertipu frasa panjang.',
        'To be':
            'Kuasai be sebagai main verb, auxiliary progressive, dan passive supaya tidak menambahkan is/are secara asal.',
        'Simple Present':
            'Latih fakta umum, kebiasaan, do/does, dan akhiran -s/-es untuk subject third-person singular.',
        'Subject Verb Agreement':
            'Belajar mencari head subject, bukan noun terdekat, terutama saat ada of-phrase atau modifier panjang.',
        'Simple Past':
            'Bedakan V2, irregular verb, did + base verb, dan konteks kejadian lampau yang sudah selesai.',
        'Future Tense':
            'Latih will, be going to, jadwal, dan future time clause agar tidak memakai will di tempat yang salah.',
        'Week 1 Review':
            'Gabungkan subject, verb, tense, be, agreement, past, dan future dalam diagnosis Structure yang cepat.',
        'Missing Subject':
            'Cari verb yang tidak punya pelaku. Kamu belajar bahwa prepositional phrase bukan subject.',
        'Missing Verb':
            'Cari subject yang belum punya finite verb. Fokus pada jebakan V-ing, to + verb, dan participle.',
        'Double Verb':
            'Latih aturan satu clause satu finite verb, lalu perbaiki dengan connector, infinitive, atau bentuk sejajar.',
        'Gerund & Infinitive':
            'Pelajari verb pattern, preposition + gerund, dan to-infinitive untuk tujuan atau complement.',
        'Parallel Structure':
            'Samakan bentuk grammar dalam daftar, perbandingan, dan correlative conjunction.',
        Preposition:
            'Bangun rasa collocation seperti focus on, increase in, effect on, solution to, dan by + gerund.',
        'Week 2 Review':
            'Simulasi diagnosis campuran: missing part, double verb, gerund, infinitive, parallelism, dan preposition.',
    };

    return (
        descriptions[day.title] ??
        'Baca konsep, kerjakan praktik langsung, lalu lanjutkan mini-test dengan koreksi detail.'
    );
}

function dayStudyFlow(day: StudyDaySummary): string[] {
    if (day.day_number <= 14) {
        return [
            'Konsep Indonesia',
            'Contoh salah/benar',
            'Praktik langsung',
            'Mini-test',
        ];
    }

    return ['Strategi', 'Drill', 'Review'];
}

function phaseSubtitle(index: number): string {
    return (
        [
            'Grammar Foundations',
            'Reading Comprehension',
            'Listening Precision',
            'Integrated Practice',
            'Exam Simulation',
        ][index] ?? 'Final Review'
    );
}

StudyPath.layout = {
    breadcrumbs: [{ title: 'Study Path', href: studyPathIndex() }],
};
