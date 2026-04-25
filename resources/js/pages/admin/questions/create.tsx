import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { QuestionForm } from './form';
import type { QuestionFormOptions } from './form';

export default function AdminQuestionsCreate({
    options,
}: {
    options: QuestionFormOptions;
}) {
    return (
        <>
            <Head title="Create Question" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <Link
                        href="/admin/questions"
                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                    >
                        <ArrowLeft className="size-4" />
                        Questions
                    </Link>
                    <p className="mt-5 text-sm font-semibold text-indigo-600">
                        Question CMS
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        Create question
                    </h1>
                </section>

                <QuestionForm
                    mode="create"
                    action="/admin/questions"
                    options={options}
                    cancelHref="/admin/questions"
                />
            </div>
        </>
    );
}
