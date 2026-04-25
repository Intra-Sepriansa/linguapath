import { Head, Link } from '@inertiajs/react';
import { AlertCircle, BarChart3, CheckCircle2, Target } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { setup as examSetup } from '@/routes/exam';
import { index as mistakesIndex } from '@/routes/mistakes';

type Result = {
    estimated_total_score: number | null;
    score_disclaimer: string;
    correct_answers: number;
    total_questions: number;
    sections: Array<{
        id: number;
        label: string;
        raw_score: string;
        accuracy: number;
        estimated_scaled_score: number | null;
    }>;
    weaknesses: Array<{ label: string; count: number; priority: string }>;
    recommendations: Array<{
        title: string;
        description: string;
        priority: string;
    }>;
    history: Array<{
        id: number;
        score: number | null;
        finished_at: string | null;
    }>;
    answers: Array<{
        answer_id: number;
        position: number;
        section_type: string;
        question_type: string;
        question_text: string;
        transcript: string | null;
        evidence_sentence: string | null;
        selected_option_text: string | null;
        correct_option_text: string | null;
        explanation: string | null;
        is_correct: boolean | null;
    }>;
};

export default function ExamResult({ result }: { result: Result }) {
    return (
        <>
            <Head title="Exam Result" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-indigo-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-indigo-600">
                                Estimated TOEFL ITP
                            </p>
                            <h1 className="mt-2 text-5xl font-semibold">
                                {result.estimated_total_score ?? 'N/A'}
                            </h1>
                            <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                {result.correct_answers}/
                                {result.total_questions} correct.{' '}
                                {result.score_disclaimer}
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <Button asChild variant="outline">
                                <Link href={mistakesIndex()}>
                                    Review mistakes
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href={examSetup()}>New simulation</Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    {result.sections.map((section) => (
                        <article
                            key={section.id}
                            className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                        >
                            <div className="flex items-center justify-between">
                                <h2 className="font-semibold">
                                    {section.label}
                                </h2>
                                <CheckCircle2 className="size-5 text-emerald-500" />
                            </div>
                            <p className="mt-4 text-3xl font-semibold">
                                {section.estimated_scaled_score ?? 'N/A'}
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                {section.raw_score} raw · {section.accuracy}%
                                accuracy
                            </p>
                        </article>
                    ))}
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex items-center gap-2">
                            <AlertCircle className="size-5 text-rose-500" />
                            <h2 className="text-xl font-semibold">
                                Top weaknesses
                            </h2>
                        </div>
                        <div className="mt-5 grid gap-3">
                            {result.weaknesses.map((weakness) => (
                                <div
                                    key={weakness.label}
                                    className="rounded-md bg-rose-50 p-4 text-sm dark:bg-rose-950/20"
                                >
                                    <p className="font-semibold">
                                        {weakness.label}
                                    </p>
                                    <p className="mt-1 text-slate-600 dark:text-slate-300">
                                        {weakness.count} misses ·{' '}
                                        {weakness.priority} priority
                                    </p>
                                </div>
                            ))}
                        </div>
                    </article>

                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex items-center gap-2">
                            <Target className="size-5 text-indigo-500" />
                            <h2 className="text-xl font-semibold">
                                Next actions
                            </h2>
                        </div>
                        <div className="mt-5 grid gap-3">
                            {result.recommendations.map((item) => (
                                <div
                                    key={item.title}
                                    className="rounded-md bg-slate-50 p-4 text-sm dark:bg-slate-900"
                                >
                                    <p className="font-semibold">
                                        {item.title}
                                    </p>
                                    <p className="mt-1 text-slate-600 dark:text-slate-300">
                                        {item.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </article>
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-center gap-2">
                        <BarChart3 className="size-5 text-indigo-500" />
                        <h2 className="text-xl font-semibold">Score history</h2>
                    </div>
                    <div className="mt-5 flex flex-wrap gap-3">
                        {result.history.map((item) => (
                            <div
                                key={item.id}
                                className="rounded-md bg-slate-50 px-4 py-3 text-sm dark:bg-slate-900"
                            >
                                <p className="font-semibold">
                                    {item.score ?? 'N/A'}
                                </p>
                                <p className="text-slate-500">
                                    {item.finished_at}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 className="text-xl font-semibold">Answer review</h2>
                    <div className="mt-5 grid max-h-[42rem] gap-3 overflow-auto pr-2">
                        {result.answers.map((answer) => (
                            <article
                                key={answer.answer_id}
                                className="rounded-md border border-slate-100 p-4 text-sm dark:border-slate-800"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <p className="font-semibold">
                                        {answer.position}.{' '}
                                        {answer.question_text}
                                    </p>
                                    <span
                                        className={
                                            answer.is_correct
                                                ? 'text-emerald-600'
                                                : 'text-rose-600'
                                        }
                                    >
                                        {answer.is_correct
                                            ? 'Correct'
                                            : 'Review'}
                                    </span>
                                </div>
                                {answer.transcript && (
                                    <p className="mt-3 rounded-md bg-cyan-50 p-3 text-cyan-900 dark:bg-cyan-950/25 dark:text-cyan-100">
                                        Transcript: {answer.transcript}
                                    </p>
                                )}
                                {answer.evidence_sentence && (
                                    <p className="mt-3 rounded-md bg-indigo-50 p-3 text-indigo-900 dark:bg-indigo-950/25 dark:text-indigo-100">
                                        Evidence: {answer.evidence_sentence}
                                    </p>
                                )}
                                <p className="mt-3 text-slate-600 dark:text-slate-300">
                                    Your answer:{' '}
                                    {answer.selected_option_text ?? 'Blank'} ·
                                    Correct:{' '}
                                    {answer.correct_option_text ?? 'N/A'}
                                </p>
                                {answer.explanation && (
                                    <p className="mt-2 text-slate-600 dark:text-slate-300">
                                        {answer.explanation}
                                    </p>
                                )}
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
