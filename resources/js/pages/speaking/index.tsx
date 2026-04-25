import { Head, router } from '@inertiajs/react';
import { Mic, PauseCircle, PlayCircle, Send, Sparkles } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { store as storeSpeakingAttempt } from '@/routes/speaking/attempts';

type Prompt = {
    id: number;
    title: string;
    prompt_type: string;
    skill_level: string;
    prompt: string;
    sample_answer: string | null;
    focus_points: string[];
    preparation_seconds: number;
    response_seconds: number;
};

type SpeakingPayload = {
    prompts: Prompt[];
    recent_attempts: Array<{
        id: number;
        prompt_title: string;
        duration_seconds: number;
        word_count: number;
        confidence_score: number;
        fluency_score: number;
        attempted_at: string | null;
    }>;
    summary: {
        attempts: number;
        minutes: number;
        average_fluency: number;
        average_confidence: number;
    };
};

export default function SpeakingIndex({
    speaking,
}: {
    speaking: SpeakingPayload;
}) {
    const [promptId, setPromptId] = useState(speaking.prompts[0]?.id ?? 0);
    const [transcript, setTranscript] = useState('');
    const [selfRating, setSelfRating] = useState(3);
    const [recording, setRecording] = useState(false);
    const [duration, setDuration] = useState(60);
    const [audioUrl, setAudioUrl] = useState<string | null>(null);
    const recorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);
    const activePrompt = useMemo(
        () =>
            speaking.prompts.find((prompt) => prompt.id === promptId) ??
            speaking.prompts[0],
        [promptId, speaking.prompts],
    );

    const startRecording = async () => {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });
        const recorder = new MediaRecorder(stream);
        chunksRef.current = [];
        recorder.ondataavailable = (event) =>
            chunksRef.current.push(event.data);
        recorder.onstop = () => {
            const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
            setAudioUrl(URL.createObjectURL(blob));
            stream.getTracks().forEach((track) => track.stop());
        };
        recorder.start();
        recorderRef.current = recorder;
        setRecording(true);
        setDuration(0);
    };

    const stopRecording = () => {
        recorderRef.current?.stop();
        setRecording(false);
    };

    const submit = () => {
        if (!activePrompt) {
            return;
        }

        router.post(
            storeSpeakingAttempt.url(),
            {
                speaking_prompt_id: activePrompt.id,
                transcript,
                duration_seconds: Math.max(duration, 1),
                self_rating: selfRating,
            },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Speaking Practice" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-indigo-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200">
                                <Mic className="size-4" />
                                Speaking Room
                            </div>
                            <h1 className="mt-5 text-4xl font-semibold">
                                Speak, record, review
                            </h1>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Practice read-aloud, roleplay, and opinion
                                responses with lightweight fluency and
                                confidence tracking.
                            </p>
                        </div>
                        <div className="grid grid-cols-3 gap-3 text-center text-sm">
                            <Metric
                                label="Attempts"
                                value={speaking.summary.attempts}
                            />
                            <Metric
                                label="Fluency"
                                value={`${speaking.summary.average_fluency}%`}
                            />
                            <Metric
                                label="Confidence"
                                value={`${speaking.summary.average_confidence}%`}
                            />
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                    <aside className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <h2 className="font-semibold">Prompt library</h2>
                        <div className="mt-4 grid gap-3">
                            {speaking.prompts.map((prompt) => (
                                <button
                                    key={prompt.id}
                                    type="button"
                                    onClick={() => setPromptId(prompt.id)}
                                    className={`rounded-md border p-4 text-left text-sm ${prompt.id === activePrompt?.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30' : 'border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900'}`}
                                >
                                    <p className="font-semibold">
                                        {prompt.title}
                                    </p>
                                    <p className="mt-1 text-slate-500">
                                        {prompt.prompt_type} ·{' '}
                                        {prompt.skill_level}
                                    </p>
                                </button>
                            ))}
                        </div>
                    </aside>

                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        {activePrompt && (
                            <>
                                <p className="text-sm font-semibold text-indigo-600">
                                    {activePrompt.prompt_type}
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold">
                                    {activePrompt.title}
                                </h2>
                                <p className="mt-4 rounded-md bg-slate-50 p-4 text-sm leading-6 dark:bg-slate-900">
                                    {activePrompt.prompt}
                                </p>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {activePrompt.focus_points.map((point) => (
                                        <span
                                            key={point}
                                            className="rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200"
                                        >
                                            {point}
                                        </span>
                                    ))}
                                </div>

                                <div className="mt-6 flex flex-wrap items-center gap-3">
                                    <Button
                                        type="button"
                                        variant={
                                            recording
                                                ? 'destructive'
                                                : 'default'
                                        }
                                        onClick={
                                            recording
                                                ? stopRecording
                                                : startRecording
                                        }
                                    >
                                        {recording ? (
                                            <PauseCircle className="size-4" />
                                        ) : (
                                            <PlayCircle className="size-4" />
                                        )}
                                        {recording
                                            ? 'Stop Recording'
                                            : 'Start Recording'}
                                    </Button>
                                    <input
                                        type="number"
                                        min={1}
                                        max={600}
                                        value={duration}
                                        onChange={(event) =>
                                            setDuration(
                                                Number(event.target.value),
                                            )
                                        }
                                        className="h-10 w-28 rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950"
                                    />
                                    <span className="text-sm text-slate-500">
                                        seconds
                                    </span>
                                </div>

                                {audioUrl && (
                                    <audio
                                        className="mt-5 w-full"
                                        controls
                                        src={audioUrl}
                                    >
                                        <track kind="captions" />
                                    </audio>
                                )}

                                <textarea
                                    value={transcript}
                                    onChange={(event) =>
                                        setTranscript(event.target.value)
                                    }
                                    placeholder="Optional transcript or self-note after speaking..."
                                    className="mt-5 min-h-36 w-full rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-950"
                                />
                                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                                    <label className="text-sm">
                                        Self rating
                                        <input
                                            type="range"
                                            min={1}
                                            max={5}
                                            value={selfRating}
                                            onChange={(event) =>
                                                setSelfRating(
                                                    Number(event.target.value),
                                                )
                                            }
                                            className="ml-3 align-middle"
                                        />
                                        <span className="ml-2 font-semibold">
                                            {selfRating}/5
                                        </span>
                                    </label>
                                    <Button type="button" onClick={submit}>
                                        <Send className="size-4" />
                                        Save Attempt
                                    </Button>
                                </div>
                            </>
                        )}
                    </article>
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-center gap-2">
                        <Sparkles className="size-5 text-indigo-500" />
                        <h2 className="text-xl font-semibold">
                            Recent attempts
                        </h2>
                    </div>
                    <div className="mt-4 grid gap-3">
                        {speaking.recent_attempts.map((attempt) => (
                            <div
                                key={attempt.id}
                                className="rounded-md bg-slate-50 p-4 text-sm dark:bg-slate-900"
                            >
                                <p className="font-semibold">
                                    {attempt.prompt_title}
                                </p>
                                <p className="mt-1 text-slate-500">
                                    {attempt.word_count} words ·{' '}
                                    {attempt.fluency_score}% fluency ·{' '}
                                    {attempt.confidence_score}% confidence
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

function Metric({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-md bg-slate-50 px-4 py-3 dark:bg-slate-900">
            <p className="font-semibold">{value}</p>
            <p className="text-xs text-slate-500">{label}</p>
        </div>
    );
}
