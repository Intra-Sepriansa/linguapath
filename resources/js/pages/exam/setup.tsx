import { Head, Link, router } from '@inertiajs/react';
import {
    BarChart3,
    Clock3,
    Headphones,
    LibraryBig,
    Play,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { setup as examSetup, start as startExam } from '@/routes/exam';

type ExamSection = {
    section_type: string;
    label: string;
    count: number;
    duration_seconds: number;
    position: number;
};

type HistoryItem = {
    id: number;
    score: number | null;
    correct_answers: number;
    total_questions: number;
    finished_at: string | null;
};

const icons = [Headphones, Sparkles, LibraryBig];

export default function ExamSetup({
    sections,
    history,
    scoreDisclaimer,
}: {
    sections: ExamSection[];
    history: HistoryItem[];
    scoreDisclaimer: string;
}) {
    return (
        <>
            <Head title="TOEFL ITP Simulation" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-indigo-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200">
                                <ShieldCheck className="size-4" />
                                Estimated TOEFL ITP Level 1
                            </div>
                            <h1 className="mt-5 text-4xl font-semibold tracking-normal text-slate-950 dark:text-white">
                                Full exam simulation
                            </h1>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                140 questions, 115 minutes, strict section
                                order, and an estimated scaled score after
                                completion.
                            </p>
                            <p className="mt-3 max-w-2xl rounded-md bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:bg-amber-950/25 dark:text-amber-100">
                                {scoreDisclaimer}
                            </p>
                        </div>
                        <Button
                            className="h-11 px-5"
                            onClick={() => router.post(startExam.url())}
                        >
                            <Play className="size-4" />
                            Start Simulation
                        </Button>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    {sections.map((section, index) => {
                        const Icon = icons[index] ?? Sparkles;

                        return (
                            <article
                                key={section.section_type}
                                className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex size-10 items-center justify-center rounded-md bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200">
                                        <Icon className="size-5" />
                                    </div>
                                    <span className="text-sm font-semibold text-slate-500">
                                        Section {section.position}
                                    </span>
                                </div>
                                <h2 className="mt-5 text-lg font-semibold text-slate-950 dark:text-white">
                                    {section.label}
                                </h2>
                                <div className="mt-4 grid grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300">
                                    <div className="rounded-md bg-slate-50 p-3 dark:bg-slate-900">
                                        <p className="font-semibold">
                                            {section.count}
                                        </p>
                                        <p>questions</p>
                                    </div>
                                    <div className="rounded-md bg-slate-50 p-3 dark:bg-slate-900">
                                        <p className="font-semibold">
                                            {Math.round(
                                                section.duration_seconds / 60,
                                            )}{' '}
                                            min
                                        </p>
                                        <p>timer</p>
                                    </div>
                                </div>
                            </article>
                        );
                    })}
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h2 className="text-xl font-semibold">
                                Simulation history
                            </h2>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                Track estimated score movement across attempts.
                            </p>
                        </div>
                        <BarChart3 className="size-5 text-indigo-500" />
                    </div>
                    <div className="mt-5 grid gap-3">
                        {history.length === 0 && (
                            <div className="rounded-md bg-slate-50 p-5 text-sm text-slate-500 dark:bg-slate-900">
                                No completed simulation yet.
                            </div>
                        )}
                        {history.map((item) => (
                            <Link
                                key={item.id}
                                href={examSetup.url()}
                                className="flex items-center justify-between rounded-md border border-slate-100 p-4 text-sm hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                            >
                                <span className="flex items-center gap-2">
                                    <Clock3 className="size-4 text-slate-400" />
                                    {item.finished_at ?? 'Completed'}
                                </span>
                                <span className="font-semibold">
                                    {item.score ?? 'N/A'} estimated
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
