import { Link, useForm } from '@inertiajs/react';
import { AlertTriangle, Save } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export type ReadingPassageFormData = {
    title: string;
    topic: string;
    passage_text: string;
    difficulty: string;
    status: string;
};

type FormOptions = {
    difficulties: string[];
    statuses: string[];
};

type ReadingPassageFormProps = {
    mode: 'create' | 'edit';
    action: string;
    options: FormOptions;
    initialValues?: Partial<ReadingPassageFormData>;
    cancelHref: string;
};

const defaultValues: ReadingPassageFormData = {
    title: '',
    topic: '',
    passage_text: '',
    difficulty: 'intermediate',
    status: 'draft',
};

export function ReadingPassageForm({
    mode,
    action,
    options,
    initialValues,
    cancelHref,
}: ReadingPassageFormProps) {
    const form = useForm<ReadingPassageFormData>({
        ...defaultValues,
        ...initialValues,
    });
    const wordCount = countWords(form.data.passage_text);
    const warnings = qualityWarnings(wordCount);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (mode === 'create') {
            form.post(action, { preserveScroll: true });

            return;
        }

        form.put(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid gap-6">
            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div className="grid gap-5">
                    <Field label="Title" error={form.errors.title}>
                        <input
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            required
                            className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                        />
                    </Field>

                    <Field label="Topic" error={form.errors.topic}>
                        <input
                            value={form.data.topic}
                            onChange={(event) =>
                                form.setData('topic', event.target.value)
                            }
                            className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                        />
                    </Field>

                    <div className="grid gap-4 md:grid-cols-3">
                        <Field
                            label="Difficulty"
                            error={form.errors.difficulty}
                        >
                            <select
                                value={form.data.difficulty}
                                onChange={(event) =>
                                    form.setData(
                                        'difficulty',
                                        event.target.value,
                                    )
                                }
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
                            >
                                {options.difficulties.map((difficulty) => (
                                    <option key={difficulty} value={difficulty}>
                                        {labelize(difficulty)}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Status" error={form.errors.status}>
                            <select
                                value={form.data.status}
                                onChange={(event) =>
                                    form.setData('status', event.target.value)
                                }
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm capitalize dark:border-slate-800 dark:bg-slate-950"
                            >
                                {options.statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {labelize(status)}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <div className="rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-900">
                            <p className="font-semibold">{wordCount} words</p>
                            <p className="mt-1 text-slate-500">
                                Target: 300-700 words
                            </p>
                        </div>
                    </div>

                    <Field
                        label="Passage text"
                        error={form.errors.passage_text}
                    >
                        <textarea
                            value={form.data.passage_text}
                            onChange={(event) =>
                                form.setData('passage_text', event.target.value)
                            }
                            required
                            className="min-h-96 w-full rounded-md border border-slate-200 bg-white p-4 text-sm leading-7 dark:border-slate-800 dark:bg-slate-950"
                        />
                    </Field>

                    {warnings.length > 0 && (
                        <div className="grid gap-2 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100">
                            {warnings.map((warning) => (
                                <p
                                    key={warning}
                                    className="flex items-start gap-2"
                                >
                                    <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                    {warning}
                                </p>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            <div className="flex flex-wrap justify-end gap-3">
                <Link
                    href={cancelHref}
                    className="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                >
                    Cancel
                </Link>
                <Button type="submit" disabled={form.processing}>
                    <Save className="size-4" />
                    {mode === 'create' ? 'Create passage' : 'Save changes'}
                </Button>
            </div>
        </form>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            {children}
            {error && <span className="text-sm text-red-600">{error}</span>}
        </label>
    );
}

export function countWords(text: string): number {
    return (
        text.trim().match(/[\p{L}\p{N}]+(?:[-'][\p{L}\p{N}]+)*/gu)?.length ?? 0
    );
}

export function qualityWarnings(wordCount: number): string[] {
    if (wordCount < 300) {
        return [
            'This passage is shorter than the TOEFL-style target of 300 words.',
        ];
    }

    if (wordCount > 700) {
        return [
            'This passage is longer than the TOEFL-style target of 700 words.',
        ];
    }

    return [];
}

export function labelize(value: string): string {
    return value.replaceAll('_', ' ');
}
