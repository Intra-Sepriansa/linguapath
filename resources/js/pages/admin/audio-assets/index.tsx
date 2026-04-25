import { Head, router } from '@inertiajs/react';
import { AudioLines, Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';

type AudioAsset = {
    id: number;
    title: string;
    status: string;
    is_real_audio: boolean;
    audio_url: string | null;
    duration_seconds: number;
    accent: string;
    speed: number;
    file_size: number | null;
    created_at: string;
};

export default function AdminAudioAssets({ assets }: { assets: AudioAsset[] }) {
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = event.currentTarget;

        router.post('/admin/audio-assets', new FormData(form), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
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
                        Upload real listening audio. Transcript is stored for
                        review mode, but exam payloads must not expose it before
                        completion.
                    </p>
                </section>

                <section className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
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
                        <div className="flex items-center gap-2">
                            <AudioLines className="size-5 text-indigo-500" />
                            <h2 className="text-xl font-semibold">
                                Recent assets
                            </h2>
                        </div>
                        <div className="mt-5 grid gap-3">
                            {assets.length === 0 && (
                                <div className="rounded-md bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-900">
                                    No uploaded audio yet.
                                </div>
                            )}
                            {assets.map((asset) => (
                                <article
                                    key={asset.id}
                                    className="rounded-md border border-slate-100 p-4 text-sm dark:border-slate-800"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="font-semibold">
                                                {asset.title}
                                            </p>
                                            <p className="mt-1 text-slate-500">
                                                {asset.accent} ·{' '}
                                                {asset.duration_seconds}s ·{' '}
                                                {asset.status}
                                            </p>
                                        </div>
                                        <span className="rounded-md bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/25 dark:text-emerald-100">
                                            {asset.is_real_audio
                                                ? 'Real audio'
                                                : 'Transcript fallback'}
                                        </span>
                                    </div>
                                    {asset.audio_url && (
                                        <audio
                                            className="mt-3 w-full"
                                            controls
                                            src={asset.audio_url}
                                        >
                                            <track kind="captions" />
                                        </audio>
                                    )}
                                </article>
                            ))}
                        </div>
                    </section>
                </section>
            </div>
        </>
    );
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
