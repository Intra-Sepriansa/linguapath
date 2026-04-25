import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReadingPassageForm } from './form';

type ReadingPassage = {
    id: number;
    title: string;
    topic: string | null;
    passage_text: string;
    difficulty: string;
    status: string;
};

type Options = {
    difficulties: string[];
    statuses: string[];
};

export default function AdminReadingPassagesEdit({
    passage,
    options,
}: {
    passage: ReadingPassage;
    options: Options;
}) {
    return (
        <>
            <Head title={`Edit ${passage.title}`} />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <Link
                        href={`/admin/reading-passages/${passage.id}`}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                    >
                        <ArrowLeft className="size-4" />
                        Passage preview
                    </Link>
                    <p className="mt-5 text-sm font-semibold text-indigo-600">
                        Reading CMS
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        Edit reading passage
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Changes update the passage bank used by reading practice
                        and exam selection. Linked questions are not edited
                        here.
                    </p>
                </section>

                <ReadingPassageForm
                    mode="edit"
                    action={`/admin/reading-passages/${passage.id}`}
                    options={options}
                    initialValues={{
                        title: passage.title,
                        topic: passage.topic ?? '',
                        passage_text: passage.passage_text,
                        difficulty: passage.difficulty,
                        status: passage.status,
                    }}
                    cancelHref={`/admin/reading-passages/${passage.id}`}
                />
            </div>
        </>
    );
}
