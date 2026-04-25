import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    BookOpen,
    FileQuestion,
    Pencil,
    Trash2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { labelize } from './form';

type ReadingPassage = {
    id: number;
    title: string;
    topic: string | null;
    passage_text: string;
    difficulty: string;
    status: string;
    word_count: number;
    source: string | null;
    questions_count: number;
    created_at: string;
    updated_at: string;
    quality_warnings: string[];
};

type PassageQuestion = {
    id: number;
    question_text: string;
    question_type: string;
    difficulty: string;
    exam_eligible: boolean;
};

export default function AdminReadingPassagesShow({
    passage,
    questions,
}: {
    passage: ReadingPassage;
    questions: PassageQuestion[];
}) {
    const deletePassage = () => {
        if (passage.questions_count > 0) {
            return;
        }

        if (!window.confirm(`Delete "${passage.title}"?`)) {
            return;
        }

        router.delete(`/admin/reading-passages/${passage.id}`);
    };

    return (
        <>
            <Head title={passage.title} />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <Link
                                href="/admin/reading-passages"
                                className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                            >
                                <ArrowLeft className="size-4" />
                                Reading passages
                            </Link>
                            <p className="mt-5 text-sm font-semibold text-indigo-600">
                                Passage preview
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold">
                                {passage.title}
                            </h1>
                            <p className="mt-2 text-sm text-slate-500">
                                {passage.topic ?? 'No topic'} ·{' '}
                                {labelize(passage.difficulty)} ·{' '}
                                {labelize(passage.status)} ·{' '}
                                {passage.word_count} words
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={`/admin/reading-passages/${passage.id}/edit`}
                                className="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Link>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={deletePassage}
                                disabled={passage.questions_count > 0}
                                title={
                                    passage.questions_count > 0
                                        ? 'Cannot delete a passage with questions'
                                        : 'Delete passage'
                                }
                            >
                                <Trash2 className="size-4" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </section>

                {passage.quality_warnings.length > 0 && (
                    <section className="grid gap-2 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100">
                        {passage.quality_warnings.map((warning) => (
                            <p key={warning} className="flex items-start gap-2">
                                <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                {warning}
                            </p>
                        ))}
                    </section>
                )}

                <section className="grid gap-6 lg:grid-cols-[1fr_0.42fr]">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex items-center gap-2">
                            <BookOpen className="size-5 text-indigo-500" />
                            <h2 className="text-xl font-semibold">
                                Passage text
                            </h2>
                        </div>
                        <div className="mt-5 rounded-md bg-slate-50 p-5 text-sm leading-7 whitespace-pre-wrap text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            {passage.passage_text}
                        </div>
                    </article>

                    <aside className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex items-center gap-2">
                            <FileQuestion className="size-5 text-indigo-500" />
                            <h2 className="text-xl font-semibold">
                                Linked questions
                            </h2>
                        </div>
                        <p className="mt-2 text-sm text-slate-500">
                            {passage.questions_count} questions use this
                            passage.
                        </p>
                        <div className="mt-5 grid gap-3">
                            {questions.length === 0 && (
                                <div className="rounded-md bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-900">
                                    No questions are linked yet.
                                </div>
                            )}
                            {questions.map((question) => (
                                <article
                                    key={question.id}
                                    className="rounded-md border border-slate-100 p-3 text-sm dark:border-slate-800"
                                >
                                    <p className="font-semibold">
                                        {question.question_text}
                                    </p>
                                    <p className="mt-2 text-xs text-slate-500">
                                        {labelize(question.question_type)} ·{' '}
                                        {labelize(question.difficulty)} ·{' '}
                                        {question.exam_eligible
                                            ? 'exam eligible'
                                            : 'practice only'}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </aside>
                </section>
            </div>
        </>
    );
}
