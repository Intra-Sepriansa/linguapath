import { Head, Link, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    ArrowLeft,
    BookOpenCheck,
    CheckCircle2,
    CircleCheck,
    CirclePlay,
    Lightbulb,
    ListChecks,
    PenLine,
    RotateCcw,
    Sparkles,
    Target,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { StaggeredText } from '@/components/reactbits/staggered-text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { start as startPractice } from '@/routes/practice';
import { index as studyPathIndex } from '@/routes/study-path';
import type { SkillType, StudyDaySummary } from '@/types';

type LessonContent = {
    goal?: string;
    pattern?: string;
    concept?: string;
    coach_note?: string;
    guided_steps?: string[];
    examples?: LessonExample[];
    advanced_notes?: string[];
    common_traps?: string[];
    tasks?: string[];
    checklist?: string[];
    practice_items?: LessonPracticeItem[];
};

type LessonExample = {
    label: string;
    incorrect?: string;
    correct: string;
    why: string;
};

type LessonPracticeItem = {
    type: 'choice' | 'rewrite';
    prompt: string;
    instruction: string;
    source?: string;
    options?: string[];
    correct_answer: string;
    accepted_answers?: string[];
    explanation: string;
    success_message: string;
    retry_message: string;
};

type LessonProps = {
    day: StudyDaySummary;
    lesson: {
        id: number | null;
        title: string | null;
        summary: string | null;
        content: LessonContent | null;
        skill_type: SkillType | null;
        question_count: number;
    };
};

export default function LessonShow({ day, lesson }: LessonProps) {
    const blueprint = lessonBlueprint(day, lesson.content);
    const assessment = day.assessment;
    const miniTestLabel =
        assessment.status === 'failed'
            ? 'Ulangi Mini-test'
            : assessment.status === 'passed'
              ? 'Latihan Lagi'
              : 'Mulai Mini-test';
    const start = () =>
        router.post(startPractice.url(), {
            section_type: lesson.skill_type ?? day.focus_skill,
            mode: 'lesson',
            study_day_id: day.id,
            question_count: lesson.question_count || 5,
        });

    return (
        <>
            <Head title={lesson.title ?? day.title} />
            <div className="mx-auto grid w-full max-w-7xl gap-6 p-4 md:p-8">
                <Link
                    href={studyPathIndex()}
                    className="inline-flex w-fit items-center gap-2 text-sm font-semibold text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Kembali ke Study Path
                </Link>

                <section className="rounded-lg border border-violet-100 bg-white p-7 shadow-sm md:p-10 dark:border-indigo-950 dark:bg-slate-950">
                    <div className="inline-flex items-center gap-2 rounded-full bg-violet-100 px-4 py-2 text-sm font-semibold text-slate-800 dark:bg-indigo-950 dark:text-slate-200">
                        <span className="size-2 rounded-full bg-emerald-400" />
                        {day.focus_label} Lesson
                    </div>
                    <h1 className="mt-8 text-4xl leading-tight font-semibold tracking-normal md:text-6xl">
                        Day {day.day_number} -{' '}
                        <span className="text-primary">
                            <StaggeredText text={day.title} />
                        </span>
                    </h1>
                    <div className="mt-6 max-w-4xl border-l-4 border-primary pl-6 text-xl leading-9 text-slate-700 dark:text-slate-300">
                        <span className="font-semibold text-slate-950 dark:text-white">
                            Tujuan:
                        </span>{' '}
                        {blueprint.goal}
                    </div>
                    <p className="mt-6 max-w-4xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        {blueprint.concept}
                    </p>
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <article className="rounded-lg border border-violet-100 bg-violet-50/60 p-7 dark:border-indigo-950 dark:bg-indigo-950/25">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-md bg-indigo-100 text-primary">
                                <BookOpenCheck className="size-5" />
                            </div>
                            <h2 className="text-2xl font-semibold">
                                Pola Utama
                            </h2>
                        </div>
                        <p className="mt-5 text-lg leading-8 text-slate-700 dark:text-slate-300">
                            {blueprint.patternExplanation}
                        </p>
                        <div className="mt-6 rounded-md border border-violet-200 bg-white p-5 text-center dark:border-indigo-900 dark:bg-slate-950">
                            <p className="font-mono text-lg font-semibold tracking-[0.2em] text-primary">
                                {blueprint.pattern}
                            </p>
                        </div>
                    </article>

                    <article className="rounded-lg border border-indigo-200 bg-indigo-100/70 p-7 dark:border-indigo-800 dark:bg-indigo-950/50">
                        <div className="flex items-center gap-3">
                            <CircleCheck className="size-6 fill-indigo-900 text-indigo-900 dark:fill-indigo-200 dark:text-indigo-200" />
                            <h2 className="text-2xl font-semibold">Contoh</h2>
                        </div>
                        <div className="mt-6 grid gap-5">
                            {blueprint.examples.map((example) => (
                                <ExampleBlock
                                    key={example.label}
                                    example={example}
                                />
                            ))}
                        </div>
                    </article>
                </section>

                <section className="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
                    <article className="rounded-lg border border-violet-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                        <div className="flex items-center gap-3">
                            <Target className="size-6 text-primary" />
                            <h2 className="text-2xl font-semibold">
                                Cara Berpikir
                            </h2>
                        </div>
                        <div className="mt-6 grid gap-4">
                            {blueprint.guidedSteps.map((step, index) => (
                                <div
                                    key={step}
                                    className="flex gap-4 rounded-lg border border-violet-100 p-4 dark:border-indigo-950"
                                >
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                        {index + 1}
                                    </div>
                                    <p className="leading-7 text-slate-700 dark:text-slate-300">
                                        {step}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </article>

                    <article className="rounded-lg border border-emerald-100 bg-emerald-50/70 p-7 dark:border-emerald-900 dark:bg-emerald-950/20">
                        <div className="flex items-center gap-3">
                            <Lightbulb className="size-6 text-emerald-700" />
                            <h2 className="text-2xl font-semibold">
                                Catatan Lanjutan
                            </h2>
                        </div>
                        <div className="mt-6 grid gap-4">
                            {blueprint.advancedNotes.map((note) => (
                                <div
                                    key={note}
                                    className="flex items-start gap-3"
                                >
                                    <CheckCircle2 className="mt-1 size-5 shrink-0 text-emerald-700" />
                                    <p className="leading-7 text-slate-700 dark:text-slate-300">
                                        {note}
                                    </p>
                                </div>
                            ))}
                        </div>
                        <h3 className="mt-8 text-lg font-semibold">
                            Jebakan Umum
                        </h3>
                        <div className="mt-4 grid gap-3">
                            {blueprint.commonTraps.map((trap) => (
                                <div
                                    key={trap}
                                    className="rounded-md border border-emerald-100 bg-white/70 p-3 text-sm leading-6 text-slate-700 dark:border-emerald-900 dark:bg-slate-950 dark:text-slate-300"
                                >
                                    {trap}
                                </div>
                            ))}
                        </div>
                    </article>
                </section>

                <section>
                    <div className="mb-5 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.18em] text-primary uppercase">
                                Praktik langsung
                            </p>
                            <h2 className="mt-2 text-3xl font-semibold">
                                Coba dulu sebelum mini-test
                            </h2>
                        </div>
                        <p className="max-w-xl leading-7 text-slate-600 dark:text-slate-300">
                            Bagian ini langsung mengoreksi jawabanmu. Kalau
                            benar, lanjutkan. Kalau salah, baca alasan detailnya
                            lalu coba lagi sampai pola grammar-nya terasa jelas.
                        </p>
                    </div>
                    <div className="grid gap-5 lg:grid-cols-2">
                        {blueprint.practiceItems.map((item, index) => (
                            <LessonPracticeCard
                                key={`${item.type}-${item.prompt}`}
                                item={item}
                                index={index}
                            />
                        ))}
                    </div>
                </section>

                <section className="rounded-lg border border-violet-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                    <div className="flex items-center gap-3">
                        <ListChecks className="size-6 text-primary" />
                        <h2 className="text-2xl font-semibold">
                            Kesimpulan Penting
                        </h2>
                    </div>
                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                        {blueprint.takeaways.map((item) => (
                            <div key={item} className="flex items-start gap-3">
                                <CheckCircle2 className="mt-1 size-5 shrink-0 text-emerald-700" />
                                <p className="leading-7 text-slate-700 dark:text-slate-300">
                                    {item}
                                </p>
                            </div>
                        ))}
                    </div>
                    <div className="mt-8 grid gap-6 border-t border-violet-100 pt-8 lg:grid-cols-[1fr_0.85fr] dark:border-indigo-950">
                        <div>
                            <h3 className="text-lg font-semibold">
                                Latihan Terarah Sebelum Mini-test
                            </h3>
                            <div className="mt-4 grid gap-3">
                                {blueprint.tasks.map((task) => (
                                    <div
                                        key={task}
                                        className="flex items-start gap-3 text-slate-700 dark:text-slate-300"
                                    >
                                        <CheckCircle2 className="mt-1 size-5 shrink-0 text-emerald-700" />
                                        <p className="leading-7">{task}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-lg border border-indigo-100 bg-indigo-50/60 p-5 dark:border-indigo-900 dark:bg-indigo-950/30">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm font-semibold tracking-wide text-primary uppercase">
                                        Mini-test akhir pelajaran
                                    </p>
                                    <p className="mt-3 text-3xl font-semibold">
                                        {lesson.question_count || 5} soal
                                    </p>
                                </div>
                                <AssessmentBadge assessment={assessment} />
                            </div>
                            <div className="mt-5 grid gap-3 rounded-lg border border-white/70 bg-white/70 p-4 dark:border-indigo-900 dark:bg-slate-950/60">
                                <div className="flex items-center justify-between gap-3 text-sm font-semibold">
                                    <span>Syarat lulus</span>
                                    <span className="text-emerald-700 dark:text-emerald-300">
                                        Benar semua
                                    </span>
                                </div>
                                <div className="h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                    <div
                                        className={cn(
                                            'h-full rounded-full',
                                            assessment.status === 'passed'
                                                ? 'bg-emerald-500'
                                                : assessment.status === 'failed'
                                                  ? 'bg-rose-500'
                                                  : 'bg-indigo-500',
                                        )}
                                        style={{
                                            width: `${assessment.score ?? 0}%`,
                                        }}
                                    />
                                </div>
                                <p className="text-sm leading-6 text-slate-700 dark:text-slate-300">
                                    {assessment.score === null
                                        ? 'Belum ada attempt mini-test.'
                                        : `${assessment.correct_answers}/${assessment.total_questions} benar · ${assessment.score}%`}
                                </p>
                            </div>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Button
                                    onClick={start}
                                    className="h-12 bg-primary px-7 shadow-sm shadow-indigo-500/25 hover:bg-primary/90"
                                >
                                    {assessment.status === 'failed' ? (
                                        <RotateCcw className="size-4" />
                                    ) : (
                                        <CirclePlay className="size-4" />
                                    )}
                                    {miniTestLabel}
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

function AssessmentBadge({
    assessment,
}: {
    assessment: StudyDaySummary['assessment'];
}) {
    if (assessment.status === 'passed') {
        return (
            <span className="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">
                <CheckCircle2 className="size-4" />
                Lulus
            </span>
        );
    }

    if (assessment.status === 'failed') {
        return (
            <span className="inline-flex items-center gap-2 rounded-full bg-rose-100 px-3 py-1.5 text-sm font-semibold text-rose-700 dark:bg-rose-950 dark:text-rose-200">
                <XCircle className="size-4" />
                Tidak lulus
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 dark:bg-slate-900 dark:text-slate-300">
            <CirclePlay className="size-4" />
            Belum tes
        </span>
    );
}

function LessonPracticeCard({
    item,
    index,
}: {
    item: LessonPracticeItem;
    index: number;
}) {
    const [selected, setSelected] = useState<string | null>(null);
    const [written, setWritten] = useState('');
    const [checked, setChecked] = useState(false);
    const isChoice = item.type === 'choice';
    const activeAnswer = isChoice ? selected : written;
    const isCorrect = checked
        ? isAcceptedAnswer(activeAnswer ?? '', item)
        : null;

    const checkRewrite = () => {
        setChecked(true);
    };

    const chooseOption = (option: string) => {
        setSelected(option);
        setChecked(true);
    };

    return (
        <SpotlightCard className="p-6">
            <div className="flex items-start gap-4">
                <div className="flex size-11 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-primary dark:bg-indigo-950">
                    {isChoice ? (
                        <Sparkles className="size-5" />
                    ) : (
                        <PenLine className="size-5" />
                    )}
                </div>
                <div>
                    <p className="text-sm font-semibold text-primary">
                        Latihan {index + 1} -{' '}
                        {isChoice ? 'Pilih kalimat benar' : 'Ketik ulang'}
                    </p>
                    <h3 className="mt-2 text-xl font-semibold">
                        {item.prompt}
                    </h3>
                    <p className="mt-2 leading-7 text-slate-600 dark:text-slate-300">
                        {item.instruction}
                    </p>
                </div>
            </div>

            {item.source && (
                <div className="mt-5 rounded-lg border border-rose-100 bg-rose-50/70 p-4 text-slate-800 dark:border-rose-900 dark:bg-rose-950/20 dark:text-slate-200">
                    <p className="text-xs font-semibold tracking-wide text-rose-700 uppercase">
                        Kalimat awal
                    </p>
                    <p className="mt-2 text-lg">{item.source}</p>
                </div>
            )}

            {isChoice ? (
                <div className="mt-5 grid gap-3">
                    {(item.options ?? []).map((option) => {
                        const optionCorrect =
                            normalizeAnswer(option) ===
                            normalizeAnswer(item.correct_answer);
                        const optionSelected = selected === option;
                        const showCorrect = checked && optionCorrect;
                        const showWrong =
                            checked && optionSelected && !optionCorrect;

                        return (
                            <button
                                key={option}
                                type="button"
                                onClick={() => chooseOption(option)}
                                className={cn(
                                    'rounded-lg border p-4 text-left leading-7 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-indigo-50 dark:border-indigo-950 dark:hover:bg-indigo-950/50',
                                    showCorrect &&
                                        'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100',
                                    showWrong &&
                                        'border-rose-300 bg-rose-50 text-rose-950 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-100',
                                )}
                            >
                                {option}
                            </button>
                        );
                    })}
                </div>
            ) : (
                <div className="mt-5 grid gap-3">
                    <Input
                        value={written}
                        onChange={(event) => {
                            setWritten(event.target.value);
                            setChecked(false);
                        }}
                        placeholder="Ketik kalimat yang sudah diperbaiki..."
                        className="h-12 bg-white text-base dark:bg-slate-950"
                    />
                    <Button
                        type="button"
                        onClick={checkRewrite}
                        disabled={!written.trim()}
                        className="w-fit bg-primary hover:bg-primary/90"
                    >
                        Koreksi Jawaban
                    </Button>
                </div>
            )}

            <AnimatePresence>
                {checked && (
                    <motion.div
                        initial={{ opacity: 0, y: 8, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -4 }}
                        transition={{ duration: 0.2 }}
                        className={cn(
                            'mt-5 rounded-lg border p-4',
                            isCorrect
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100'
                                : 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-100',
                        )}
                    >
                        <p className="font-semibold">
                            {isCorrect
                                ? item.success_message
                                : item.retry_message}
                        </p>
                        {!isCorrect && (
                            <p className="mt-3 text-sm leading-6">
                                Jawaban yang benar:{' '}
                                <span className="font-semibold">
                                    {item.correct_answer}
                                </span>
                            </p>
                        )}
                        <p className="mt-3 leading-7">{item.explanation}</p>
                    </motion.div>
                )}
            </AnimatePresence>
        </SpotlightCard>
    );
}

function ExampleBlock({ example }: { example: LessonExample }) {
    return (
        <div>
            <p className="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                {example.label}
            </p>
            {example.incorrect && (
                <div className="mb-3">
                    <span className="inline-flex rounded-md bg-rose-100 px-3 py-1 text-sm font-medium text-rose-700">
                        Salah
                    </span>
                    <p className="mt-2 rounded-md bg-white/70 p-3 text-lg text-indigo-950 italic dark:bg-slate-950 dark:text-indigo-100">
                        {example.incorrect}
                    </p>
                </div>
            )}
            <div>
                <span className="inline-flex rounded-md bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-700">
                    Benar
                </span>
                <p
                    className={cn(
                        'mt-2 rounded-md bg-white/80 p-3 text-lg text-indigo-950 dark:bg-slate-950 dark:text-indigo-100',
                        !example.incorrect && 'font-medium',
                    )}
                >
                    {example.correct}
                </p>
            </div>
            <p className="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-300">
                {example.why}
            </p>
        </div>
    );
}

function lessonBlueprint(day: StudyDaySummary, content: LessonContent | null) {
    const fallback = defaultBlueprint(day);

    return {
        goal: content?.goal ?? fallback.goal,
        concept: content?.concept ?? fallback.concept,
        coachNote: content?.coach_note ?? fallback.coachNote,
        pattern: content?.pattern ?? fallback.pattern,
        patternExplanation: content?.coach_note ?? fallback.patternExplanation,
        guidedSteps: content?.guided_steps?.length
            ? content.guided_steps
            : fallback.guidedSteps,
        examples: content?.examples?.length
            ? content.examples
            : fallback.examples,
        advancedNotes: content?.advanced_notes?.length
            ? content.advanced_notes
            : fallback.advancedNotes,
        commonTraps: content?.common_traps?.length
            ? content.common_traps
            : fallback.commonTraps,
        tasks: content?.tasks?.length ? content.tasks : fallback.tasks,
        practiceItems: content?.practice_items?.length
            ? content.practice_items
            : fallback.practiceItems,
        takeaways: content?.checklist?.length
            ? content.checklist
            : fallback.takeaways,
    };
}

function defaultBlueprint(day: StudyDaySummary) {
    if (day.title.toLowerCase().includes('parallel')) {
        return {
            goal: 'Gunakan bentuk grammar yang sejajar dalam daftar, perbandingan, dan ide yang terhubung agar kalimat jelas dan seimbang.',
            concept:
                'Parallel structure sering muncul di TOEFL karena tes ini ingin melihat apakah kamu bisa menjaga bentuk ide tetap sama. Intinya: kalau item pertama memakai gerund, item berikutnya juga gerund. Kalau item pertama noun, item berikutnya juga noun.',
            coachNote:
                'Saat kalimat membuat daftar atau membandingkan beberapa hal, semua bagian yang punya fungsi sama harus memakai bentuk grammar yang sama.',
            pattern: 'NOUN + NOUN + NOUN  /  GERUND + GERUND + GERUND',
            patternExplanation:
                'Ketika menghubungkan item dalam daftar, membandingkan item, atau menyambungkan klausa, samakan bentuk grammar setiap item.',
            guidedSteps: [
                'Cari sinyal penghubung: and, but, or, both/and, either/or, neither/nor, not only/but also, more than, less than, atau as much as.',
                'Garisbawahi setiap item yang dihubungkan oleh sinyal itu, lalu beri label bentuknya: noun, adjective, infinitive, gerund, verb phrase, atau clause.',
                'Perbaiki bagian yang tidak sejajar, lalu baca ulang seluruh kalimat untuk memastikan artinya tetap logis.',
            ],
            examples: [
                {
                    label: 'Bentuk Daftar',
                    incorrect: 'She likes to run, swimming, and hiking.',
                    correct: 'She likes running, swimming, and hiking.',
                    why: 'Tiga aktivitas memakai bentuk gerund: running, swimming, dan hiking. Karena bentuknya sama, kalimat menjadi sejajar.',
                },
                {
                    label: 'Correlative Conjunction',
                    incorrect:
                        'The lecture was not only informative but also inspired the class.',
                    correct:
                        'The lecture was not only informative but also inspiring.',
                    why: 'Not only dan but also menghubungkan dua bentuk adjective: informative dan inspiring.',
                },
            ],
            advancedNotes: [
                'Di TOEFL Structure, pilihan yang salah sering mencampur infinitive dengan gerund, atau adjective dengan verb phrase.',
                'Pada comparison, dua sisi yang dibandingkan harus punya fungsi grammar yang sama. Jangan membandingkan noun dengan clause atau adjective dengan verb phrase.',
                'Parallelism juga membantu Writing dan Speaking karena kalimat yang seimbang lebih mudah dipahami ketika waktu terbatas.',
            ],
            commonTraps: [
                'Memilih jawaban yang terdengar formal, tetapi merusak pola daftar.',
                'Mengabaikan pasangan not only/but also, either/or, atau neither/nor.',
                'Menganggap semua kata -ing sebagai verb, padahal bisa berfungsi sebagai gerund.',
            ],
            tasks: [
                'Tandai setiap kata penghubung yang membuat daftar atau perbandingan.',
                'Samakan bentuk grammar pada semua item yang punya fungsi sama.',
                'Kerjakan mini-test setelah kamu bisa menjelaskan alasan perbaikannya.',
            ],
            practiceItems: fallbackPracticeItems(
                'She likes running, swimming, and hiking.',
            ),
            takeaways: [
                'Cek daftar yang dihubungkan oleh and, but, atau or.',
                'Pastikan correlative conjunction membingkai bentuk yang sejajar.',
                'Pastikan elemen yang dibandingkan punya bentuk grammar yang sama.',
                'Baca ulang kalimat yang sudah diperbaiki untuk menangkap pola yang masih janggal.',
            ],
        };
    }

    if (day.focus_skill === 'listening') {
        return {
            goal: day.objective,
            concept:
                'TOEFL Listening menilai kemampuan menangkap makna, sikap, dan implikasi, bukan sekadar kata yang terdengar. Fokus utama: pahami maksud pembicara.',
            coachNote:
                'Jawaban benar biasanya memakai paraphrase, bukan menyalin kata dari transcript.',
            pattern: 'INTENT -> PARAPHRASE -> ELIMINATE TRAPS',
            patternExplanation:
                'Dengarkan maksud pembicara, ubah menjadi arti sederhana, lalu buang opsi yang hanya mengulang kata tetapi tidak sesuai maksud.',
            guidedSteps: [
                'Temukan respons terakhir atau opini paling kuat dalam percakapan.',
                'Ulangi maknanya dengan kata-katamu sendiri sebelum melihat opsi jawaban.',
                'Tolak opsi yang mengulang kosakata, tetapi mengubah sikap pembicara.',
            ],
            examples: [
                {
                    label: 'Makna',
                    incorrect:
                        'Speaker says, "I can hardly keep up." Choose: The speaker is keeping up easily.',
                    correct: 'The speaker is having difficulty following.',
                    why: 'Kata "hardly" menunjukkan kesulitan. Jadi maknanya bukan mudah mengikuti, tetapi sulit mengikuti.',
                },
            ],
            advancedNotes: [
                'Penanda nada seperti actually, probably, hardly, dan I wish sering menentukan jawaban.',
                'Jika dua opsi terdengar mirip, pilih yang menjaga sikap pembicara tetap sama.',
                'Untuk lecture, ikuti transisi seperti however, therefore, for example, dan as a result.',
            ],
            commonTraps: [
                'Memilih opsi hanya karena mengulang kata dari audio.',
                'Melewatkan kata negatif atau penanda ketidaksetujuan halus.',
                'Lupa bahwa TOEFL sering menanyakan makna tersirat.',
            ],
            tasks: [
                'Dengarkan maksud pembicara sebelum membaca opsi terlalu dalam.',
                'Tulis satu paraphrase pendek dari respons utama.',
                'Kerjakan mini-test setelah kamu bisa menolak opsi yang hanya mengulang kata.',
            ],
            practiceItems: fallbackPracticeItems(
                'The speaker is having difficulty following.',
            ),
            takeaways: [
                'Jangan memilih jawaban hanya karena ada kata yang sama dengan audio.',
                'Ubah idiom dan ketidaksetujuan halus menjadi makna langsung.',
                'Gunakan transisi untuk memprediksi bagian yang akan ditanyakan.',
            ],
        };
    }

    if (day.focus_skill === 'reading') {
        return {
            goal: day.objective,
            concept:
                'TOEFL Reading berbasis bukti. Jawaban yang benar harus punya dukungan dari passage, bukan hanya terasa masuk akal.',
            coachNote:
                'Urutannya: keyword pertanyaan dulu, bukti di passage kedua, opsi jawaban terakhir.',
            pattern: 'KEYWORD -> EVIDENCE -> ANSWER',
            patternExplanation:
                'Ubah pertanyaan menjadi target keyword, scan kalimat yang relevan, lalu cocokkan jawaban dengan bukti eksplisit atau tersirat.',
            guidedSteps: [
                'Klasifikasikan pertanyaan: main idea, detail, vocabulary, reference, atau inference.',
                'Temukan kalimat atau paragraf yang berisi bukti.',
                'Pilih jawaban yang menjaga makna passage tanpa menambah asumsi dari luar.',
            ],
            examples: [
                {
                    label: 'Cocokkan Bukti',
                    correct:
                        'If the passage says review reduces repeated errors, the answer must mention review or error reduction.',
                    why: 'Opsi benar tetap berada di dalam batas bukti yang disebutkan passage.',
                },
            ],
            advancedNotes: [
                'Pilihan salah sering memakai kata ekstrem seperti always, never, dan completely.',
                'Jawaban main idea harus mencakup seluruh paragraf, bukan hanya satu detail.',
                'Jawaban inference tetap harus didukung bukti, meskipun passage tidak mengatakannya secara langsung.',
            ],
            commonTraps: [
                'Memilih jawaban yang benar secara umum, tetapi tidak didukung passage.',
                'Mengira satu detail sebagai main idea.',
                'Menambahkan asumsi yang tidak pernah dinyatakan penulis.',
            ],
            tasks: [
                'Ubah pertanyaan menjadi keyword yang bisa dicari di passage.',
                'Temukan kalimat bukti sebelum memilih opsi.',
                'Kerjakan mini-test setelah kamu bisa menyebutkan bukti untuk jawabanmu.',
            ],
            practiceItems: fallbackPracticeItems(
                'The answer must be supported by the passage.',
            ),
            takeaways: [
                'Cari bukti sebelum membaca semua opsi terlalu dalam.',
                'Hindari jawaban yang benar secara umum tetapi tidak ada di passage.',
                'Gunakan fungsi paragraf untuk membedakan main idea dan detail.',
            ],
        };
    }

    return {
        goal: day.objective,
        concept:
            'Structure menguji apakah kalimat lengkap, logis, dan seimbang secara grammar. Mulai dari subject, verb, connector, lalu bentuk kata.',
        coachNote:
            'Kalimat lengkap membutuhkan subject yang jelas, verb yang valid, dan connector yang tidak membuat struktur rusak.',
        pattern: 'SUBJECT + VERB + COMPLETE THOUGHT',
        patternExplanation:
            'Cek inti kalimat lebih dulu sebelum fokus ke detail grammar kecil.',
        guidedSteps: [
            'Temukan subject dan main verb terlebih dahulu.',
            'Cek apakah connector membuat dependent clause atau clause tambahan.',
            'Cocokkan jawaban kosong dengan fungsi grammar yang dibutuhkan kalimat.',
        ],
        examples: [
            {
                label: 'Sentence Core',
                incorrect: 'The students in the library every morning.',
                correct: 'The students study in the library every morning.',
                why: 'Kalimat awal punya subject, tetapi tidak punya main verb. Kata study melengkapi inti kalimat.',
            },
        ],
        advancedNotes: [
            'Prepositional phrase di antara subject dan verb sering mengecoh subject-verb agreement.',
            'Clause connector bisa membuat clause menjadi dependent, sehingga kalimat masih membutuhkan main clause.',
            'Verb form, word order, dan missing words sering diuji bersamaan.',
        ],
        commonTraps: [
            'Menganggap prepositional phrase sebagai subject.',
            'Memilih word form sebelum mengecek apakah main verb sudah ada.',
            'Mengabaikan apakah connector membuat clause menjadi tidak lengkap.',
        ],
        tasks: [
            'Garisbawahi subject dan main verb pada setiap contoh.',
            'Jelaskan fungsi grammar dari jawaban benar sebelum lanjut.',
            'Kerjakan mini-test setelah kamu bisa melihat inti kalimat dengan cepat.',
        ],
        practiceItems: fallbackPracticeItems(
            'The students study in the library every morning.',
        ),
        takeaways: [
            'Temukan subject dan verb sebelum memilih jawaban.',
            'Abaikan prepositional phrase saat mengecek agreement.',
            'Pastikan kalimat yang selesai punya satu main idea yang jelas.',
        ],
    };
}

function fallbackPracticeItems(correctAnswer: string): LessonPracticeItem[] {
    return [
        {
            type: 'choice',
            prompt: 'Pilih kalimat yang paling benar.',
            instruction:
                'Cari inti kalimat terlebih dahulu, lalu pilih jawaban yang lengkap dan logis.',
            options: [
                correctAnswer,
                'The students in the library every morning.',
            ],
            correct_answer: correctAnswer,
            accepted_answers: [correctAnswer],
            explanation:
                'Jawaban benar punya struktur yang lengkap dan makna yang jelas.',
            success_message: 'Bagus. Pola utamanya sudah kamu tangkap.',
            retry_message:
                'Belum tepat. Cek lagi apakah kalimat punya subject dan verb lengkap.',
        },
    ];
}

function isAcceptedAnswer(answer: string, item: LessonPracticeItem) {
    return (item.accepted_answers ?? [item.correct_answer]).some(
        (accepted) => normalizeAnswer(accepted) === normalizeAnswer(answer),
    );
}

function normalizeAnswer(answer: string) {
    return answer
        .trim()
        .toLowerCase()
        .replace(/[.,!?;:]/g, '')
        .replace(/\s+/g, ' ');
}

LessonShow.layout = {
    breadcrumbs: [{ title: 'Study Path', href: studyPathIndex() }],
};
