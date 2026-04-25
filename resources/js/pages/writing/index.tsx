import { Head, router } from '@inertiajs/react';
import { FileText, Send, Sparkles } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { store as storeWritingSubmission } from '@/routes/writing/submissions';

type Prompt = {
    id: number;
    title: string;
    prompt_type: string;
    skill_level: string;
    prompt: string;
    suggested_minutes: number;
    min_words: number;
    rubric: string[];
    sample_response: string | null;
};

type WritingPayload = {
    prompts: Prompt[];
    recent_submissions: Array<{
        id: number;
        prompt_title: string;
        word_count: number;
        overall_score: number;
        submitted_at: string | null;
    }>;
    summary: { submissions: number; words: number; average_score: number };
};

export default function WritingIndex({ writing }: { writing: WritingPayload }) {
    const [promptId, setPromptId] = useState(writing.prompts[0]?.id ?? 0);
    const [responseText, setResponseText] = useState('');
    const activePrompt = useMemo(
        () =>
            writing.prompts.find((prompt) => prompt.id === promptId) ??
            writing.prompts[0],
        [promptId, writing.prompts],
    );
    const wordCount = responseText.trim()
        ? responseText.trim().split(/\s+/).length
        : 0;

    const submit = () => {
        if (!activePrompt) {
            return;
        }

        router.post(
            storeWritingSubmission.url(),
            {
                writing_prompt_id: activePrompt.id,
                response_text: responseText,
            },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Writing Practice" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-indigo-100 bg-white p-7 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200">
                                <FileText className="size-4" />
                                Writing Room
                            </div>
                            <h1 className="mt-5 text-4xl font-semibold">
                                Build sentences into clear paragraphs
                            </h1>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Practice sentence building, opinion paragraphs,
                                rewrite tasks, and clear academic responses.
                            </p>
                        </div>
                        <div className="grid grid-cols-3 gap-3 text-center text-sm">
                            <Metric
                                label="Submissions"
                                value={writing.summary.submissions}
                            />
                            <Metric
                                label="Words"
                                value={writing.summary.words}
                            />
                            <Metric
                                label="Avg score"
                                value={`${writing.summary.average_score}%`}
                            />
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                    <aside className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <h2 className="font-semibold">Prompt library</h2>
                        <div className="mt-4 grid gap-3">
                            {writing.prompts.map((prompt) => (
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
                                    {activePrompt.rubric.map((item) => (
                                        <span
                                            key={item}
                                            className="rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200"
                                        >
                                            {item}
                                        </span>
                                    ))}
                                </div>
                                <textarea
                                    value={responseText}
                                    onChange={(event) =>
                                        setResponseText(event.target.value)
                                    }
                                    placeholder="Write your answer here..."
                                    className="mt-5 min-h-72 w-full rounded-md border border-slate-200 bg-white p-4 text-sm leading-7 dark:border-slate-800 dark:bg-slate-950"
                                />
                                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                                    <p className="text-sm text-slate-500">
                                        {wordCount}/{activePrompt.min_words}{' '}
                                        words · suggested{' '}
                                        {activePrompt.suggested_minutes} min
                                    </p>
                                    <Button
                                        type="button"
                                        onClick={submit}
                                        disabled={
                                            responseText.trim().length < 20
                                        }
                                    >
                                        <Send className="size-4" />
                                        Submit Writing
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
                            Recent submissions
                        </h2>
                    </div>
                    <div className="mt-4 grid gap-3">
                        {writing.recent_submissions.map((submission) => (
                            <div
                                key={submission.id}
                                className="rounded-md bg-slate-50 p-4 text-sm dark:bg-slate-900"
                            >
                                <p className="font-semibold">
                                    {submission.prompt_title}
                                </p>
                                <p className="mt-1 text-slate-500">
                                    {submission.word_count} words ·{' '}
                                    {submission.overall_score}% score ·{' '}
                                    {submission.submitted_at}
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
