import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    ArrowRight,
    BarChart3,
    CheckCircle2,
    Clock,
    RotateCcw,
    Target,
    Trophy,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import { CountUp } from '@/components/reactbits/count-up';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as lessonShow } from '@/routes/lessons';
import { index as mistakesIndex } from '@/routes/mistakes';
import {
    setup as practiceSetup,
    start as startPractice,
} from '@/routes/practice';
import { index as studyPathIndex } from '@/routes/study-path';

type ResultFilter = 'all' | 'wrong' | 'correct';

type PracticeResultProps = {
    result: {
        score: number;
        correct_answers: number;
        total_questions: number;
        wrong_answers: number;
        duration_seconds: number;
        duration_minutes: number;
        section_type: string;
        mode: string;
        passed: boolean;
        passing_score: number;
        requires_all_correct: boolean;
        accuracy_label: string;
        study_day: {
            id: number;
            day_number: number;
            title: string;
        } | null;
        answers: Array<{
            id: number;
            position: number;
            section_type: string;
            question_type: string;
            question: string;
            is_correct: boolean;
            selected: string | null;
            correct: string | null;
            explanation: string;
        }>;
    };
};

export default function PracticeResult({ result }: PracticeResultProps) {
    const [filter, setFilter] = useState<ResultFilter>('all');
    const isLessonMiniTest = result.mode === 'lesson' && result.study_day;
    const chartData = [
        { name: 'Correct', value: result.correct_answers, fill: '#10b981' },
        { name: 'Wrong', value: result.wrong_answers, fill: '#f43f5e' },
    ];
    const filteredAnswers = useMemo(
        () =>
            result.answers.filter((answer) => {
                if (filter === 'correct') {
                    return answer.is_correct;
                }

                if (filter === 'wrong') {
                    return !answer.is_correct;
                }

                return true;
            }),
        [filter, result.answers],
    );
    const masteryMessage = result.passed
        ? 'Semua pola sudah tepat di sesi ini.'
        : `${result.wrong_answers} pola perlu direview ulang.`;

    const retryMiniTest = () => {
        if (!result.study_day) {
            return;
        }

        router.post(startPractice.url(), {
            section_type: result.section_type,
            mode: 'lesson',
            study_day_id: result.study_day.id,
            question_count: result.total_questions,
        });
    };

    return (
        <>
            <Head title="Practice Result" />
            <div className="mx-auto grid w-full max-w-7xl gap-6 p-4 md:p-8 xl:grid-cols-[0.42fr_0.58fr]">
                <section className="grid gap-6">
                    <SpotlightCard className="p-6 md:p-7">
                        {isLessonMiniTest && (
                            <div
                                className={cn(
                                    'mb-6 rounded-lg border p-4',
                                    result.passed
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100'
                                        : 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-100',
                                )}
                            >
                                <div className="flex items-center gap-3">
                                    {result.passed ? (
                                        <Trophy className="size-6 text-emerald-600" />
                                    ) : (
                                        <XCircle className="size-6 text-rose-600" />
                                    )}
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {result.passed
                                                ? 'Mini-test lulus'
                                                : 'Mini-test belum lulus'}
                                        </p>
                                        <p className="mt-1 text-sm">
                                            {result.passed
                                                ? 'Day ini sudah bisa ditandai selesai.'
                                                : 'Syarat mini-test adalah benar semua.'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        <p className="text-sm font-medium text-indigo-600">
                            Score
                        </p>
                        <div className="mt-3 text-6xl font-semibold text-slate-950 dark:text-white">
                            <CountUp value={result.score} suffix="%" />
                        </div>
                        <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {masteryMessage}
                        </p>

                        <div className="mt-6 grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                            <ResultMetric
                                icon={CheckCircle2}
                                label="Correct"
                                value={`${result.correct_answers}/${result.total_questions}`}
                                tone="emerald"
                            />
                            <ResultMetric
                                icon={Clock}
                                label="Duration"
                                value={`${result.duration_minutes} min`}
                                tone="slate"
                            />
                            <ResultMetric
                                icon={Target}
                                label="Status"
                                value={result.accuracy_label}
                                tone={result.passed ? 'emerald' : 'rose'}
                            />
                        </div>

                        <div className="mt-7 h-56">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={chartData}
                                        dataKey="value"
                                        innerRadius={55}
                                        outerRadius={82}
                                        paddingAngle={4}
                                    >
                                        {chartData.map((item) => (
                                            <Cell
                                                key={item.name}
                                                fill={item.fill}
                                            />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>

                        <div className="mt-5 flex flex-wrap gap-2">
                            {isLessonMiniTest && !result.passed ? (
                                <Button onClick={retryMiniTest}>
                                    <RotateCcw className="size-4" />
                                    Ulangi Mini-test
                                </Button>
                            ) : (
                                <Button asChild>
                                    <Link href={practiceSetup()}>
                                        <RotateCcw className="size-4" />
                                        Practice Again
                                    </Link>
                                </Button>
                            )}
                            <Button asChild variant="outline">
                                <Link href={mistakesIndex()}>
                                    Review Mistakes
                                </Link>
                            </Button>
                            {isLessonMiniTest && result.study_day && (
                                <Button asChild variant="outline">
                                    <Link
                                        href={
                                            result.passed
                                                ? studyPathIndex()
                                                : lessonShow(
                                                      result.study_day.id,
                                                  )
                                        }
                                    >
                                        {result.passed
                                            ? 'Study Path'
                                            : 'Back to Lesson'}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </SpotlightCard>
                </section>

                <SpotlightCard className="p-6 md:p-7">
                    <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-primary dark:bg-indigo-950 dark:text-indigo-200">
                                <BarChart3 className="size-4" />
                                Answer Review
                            </div>
                            <h1 className="mt-4 text-2xl font-semibold">
                                Diagnosis per soal
                            </h1>
                            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Buka lagi alasan setiap jawaban supaya pola
                                salah tidak ikut terbawa ke sesi berikutnya.
                            </p>
                        </div>

                        <div className="grid grid-cols-3 gap-2 rounded-lg bg-slate-100 p-1 dark:bg-slate-900">
                            {(
                                ['all', 'wrong', 'correct'] as ResultFilter[]
                            ).map((item) => (
                                <button
                                    key={item}
                                    type="button"
                                    onClick={() => setFilter(item)}
                                    className={cn(
                                        'h-9 rounded-md px-3 text-sm font-semibold capitalize transition',
                                        filter === item
                                            ? 'bg-white text-primary shadow-sm dark:bg-slate-950'
                                            : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
                                    )}
                                >
                                    {item}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="mt-6 grid gap-3">
                        {filteredAnswers.map((answer, index) => (
                            <motion.article
                                key={answer.id}
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{
                                    delay: Math.min(index * 0.03, 0.2),
                                }}
                                className={cn(
                                    'rounded-lg border p-4',
                                    answer.is_correct
                                        ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20'
                                        : 'border-rose-200 bg-rose-50/70 dark:border-rose-900 dark:bg-rose-950/20',
                                )}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-md bg-white px-2 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-950 dark:text-slate-300">
                                                #{answer.position}
                                            </span>
                                            <span className="rounded-md bg-white px-2 py-1 text-xs font-semibold text-slate-600 capitalize dark:bg-slate-950 dark:text-slate-300">
                                                {answer.section_type}
                                            </span>
                                            <span className="rounded-md bg-white px-2 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-950 dark:text-slate-300">
                                                {formatQuestionType(
                                                    answer.question_type,
                                                )}
                                            </span>
                                        </div>
                                        <p className="mt-3 leading-7 font-medium text-slate-950 dark:text-white">
                                            {answer.question}
                                        </p>
                                    </div>
                                    <span
                                        className={cn(
                                            'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-sm font-semibold',
                                            answer.is_correct
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
                                        )}
                                    >
                                        {answer.is_correct ? (
                                            <CheckCircle2 className="size-4" />
                                        ) : (
                                            <XCircle className="size-4" />
                                        )}
                                        {answer.is_correct
                                            ? 'Correct'
                                            : 'Wrong'}
                                    </span>
                                </div>

                                {!answer.is_correct && (
                                    <div className="mt-4 grid gap-2 text-sm">
                                        <p className="leading-6 text-slate-700 dark:text-slate-300">
                                            Jawabanmu:{' '}
                                            <span className="font-semibold">
                                                {answer.selected ?? '-'}
                                            </span>
                                        </p>
                                        <p className="leading-6 text-slate-700 dark:text-slate-300">
                                            Jawaban benar:{' '}
                                            <span className="font-semibold">
                                                {answer.correct}
                                            </span>
                                        </p>
                                    </div>
                                )}

                                <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {answer.explanation}
                                </p>
                            </motion.article>
                        ))}
                    </div>
                </SpotlightCard>
            </div>
        </>
    );
}

function ResultMetric({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: typeof CheckCircle2;
    label: string;
    value: string;
    tone: 'emerald' | 'rose' | 'slate';
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
            <div className="flex items-center gap-3">
                <div
                    className={cn(
                        'flex size-9 items-center justify-center rounded-md',
                        tone === 'emerald' &&
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
                        tone === 'rose' &&
                            'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200',
                        tone === 'slate' &&
                            'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
                    )}
                >
                    <Icon className="size-4" />
                </div>
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    {label}
                </p>
            </div>
            <p className="mt-3 font-semibold text-slate-950 dark:text-white">
                {value}
            </p>
        </div>
    );
}

function formatQuestionType(type: string): string {
    return type
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}
