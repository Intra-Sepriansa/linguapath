import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownAZ,
    BookOpen,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { labelize } from './form';

type ReadingPassage = {
    id: number;
    title: string;
    topic: string | null;
    difficulty: string;
    status: string;
    word_count: number;
    source: string | null;
    questions_count: number;
    created_at: string;
    updated_at: string;
    quality_warnings: string[];
};

type PaginatedPassages = {
    data: ReadingPassage[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Filters = {
    search: string;
    status: string;
    difficulty: string;
    sort: string;
};

type Options = {
    difficulties: string[];
    statuses: string[];
};

type Stats = {
    total: number;
    published: number;
    short: number;
    long: number;
};

export default function AdminReadingPassagesIndex({
    passages,
    filters,
    options,
    stats,
}: {
    passages: PaginatedPassages;
    filters: Filters;
    options: Options;
    stats: Stats;
}) {
    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        router.get(
            '/admin/reading-passages',
            {
                search: formData.get('search')?.toString() ?? '',
                status: formData.get('status')?.toString() ?? '',
                difficulty: formData.get('difficulty')?.toString() ?? '',
                sort: formData.get('sort')?.toString() ?? 'newest',
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    const deletePassage = (passage: ReadingPassage) => {
        if (passage.questions_count > 0) {
            return;
        }

        if (!window.confirm(`Delete "${passage.title}"?`)) {
            return;
        }

        router.delete(`/admin/reading-passages/${passage.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Admin Reading Passages" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-indigo-600">
                                Reading CMS
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold">
                                Reading passages
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Manage TOEFL-style passages independently from
                                seeders. Published passages are held to the
                                300-700 word target.
                            </p>
                        </div>
                        <Link
                            href="/admin/reading-passages/create"
                            className="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            <Plus className="mr-2 size-4" />
                            New passage
                        </Link>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-4">
                    <StatCard label="Total" value={stats.total} />
                    <StatCard label="Published" value={stats.published} />
                    <StatCard label="Under 300 words" value={stats.short} />
                    <StatCard label="Over 700 words" value={stats.long} />
                </section>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1.3fr_0.8fr_0.8fr_0.8fr_auto] dark:border-slate-800 dark:bg-slate-950"
                >
                    <label className="grid gap-2 text-sm font-medium">
                        Search
                        <input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Title, topic, or passage text"
                            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                        />
                    </label>
                    <Select
                        label="Status"
                        name="status"
                        value={filters.status}
                        options={options.statuses}
                    />
                    <Select
                        label="Difficulty"
                        name="difficulty"
                        value={filters.difficulty}
                        options={options.difficulties}
                    />
                    <Select
                        label="Sort"
                        name="sort"
                        value={filters.sort}
                        options={[
                            'newest',
                            'oldest',
                            'title',
                            'word_count_asc',
                            'word_count_desc',
                        ]}
                    />
                    <Button type="submit" className="self-end">
                        <Search className="size-4" />
                        Apply
                    </Button>
                </form>

                <section className="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="hidden grid-cols-[1.4fr_0.65fr_0.55fr_0.55fr_0.45fr] gap-4 border-b border-slate-200 px-5 py-3 text-xs font-semibold tracking-wide text-slate-500 uppercase lg:grid dark:border-slate-800">
                        <span>Passage</span>
                        <span>Status</span>
                        <span>Words</span>
                        <span>Questions</span>
                        <span className="text-right">Actions</span>
                    </div>

                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {passages.data.length === 0 && (
                            <div className="p-6 text-sm text-slate-500">
                                No reading passages match the current filters.
                            </div>
                        )}

                        {passages.data.map((passage) => (
                            <article
                                key={passage.id}
                                className="grid gap-4 px-5 py-4 lg:grid-cols-[1.4fr_0.65fr_0.55fr_0.55fr_0.45fr] lg:items-center"
                            >
                                <div>
                                    <div className="flex items-center gap-2">
                                        <BookOpen className="size-4 text-indigo-500" />
                                        <h2 className="font-semibold">
                                            {passage.title}
                                        </h2>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {passage.topic ?? 'No topic'} ·{' '}
                                        {labelize(passage.difficulty)} ·{' '}
                                        {passage.source ?? 'manual'}
                                    </p>
                                    {passage.quality_warnings.length > 0 && (
                                        <div className="mt-2 grid gap-1 text-sm text-amber-700 dark:text-amber-200">
                                            {passage.quality_warnings.map(
                                                (warning) => (
                                                    <p
                                                        key={warning}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <AlertTriangle className="size-4" />
                                                        {warning}
                                                    </p>
                                                ),
                                            )}
                                        </div>
                                    )}
                                </div>
                                <span className="w-fit rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold capitalize dark:bg-slate-900">
                                    {labelize(passage.status)}
                                </span>
                                <span className="flex items-center gap-2 text-sm">
                                    <ArrowDownAZ className="size-4 text-slate-400" />
                                    {passage.word_count}
                                </span>
                                <span className="text-sm">
                                    {passage.questions_count}
                                </span>
                                <div className="flex justify-start gap-2 lg:justify-end">
                                    <IconLink
                                        href={`/admin/reading-passages/${passage.id}`}
                                        label="Preview"
                                    >
                                        <Eye className="size-4" />
                                    </IconLink>
                                    <IconLink
                                        href={`/admin/reading-passages/${passage.id}/edit`}
                                        label="Edit"
                                    >
                                        <Pencil className="size-4" />
                                    </IconLink>
                                    <button
                                        type="button"
                                        onClick={() => deletePassage(passage)}
                                        disabled={passage.questions_count > 0}
                                        className="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-red-950/25"
                                        title={
                                            passage.questions_count > 0
                                                ? 'Cannot delete a passage with questions'
                                                : 'Delete'
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>

                <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                    <span>
                        Showing {passages.from ?? 0}-{passages.to ?? 0} of{' '}
                        {passages.total}
                    </span>
                    <div className="flex gap-2">
                        <PaginationLink
                            href={passages.prev_page_url}
                            label="Previous"
                        />
                        <PaginationLink
                            href={passages.next_page_url}
                            label="Next"
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-2xl font-semibold">{value}</p>
        </article>
    );
}

function Select({
    label,
    name,
    value,
    options,
}: {
    label: string;
    name: string;
    value: string;
    options: string[];
}) {
    return (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            <select
                name={name}
                defaultValue={value}
                className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
            >
                <option value="">All</option>
                {options.map((option) => (
                    <option key={option} value={option}>
                        {labelize(option)}
                    </option>
                ))}
            </select>
        </label>
    );
}

function IconLink({
    href,
    label,
    children,
}: {
    href: string;
    label: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            title={label}
            className="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
        >
            {children}
        </Link>
    );
}

function PaginationLink({
    href,
    label,
}: {
    href: string | null;
    label: string;
}) {
    if (!href) {
        return (
            <span className="inline-flex h-9 items-center rounded-md border border-slate-200 px-3 opacity-40 dark:border-slate-800">
                {label}
            </span>
        );
    }

    return (
        <Link
            href={href}
            className="inline-flex h-9 items-center rounded-md border border-slate-200 px-3 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
        >
            {label}
        </Link>
    );
}
