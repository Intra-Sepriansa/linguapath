import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReadingPassageForm } from './form';

type Options = {
    difficulties: string[];
    statuses: string[];
};

export default function AdminReadingPassagesCreate({
    options,
}: {
    options: Options;
}) {
    return (
        <>
            <Head title="Create Reading Passage" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <Link
                        href="/admin/reading-passages"
                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                    >
                        <ArrowLeft className="size-4" />
                        Reading passages
                    </Link>
                    <p className="mt-5 text-sm font-semibold text-indigo-600">
                        Reading CMS
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        Create reading passage
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Drafts may be shorter while being written. Published
                        passages must meet the 300-700 word TOEFL-style target.
                    </p>
                </section>

                <ReadingPassageForm
                    mode="create"
                    action="/admin/reading-passages"
                    options={options}
                    cancelHref="/admin/reading-passages"
                />
            </div>
        </>
    );
}
