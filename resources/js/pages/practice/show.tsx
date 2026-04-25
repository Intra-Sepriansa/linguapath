import { Head, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    ArrowLeft,
    ArrowRight,
    BookOpenText,
    CheckCircle2,
    CircleAlert,
    Flag,
    Headphones,
    Layers3,
    Send,
    Sparkles,
    Timer,
    Trophy,
    XCircle,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { useEffect, useMemo, useState } from 'react';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    answer as answerRoute,
    finish as finishRoute,
    setup as practiceSetup,
} from '@/routes/practice';
import { usePracticeStore } from '@/stores/practice-store';
import type {
    PracticeQuestion,
    PracticeSessionPayload,
    SkillType,
} from '@/types';

type QuestionStatus =
    | 'current'
    | 'correct'
    | 'wrong'
    | 'answered'
    | 'flagged'
    | 'open';

const sectionIcons: Record<
    Exclude<SkillType, 'vocabulary'>,
    ComponentType<{ className?: string }>
> = {
    structure: Sparkles,
    listening: Headphones,
    reading: BookOpenText,
    mixed: Layers3,
};

const sectionStyles: Record<
    Exclude<SkillType, 'vocabulary'>,
    { badge: string; bar: string; panel: string }
> = {
    structure: {
        badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        bar: 'bg-indigo-500',
        panel: 'border-indigo-200 bg-indigo-50/80 dark:border-indigo-900 dark:bg-indigo-950/20',
    },
    listening: {
        badge: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
        bar: 'bg-cyan-500',
        panel: 'border-cyan-200 bg-cyan-50/80 dark:border-cyan-900 dark:bg-cyan-950/20',
    },
    reading: {
        badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        bar: 'bg-emerald-500',
        panel: 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900 dark:bg-emerald-950/20',
    },
    mixed: {
        badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        bar: 'bg-amber-500',
        panel: 'border-amber-200 bg-amber-50/80 dark:border-amber-900 dark:bg-amber-950/20',
    },
};

export default function PracticeShow({
    session,
}: {
    session: PracticeSessionPayload;
}) {
    const {
        currentIndex,
        selectedOptions,
        flaggedQuestions,
        syncSession,
        setCurrentIndex,
        selectOption,
        toggleFlag,
        elapsedSeconds,
    } = usePracticeStore();
    const [, setTimerTick] = useState(0);
    const serverSelectedOptions = useMemo<Record<number, number>>(
        () =>
            Object.fromEntries(
                session.questions
                    .filter((question) => question.selected_option_id !== null)
                    .map((question) => [
                        question.id,
                        question.selected_option_id as number,
                    ]),
            ),
        [session.questions],
    );

    useEffect(() => {
        syncSession(session.id, serverSelectedOptions);
    }, [serverSelectedOptions, session.id, syncSession]);

    useEffect(() => {
        const interval = window.setInterval(
            () => setTimerTick((value) => value + 1),
            1000,
        );

        return () => window.clearInterval(interval);
    }, []);

    const safeIndex = Math.min(
        Math.max(currentIndex, 0),
        Math.max(session.questions.length - 1, 0),
    );
    const question = session.questions[safeIndex] ?? session.questions[0];
    const selected = question
        ? (selectedOptions[question.id] ?? question.selected_option_id)
        : null;
    const hasServerFeedback = question?.is_correct !== null;
    const optimisticAnsweredCount = session.questions.filter(
        (item) =>
            item.selected_option_id !== null ||
            selectedOptions[item.id] !== undefined,
    ).length;
    const flaggedCount = Object.values(flaggedQuestions).filter(Boolean).length;
    const allAnswered = optimisticAnsweredCount === session.total_questions;
    const progress = Math.round(
        (optimisticAnsweredCount / session.total_questions) * 100,
    );
    const currentSection = normalizeSection(
        question?.section_type ?? session.section_type,
    );
    const CurrentIcon = sectionIcons[currentSection];
    const activeStyle = sectionStyles[currentSection];
    const elapsedLabel = formatSeconds(elapsedSeconds());

    const submitAnswer = (optionId: number) => {
        if (!question || hasServerFeedback) {
            return;
        }

        selectOption(question.id, optionId);
        router.post(
            answerRoute.url(session.id),
            {
                question_id: question.id,
                selected_option_id: optionId,
                time_spent_seconds: elapsedSeconds(),
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const finishPractice = () => {
        router.post(finishRoute.url(session.id), {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Practice Room" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="grid gap-6 xl:grid-cols-[1fr_0.36fr]">
                    <SpotlightCard className="p-6 md:p-7">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <div
                                    className={cn(
                                        'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold',
                                        activeStyle.badge,
                                    )}
                                >
                                    <CurrentIcon className="size-4" />
                                    {sectionLabel(currentSection)}
                                </div>
                                <h1 className="mt-4 text-3xl leading-tight font-semibold text-slate-950 md:text-4xl dark:text-white">
                                    Practice room
                                </h1>
                                <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    Jawab satu per satu, baca koreksi langsung,
                                    lalu lanjutkan sampai seluruh sesi selesai.
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <StatusPill icon={Timer} label={elapsedLabel} />
                                <StatusPill
                                    icon={CheckCircle2}
                                    label={`${optimisticAnsweredCount}/${session.total_questions} terjawab`}
                                />
                                <StatusPill
                                    icon={Flag}
                                    label={`${flaggedCount} ditandai`}
                                />
                            </div>
                        </div>

                        <div className="mt-6 h-2.5 rounded-full bg-slate-100 dark:bg-slate-800">
                            <motion.div
                                initial={{ width: 0 }}
                                animate={{ width: `${progress}%` }}
                                transition={{ duration: 0.35 }}
                                className={cn(
                                    'h-2.5 rounded-full',
                                    activeStyle.bar,
                                )}
                            />
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-5">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-medium text-indigo-600">
                                    Finish
                                </p>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    {allAnswered
                                        ? 'Semua soal sudah siap dinilai.'
                                        : `${session.total_questions - optimisticAnsweredCount} soal belum dijawab.`}
                                </p>
                            </div>
                            <Trophy className="size-6 text-amber-500" />
                        </div>
                        <Button
                            className="mt-5 h-11 w-full"
                            disabled={!allAnswered}
                            onClick={finishPractice}
                        >
                            <Send className="size-4" />
                            Lihat Hasil
                        </Button>
                    </SpotlightCard>
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.28fr_1fr]">
                    <SpotlightCard className="p-5">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-medium text-indigo-600">
                                    Navigator
                                </p>
                                <h2 className="mt-1 font-semibold">
                                    {session.total_questions} soal
                                </h2>
                            </div>
                            <CircleAlert className="size-5 text-slate-400" />
                        </div>

                        <div className="mt-5 grid grid-cols-5 gap-2 xl:grid-cols-4">
                            {session.questions.map((item, index) => (
                                <QuestionDot
                                    key={item.id}
                                    question={item}
                                    active={index === safeIndex}
                                    selected={
                                        selectedOptions[item.id] ??
                                        item.selected_option_id
                                    }
                                    flagged={Boolean(flaggedQuestions[item.id])}
                                    onClick={() => setCurrentIndex(index)}
                                />
                            ))}
                        </div>

                        <div className="mt-6 grid gap-2 text-xs text-slate-500">
                            <Legend color="bg-emerald-500" label="Benar" />
                            <Legend color="bg-rose-500" label="Salah" />
                            <Legend color="bg-amber-500" label="Ditandai" />
                        </div>
                    </SpotlightCard>

                    <AnimatePresence mode="wait">
                        {question && (
                            <motion.div
                                key={question.id}
                                initial={{ opacity: 0, y: 14, scale: 0.98 }}
                                animate={{ opacity: 1, y: 0, scale: 1 }}
                                exit={{ opacity: 0, y: -10, scale: 0.98 }}
                                transition={{ duration: 0.22 }}
                            >
                                <SpotlightCard className="p-5 md:p-7">
                                    <QuestionHeader
                                        question={question}
                                        activeStyle={activeStyle}
                                        flagged={Boolean(
                                            flaggedQuestions[question.id],
                                        )}
                                        onToggleFlag={() =>
                                            toggleFlag(question.id)
                                        }
                                    />

                                    {question.passage_text && (
                                        <SourcePanel
                                            tone="reading"
                                            title="Passage"
                                            text={question.passage_text}
                                        />
                                    )}
                                    {question.transcript && (
                                        <SourcePanel
                                            tone="listening"
                                            title="Transcript"
                                            text={question.transcript}
                                        />
                                    )}

                                    <h2 className="mt-6 text-xl leading-8 font-semibold text-slate-950 md:text-2xl dark:text-white">
                                        {question.question_text}
                                    </h2>

                                    <div className="mt-6 grid gap-3">
                                        {question.options.map((option) => {
                                            const isSelected =
                                                selected === option.id;
                                            const isCorrect =
                                                hasServerFeedback &&
                                                question.correct_option_id ===
                                                    option.id;
                                            const isWrongSelection =
                                                hasServerFeedback &&
                                                isSelected &&
                                                question.correct_option_id !==
                                                    option.id;

                                            return (
                                                <motion.button
                                                    key={option.id}
                                                    type="button"
                                                    disabled={hasServerFeedback}
                                                    whileHover={
                                                        hasServerFeedback
                                                            ? undefined
                                                            : { x: 4 }
                                                    }
                                                    whileTap={
                                                        hasServerFeedback
                                                            ? undefined
                                                            : { scale: 0.99 }
                                                    }
                                                    onClick={() =>
                                                        submitAnswer(option.id)
                                                    }
                                                    className={cn(
                                                        'flex min-h-16 items-center gap-4 rounded-lg border p-4 text-left text-sm transition disabled:cursor-default',
                                                        'border-slate-200 bg-white hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-indigo-950/20',
                                                        isSelected &&
                                                            'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30',
                                                        isCorrect &&
                                                            'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100',
                                                        isWrongSelection &&
                                                            'border-rose-300 bg-rose-50 text-rose-950 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-100',
                                                    )}
                                                >
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-slate-100 font-semibold text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                                        {option.label}
                                                    </span>
                                                    <span className="min-w-0 flex-1 leading-6">
                                                        {option.text}
                                                    </span>
                                                    {isCorrect && (
                                                        <CheckCircle2 className="size-5 shrink-0 text-emerald-600" />
                                                    )}
                                                    {isWrongSelection && (
                                                        <XCircle className="size-5 shrink-0 text-rose-600" />
                                                    )}
                                                </motion.button>
                                            );
                                        })}
                                    </div>

                                    <FeedbackPanel
                                        selected={selected}
                                        question={question}
                                    />

                                    <div className="mt-7 flex flex-wrap items-center justify-between gap-3">
                                        <Button
                                            variant="outline"
                                            disabled={safeIndex === 0}
                                            onClick={() =>
                                                setCurrentIndex(safeIndex - 1)
                                            }
                                        >
                                            <ArrowLeft className="size-4" />
                                            Sebelumnya
                                        </Button>
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                toggleFlag(question.id)
                                            }
                                        >
                                            <Flag
                                                className={cn(
                                                    'size-4',
                                                    flaggedQuestions[
                                                        question.id
                                                    ] &&
                                                        'fill-amber-500 text-amber-500',
                                                )}
                                            />
                                            Tandai
                                        </Button>
                                        <Button
                                            disabled={
                                                safeIndex >=
                                                session.questions.length - 1
                                            }
                                            onClick={() =>
                                                setCurrentIndex(safeIndex + 1)
                                            }
                                        >
                                            Berikutnya
                                            <ArrowRight className="size-4" />
                                        </Button>
                                    </div>
                                </SpotlightCard>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </section>
            </div>
        </>
    );
}

function QuestionHeader({
    question,
    activeStyle,
    flagged,
    onToggleFlag,
}: {
    question: PracticeQuestion;
    activeStyle: { badge: string; bar: string; panel: string };
    flagged: boolean;
    onToggleFlag: () => void;
}) {
    return (
        <div className={cn('rounded-lg border p-4', activeStyle.panel)}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-md bg-white px-2.5 py-1 text-sm font-semibold text-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        Soal {question.position}
                    </span>
                    <span
                        className={cn(
                            'rounded-md px-2.5 py-1 text-sm font-semibold',
                            activeStyle.badge,
                        )}
                    >
                        {questionTypeLabel(question.question_type)}
                    </span>
                    <span className="rounded-md bg-white/80 px-2.5 py-1 text-sm font-semibold text-slate-600 capitalize dark:bg-slate-950/60 dark:text-slate-300">
                        {question.difficulty}
                    </span>
                </div>
                <button
                    type="button"
                    onClick={onToggleFlag}
                    className="inline-flex size-10 items-center justify-center rounded-md bg-white text-slate-500 transition hover:text-amber-600 dark:bg-slate-950"
                    aria-pressed={flagged}
                >
                    <Flag
                        className={cn(
                            'size-4',
                            flagged && 'fill-amber-500 text-amber-500',
                        )}
                    />
                </button>
            </div>
        </div>
    );
}

function SourcePanel({
    title,
    text,
    tone,
}: {
    title: string;
    text: string;
    tone: 'listening' | 'reading';
}) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            className={cn(
                'mt-6 rounded-lg border p-4 text-sm leading-7',
                tone === 'listening'
                    ? 'border-cyan-200 bg-cyan-50 text-cyan-950 dark:border-cyan-900 dark:bg-cyan-950/25 dark:text-cyan-100'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100',
            )}
        >
            <p className="mb-2 text-xs font-semibold tracking-wide uppercase opacity-70">
                {title}
            </p>
            {text}
        </motion.div>
    );
}

function FeedbackPanel({
    selected,
    question,
}: {
    selected: number | null;
    question: PracticeQuestion;
}) {
    return (
        <AnimatePresence>
            {selected && (
                <motion.div
                    initial={{ opacity: 0, y: 10, scale: 0.98 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: -6 }}
                    transition={{ duration: 0.2 }}
                    className={cn(
                        'mt-6 rounded-lg border p-5',
                        question.is_correct === true &&
                            'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100',
                        question.is_correct === false &&
                            'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-100',
                        question.is_correct === null &&
                            'border-indigo-100 bg-indigo-50 text-indigo-950 dark:border-indigo-900 dark:bg-indigo-950/25 dark:text-indigo-100',
                    )}
                >
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5">
                            {question.is_correct === true ? (
                                <CheckCircle2 className="size-6 text-emerald-600" />
                            ) : question.is_correct === false ? (
                                <XCircle className="size-6 text-amber-600" />
                            ) : (
                                <Sparkles className="size-6 text-primary" />
                            )}
                        </div>
                        <div>
                            <p className="font-semibold">
                                {question.is_correct === true &&
                                    'Benar. Pola ini sudah kamu kunci.'}
                                {question.is_correct === false &&
                                    'Belum tepat. Ini pola yang perlu dikuatkan.'}
                                {question.is_correct === null &&
                                    'Jawaban sedang dikoreksi...'}
                            </p>
                            {question.is_correct === false &&
                                question.correct_option_text && (
                                    <p className="mt-3 text-sm leading-6">
                                        Jawaban benar:{' '}
                                        <span className="font-semibold">
                                            {question.correct_option_text}
                                        </span>
                                    </p>
                                )}
                            {question.explanation && (
                                <p className="mt-3 leading-7">
                                    {question.explanation}
                                </p>
                            )}
                        </div>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

function QuestionDot({
    question,
    active,
    selected,
    flagged,
    onClick,
}: {
    question: PracticeQuestion;
    active: boolean;
    selected: number | null;
    flagged: boolean;
    onClick: () => void;
}) {
    const status = questionStatus(question, active, selected, flagged);

    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'relative h-10 rounded-md border text-sm font-semibold transition',
                statusClass(status),
            )}
        >
            {question.position}
            {flagged && (
                <span className="absolute -top-1 -right-1 size-2 rounded-full bg-amber-500" />
            )}
        </button>
    );
}

function StatusPill({
    icon: Icon,
    label,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
}) {
    return (
        <div className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
            <Icon className="size-4 text-primary" />
            {label}
        </div>
    );
}

function Legend({ color, label }: { color: string; label: string }) {
    return (
        <div className="flex items-center gap-2">
            <span className={cn('size-2 rounded-full', color)} />
            {label}
        </div>
    );
}

function questionStatus(
    question: PracticeQuestion,
    active: boolean,
    selected: number | null,
    flagged: boolean,
): QuestionStatus {
    if (active) {
        return 'current';
    }

    if (question.is_correct === true) {
        return 'correct';
    }

    if (question.is_correct === false) {
        return 'wrong';
    }

    if (flagged) {
        return 'flagged';
    }

    if (selected) {
        return 'answered';
    }

    return 'open';
}

function statusClass(status: QuestionStatus): string {
    return {
        current:
            'border-primary bg-primary text-primary-foreground shadow-sm shadow-indigo-500/20',
        correct:
            'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
        wrong: 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200',
        answered:
            'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-200',
        flagged:
            'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
        open: 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300',
    }[status];
}

function normalizeSection(
    section: SkillType,
): Exclude<SkillType, 'vocabulary'> {
    return section === 'vocabulary' ? 'mixed' : section;
}

function sectionLabel(section: Exclude<SkillType, 'vocabulary'>): string {
    return {
        structure: 'Structure',
        listening: 'Listening',
        reading: 'Reading',
        mixed: 'Mixed Practice',
    }[section];
}

function questionTypeLabel(type: string): string {
    return type
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatSeconds(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;

    return `${minutes}:${String(remainder).padStart(2, '0')}`;
}

PracticeShow.layout = {
    breadcrumbs: [{ title: 'Practice', href: practiceSetup() }],
};
