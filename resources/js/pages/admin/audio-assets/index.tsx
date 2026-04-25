import { Head, router, usePage } from '@inertiajs/react';
import {
    Archive,
    AudioLines,
    CheckCircle2,
    RotateCcw,
    ShieldCheck,
    Upload,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';

type AudioAsset = {
    id: number;
    title: string;
    status: string;
    source: string | null;
    is_real_audio: boolean;
    is_approved_for_exam: boolean;
    can_be_approved: boolean;
    approval_blockers: string[];
    quality_badges: string[];
    audio_url: string | null;
    duration_seconds: number;
    accent: string;
    speed: number;
    file_size: number | null;
    transcript_reviewed_at: string | null;
    approved_at: string | null;
    review_notes: string | null;
    questions_count: number;
    listening_questions_count: number;
    is_attached_to_listening_question: boolean;
    created_at: string;
};

type BulkResult = {
    processed: number;
    skipped: number;
    errors: string[];
};

type FilterOption = {
    value: string;
    label: string;
};

type AudioAssetsProps = {
    assets: AudioAsset[];
    filters: {
        filter: string;
        options: FilterOption[];
    };
};

const bulkActions = [
    ['approve_selected', 'Approve selected', ShieldCheck],
    ['mark_transcript_reviewed', 'Mark transcript reviewed', CheckCircle2],
    ['mark_real_audio', 'Mark real audio', AudioLines],
    ['needs_review', 'Needs review', RotateCcw],
    ['archive', 'Archive', Archive],
] as const;

export default function AdminAudioAssets({
    assets,
    filters,
}: AudioAssetsProps) {
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [bulkAction, setBulkAction] = useState('approve_selected');
    const { flash } = usePage().props as {
        flash?: {
            success?: string | null;
            bulkAudioReview?: BulkResult | null;
        };
    };
    const allVisibleSelected =
        assets.length > 0 &&
        assets.every((asset) => selectedIds.includes(asset.id));
    const selectedAssets = useMemo(
        () => assets.filter((asset) => selectedIds.includes(asset.id)),
        [assets, selectedIds],
    );

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = event.currentTarget;

        router.post('/admin/audio-assets', new FormData(form), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const review = (asset: AudioAsset, approved: boolean) => {
        router.patch(
            `/admin/audio-assets/${asset.id}/review`,
            {
                transcript_reviewed: approved,
                approved,
                status: approved ? 'ready' : 'draft',
                review_notes: approved
                    ? 'Transcript reviewed and audio approved for exam selection.'
                    : 'Marked for another review pass.',
            },
            { preserveScroll: true },
        );
    };

    const toggleAll = () => {
        setSelectedIds(
            allVisibleSelected ? [] : assets.map((asset) => asset.id),
        );
    };

    const toggleAsset = (assetId: number) => {
        setSelectedIds((current) =>
            current.includes(assetId)
                ? current.filter((id) => id !== assetId)
                : [...current, assetId],
        );
    };

    const submitBulkAction = () => {
        if (selectedIds.length === 0) {
            return;
        }

        const actionLabel =
            bulkActions.find(([value]) => value === bulkAction)?.[1] ??
            'Apply action';

        if (
            !window.confirm(
                `${actionLabel} for ${selectedIds.length} selected audio assets?`,
            )
        ) {
            return;
        }

        router.patch(
            '/admin/audio-assets/bulk-review',
            {
                asset_ids: selectedIds,
                action: bulkAction,
                review_notes: `${actionLabel} from admin bulk action.`,
            },
            {
                preserveScroll: true,
                onSuccess: () => setSelectedIds([]),
            },
        );
    };

    const applyFilter = (filter: string) => {
        router.get('/admin/audio-assets', filter ? { filter } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Admin Audio Assets" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <p className="text-sm font-semibold text-indigo-600">
                        Listening CMS
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        Audio assets
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Upload real listening audio, review transcripts, and
                        approve only assets that can safely enter exam
                        selection.
                    </p>
                </section>

                <section className="grid gap-6 lg:grid-cols-[0.75fr_1.25fr]">
                    <form
                        onSubmit={submit}
                        className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div className="flex items-center gap-2">
                            <Upload className="size-5 text-indigo-500" />
                            <h2 className="text-xl font-semibold">
                                Upload audio
                            </h2>
                        </div>
                        <div className="mt-5 grid gap-4">
                            <Field label="Title">
                                <input
                                    name="title"
                                    required
                                    className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                />
                            </Field>
                            <Field label="Audio file">
                                <input
                                    name="audio_file"
                                    type="file"
                                    accept=".mp3,.wav,.m4a,audio/mpeg,audio/wav,audio/mp4"
                                    required
                                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"
                                />
                            </Field>
                            <Field label="Transcript">
                                <textarea
                                    name="transcript"
                                    required
                                    className="min-h-32 w-full rounded-md border border-slate-200 bg-white p-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                />
                            </Field>
                            <div className="grid gap-4 md:grid-cols-3">
                                <Field label="Duration">
                                    <input
                                        name="duration_seconds"
                                        type="number"
                                        min={1}
                                        max={3600}
                                        className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                    />
                                </Field>
                                <Field label="Accent">
                                    <input
                                        name="accent"
                                        defaultValue="american"
                                        className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                    />
                                </Field>
                                <Field label="Exam plays">
                                    <input
                                        name="playback_limit_exam"
                                        type="number"
                                        min={1}
                                        max={3}
                                        defaultValue={1}
                                        className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                    />
                                </Field>
                            </div>
                            <input type="hidden" name="status" value="ready" />
                            <Button type="submit">
                                <Upload className="size-4" />
                                Save audio
                            </Button>
                        </div>
                    </form>

                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                            <div>
                                <div className="flex items-center gap-2">
                                    <AudioLines className="size-5 text-indigo-500" />
                                    <h2 className="text-xl font-semibold">
                                        Audio review queue
                                    </h2>
                                </div>
                                <p className="mt-1 text-sm text-slate-500">
                                    {selectedIds.length} selected,{' '}
                                    {
                                        selectedAssets.filter(
                                            (asset) => asset.can_be_approved,
                                        ).length
                                    }{' '}
                                    currently approvable
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <select
                                    value={filters.filter}
                                    onChange={(event) =>
                                        applyFilter(event.target.value)
                                    }
                                    className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                >
                                    {filters.options.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <select
                                    value={bulkAction}
                                    onChange={(event) =>
                                        setBulkAction(event.target.value)
                                    }
                                    className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                >
                                    {bulkActions.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                                <Button
                                    type="button"
                                    onClick={submitBulkAction}
                                    disabled={selectedIds.length === 0}
                                >
                                    Apply bulk action
                                </Button>
                            </div>
                        </div>

                        {flash?.success && (
                            <div className="mt-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/25 dark:text-emerald-100">
                                {flash.success}
                            </div>
                        )}
                        {flash?.bulkAudioReview && (
                            <div className="mt-4 rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-900">
                                <p className="font-semibold">
                                    {flash.bulkAudioReview.processed} processed,{' '}
                                    {flash.bulkAudioReview.skipped} skipped
                                </p>
                                {flash.bulkAudioReview.errors.length > 0 && (
                                    <ul className="mt-2 grid gap-1 text-xs text-amber-700 dark:text-amber-200">
                                        {flash.bulkAudioReview.errors
                                            .slice(0, 5)
                                            .map((error) => (
                                                <li key={error}>{error}</li>
                                            ))}
                                    </ul>
                                )}
                            </div>
                        )}

                        <div className="mt-5 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase dark:bg-slate-900">
                                    <tr>
                                        <th className="w-10 p-3">
                                            <input
                                                type="checkbox"
                                                checked={allVisibleSelected}
                                                onChange={toggleAll}
                                                aria-label="Select all visible audio assets"
                                            />
                                        </th>
                                        <th className="p-3">Asset</th>
                                        <th className="p-3">Readiness</th>
                                        <th className="p-3">Usage</th>
                                        <th className="p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {assets.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="p-6 text-center text-slate-500"
                                            >
                                                No audio assets match this
                                                filter.
                                            </td>
                                        </tr>
                                    )}
                                    {assets.map((asset) => (
                                        <tr key={asset.id}>
                                            <td className="p-3 align-top">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(
                                                        asset.id,
                                                    )}
                                                    onChange={() =>
                                                        toggleAsset(asset.id)
                                                    }
                                                    aria-label={`Select ${asset.title}`}
                                                />
                                            </td>
                                            <td className="p-3 align-top">
                                                <p className="font-semibold">
                                                    {asset.title}
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {asset.duration_seconds}s ·{' '}
                                                    {asset.accent} ·{' '}
                                                    {asset.source ?? 'unknown'}{' '}
                                                    · {asset.status}
                                                </p>
                                                {asset.audio_url && (
                                                    <audio
                                                        className="mt-3 w-full max-w-xs"
                                                        controls
                                                        src={asset.audio_url}
                                                    >
                                                        <track kind="captions" />
                                                    </audio>
                                                )}
                                            </td>
                                            <td className="p-3 align-top">
                                                <div className="flex max-w-sm flex-wrap gap-2">
                                                    {asset.quality_badges.map(
                                                        (badge) => (
                                                            <span
                                                                key={badge}
                                                                className={`rounded-md px-2.5 py-1 text-xs font-semibold ${badgeClass(badge)}`}
                                                            >
                                                                {badge}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                                {asset.approval_blockers
                                                    .length > 0 && (
                                                    <ul className="mt-2 grid gap-1 text-xs text-amber-700 dark:text-amber-200">
                                                        {asset.approval_blockers.map(
                                                            (blocker) => (
                                                                <li
                                                                    key={
                                                                        blocker
                                                                    }
                                                                >
                                                                    {blocker}
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </td>
                                            <td className="p-3 align-top">
                                                <p className="font-semibold">
                                                    {asset.questions_count}{' '}
                                                    questions
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {
                                                        asset.listening_questions_count
                                                    }{' '}
                                                    listening linked
                                                </p>
                                            </td>
                                            <td className="p-3 align-top">
                                                <div className="flex flex-wrap gap-2">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() =>
                                                            review(asset, true)
                                                        }
                                                        disabled={
                                                            !asset.can_be_approved
                                                        }
                                                    >
                                                        <CheckCircle2 className="size-4" />
                                                        Approve
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            review(asset, false)
                                                        }
                                                    >
                                                        <RotateCcw className="size-4" />
                                                        Needs review
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </section>
            </div>
        </>
    );
}

function badgeClass(badge: string): string {
    if (badge === 'Approved' || badge === 'Real Audio') {
        return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/25 dark:text-emerald-100';
    }

    if (badge === 'Transcript Reviewed') {
        return 'bg-blue-50 text-blue-700 dark:bg-blue-950/25 dark:text-blue-100';
    }

    return 'bg-amber-50 text-amber-700 dark:bg-amber-950/25 dark:text-amber-100';
}

function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            {children}
        </label>
    );
}
