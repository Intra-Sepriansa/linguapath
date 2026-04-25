import { Head, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock3,
    Headphones,
    Lock,
    Send,
    Volume2,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    answer as answerExam,
    finish as finishExam,
    finishSection,
} from '@/routes/exam';

type ExamQuestion = {
    id: number;
    answer_id: number;
    position: number;
    section_type: string;
    question_type: string;
    question_text: string;
    passage_text: string | null;
    audio_url: string | null;
    audio_playback_text: string | null;
    audio_source_label: string;
    has_real_audio: boolean;
    playback_limit_exam: number;
    selected_option_id: number | null;
    options: Array<{ id: number; label: string; text: string }>;
};

type ExamSection = {
    id: number;
    section_type: string;
    label: string;
    position: number;
    status: string;
    section_started_at: string | null;
    section_duration_seconds: number;
    section_ends_at: string | null;
    remaining_seconds: number;
    total_questions: number;
    answered_count: number;
};

type ExamPayload = {
    id: number;
    status: string;
    server_now: string;
    exam_started_at: string | null;
    score_disclaimer: string;
    sections: ExamSection[];
    current_section: ExamSection | null;
    locked_sections: string[];
    questions: ExamQuestion[];
};

export default function ExamShow({ exam }: { exam: ExamPayload }) {
    const sectionKey = `${exam.current_section?.id ?? 'result'}-${exam.server_now}`;

    return <ExamShowContent key={sectionKey} exam={exam} />;
}

function ExamShowContent({ exam }: { exam: ExamPayload }) {
    const [index, setIndex] = useState(0);
    const [remainingSeconds, setRemainingSeconds] = useState(
        exam.current_section?.remaining_seconds ?? 0,
    );
    const submittedSectionId = useRef<number | null>(null);
    const question =
        exam.questions[Math.min(index, Math.max(0, exam.questions.length - 1))];
    const answered = useMemo(
        () =>
            exam.questions.filter((item) => item.selected_option_id !== null)
                .length,
        [exam.questions],
    );

    useEffect(() => {
        const interval = window.setInterval(() => {
            setRemainingSeconds((current) => Math.max(0, current - 1));
        }, 1000);

        return () => window.clearInterval(interval);
    }, []);

    useEffect(() => {
        if (
            exam.status !== 'in_progress' ||
            !exam.current_section ||
            remainingSeconds > 0 ||
            submittedSectionId.current === exam.current_section.id
        ) {
            return;
        }

        submittedSectionId.current = exam.current_section.id;
        router.post(
            finishSection.url(exam.id),
            {},
            { preserveScroll: true, preserveState: false },
        );
    }, [exam, remainingSeconds]);

    const submitAnswer = (optionId: number) => {
        if (
            !question ||
            question.selected_option_id !== null ||
            remainingSeconds <= 0
        ) {
            return;
        }

        router.post(
            answerExam.url(exam.id),
            {
                answer_id: question.answer_id,
                selected_option_id: optionId,
                time_spent_seconds: 0,
            },
            { preserveScroll: true },
        );
    };

    const playAudio = () => {
        if (question?.audio_url) {
            void new Audio(question.audio_url).play();

            return;
        }

        if (!question?.audio_playback_text) {
            return;
        }

        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(
            new SpeechSynthesisUtterance(question.audio_playback_text),
        );
    };

    return (
        <>
            <Head title="Exam Simulation" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-indigo-600">
                                Active section
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {exam.current_section?.label ?? 'Review'}
                            </h1>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {exam.sections.map((section) => (
                                <span
                                    key={section.id}
                                    className="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-2 text-sm dark:bg-slate-900"
                                >
                                    {section.status === 'locked' ? (
                                        <Lock className="size-4" />
                                    ) : (
                                        <CheckCircle2 className="size-4" />
                                    )}
                                    {section.label}
                                </span>
                            ))}
                        </div>
                    </div>
                    <p className="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:bg-amber-950/25 dark:text-amber-100">
                        {exam.score_disclaimer}
                    </p>
                    <div className="mt-4 flex flex-wrap gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <span className="inline-flex items-center gap-2 rounded-md bg-indigo-50 px-3 py-2 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200">
                            <Clock3 className="size-4" />
                            {formatSeconds(remainingSeconds)}
                        </span>
                        <span className="rounded-md bg-slate-50 px-3 py-2 dark:bg-slate-900">
                            {answered}/
                            {exam.current_section?.total_questions ??
                                exam.questions.length}{' '}
                            answered
                        </span>
                    </div>
                </section>

                {question && (
                    <section className="grid gap-6 xl:grid-cols-[0.28fr_1fr]">
                        <aside className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <div className="grid grid-cols-5 gap-2 xl:grid-cols-4">
                                {exam.questions.map((item, itemIndex) => (
                                    <button
                                        key={item.answer_id}
                                        type="button"
                                        onClick={() => setIndex(itemIndex)}
                                        className={`h-9 rounded-md text-sm font-semibold ${itemIndex === index ? 'bg-indigo-600 text-white' : item.selected_option_id ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300'}`}
                                    >
                                        {itemIndex + 1}
                                    </button>
                                ))}
                            </div>
                        </aside>

                        <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <span className="rounded-md bg-slate-100 px-3 py-1.5 text-sm font-semibold dark:bg-slate-900">
                                    Question {question.position}
                                </span>
                                {question.section_type === 'listening' && (
                                    <div className="flex flex-col items-end gap-2">
                                        <Button
                                            variant="outline"
                                            onClick={playAudio}
                                            disabled={
                                                !question.audio_url &&
                                                !question.audio_playback_text
                                            }
                                        >
                                            {question.audio_url ? (
                                                <Headphones className="size-4" />
                                            ) : (
                                                <Volume2 className="size-4" />
                                            )}
                                            {question.has_real_audio
                                                ? 'Play audio'
                                                : 'No uploaded audio'}
                                        </Button>
                                        <span className="text-xs text-slate-500">
                                            {question.audio_source_label}
                                        </span>
                                    </div>
                                )}
                            </div>

                            {question.audio_url && (
                                <audio
                                    className="mt-5 w-full"
                                    controls
                                    src={question.audio_url}
                                >
                                    <track kind="captions" />
                                </audio>
                            )}

                            {question.passage_text && (
                                <div className="mt-5 max-h-80 overflow-auto rounded-md bg-slate-50 p-4 text-sm leading-7 text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    {question.passage_text}
                                </div>
                            )}

                            <h2 className="mt-6 text-xl leading-8 font-semibold">
                                {question.question_text}
                            </h2>
                            <div className="mt-5 grid gap-3">
                                {question.options.map((option) => (
                                    <button
                                        key={option.id}
                                        type="button"
                                        onClick={() => submitAnswer(option.id)}
                                        disabled={
                                            question.selected_option_id !==
                                                null || remainingSeconds <= 0
                                        }
                                        className={`rounded-lg border p-4 text-left transition ${question.selected_option_id === option.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40' : 'border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900'}`}
                                    >
                                        <span className="font-semibold">
                                            {option.label}.
                                        </span>{' '}
                                        {option.text}
                                    </button>
                                ))}
                            </div>

                            <div className="mt-6 flex flex-wrap justify-between gap-3">
                                <Button
                                    variant="outline"
                                    disabled={index === 0}
                                    onClick={() => setIndex(index - 1)}
                                >
                                    Previous
                                </Button>
                                <div className="flex gap-3">
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            router.post(finishExam.url(exam.id))
                                        }
                                    >
                                        Finish Exam
                                    </Button>
                                    <Button
                                        onClick={() =>
                                            index < exam.questions.length - 1
                                                ? setIndex(index + 1)
                                                : router.post(
                                                      finishSection.url(
                                                          exam.id,
                                                      ),
                                                  )
                                        }
                                    >
                                        {index < exam.questions.length - 1
                                            ? 'Next'
                                            : 'Finish Section'}
                                        <Send className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </section>
                )}
            </div>
        </>
    );
}

function formatSeconds(seconds: number) {
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}
