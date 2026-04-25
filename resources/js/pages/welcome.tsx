import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpenCheck,
    CirclePlay,
    ClipboardCheck,
    Headphones,
    Lock,
    Rocket,
    Sparkles,
} from 'lucide-react';
import type { ElementType } from 'react';
import { CountUp } from '@/components/reactbits/count-up';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import { index as studyPathIndex } from '@/routes/study-path';

type Feature = {
    icon: ElementType;
    title: string;
    text: string;
    accent: string;
};

const features: Feature[] = [
    {
        icon: BarChart3,
        title: 'Deep Performance Analytics',
        text: 'Find weak patterns across Listening, Structure, and Reading before they become repeated mistakes.',
        accent: 'bg-indigo-100 text-indigo-700',
    },
    {
        icon: ClipboardCheck,
        title: 'Guided Exam Simulation',
        text: 'Move from daily learning into timed TOEFL-style sessions with review loops after every attempt.',
        accent: 'bg-emerald-100 text-emerald-700',
    },
    {
        icon: BookOpenCheck,
        title: 'Mistake Bank',
        text: 'Wrong answers are saved with explanations, so review becomes part of the learning path.',
        accent: 'bg-rose-100 text-rose-700',
    },
];

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;
    const primaryHref = auth.user ? dashboard() : register();
    const pathHref = auth.user ? studyPathIndex() : login();

    return (
        <>
            <Head title="LinguaPath" />
            <main className="min-h-screen bg-[#fbfaff] text-slate-950 dark:bg-slate-950 dark:text-white">
                <header className="sticky top-0 z-30 border-b border-violet-100 bg-white/90 backdrop-blur dark:border-indigo-950 dark:bg-slate-950/90">
                    <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-5">
                        <Link
                            href={auth.user ? dashboard() : '/'}
                            className="text-lg font-semibold tracking-tight"
                        >
                            LinguaPath
                        </Link>
                        <nav className="hidden items-center gap-10 text-sm font-medium text-slate-700 md:flex dark:text-slate-200">
                            <a href="#features">Features</a>
                            <Link href={pathHref}>Study Path</Link>
                        </nav>
                        <div className="flex items-center gap-2 text-sm">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost">
                                        <Link href={login()}>Login</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button
                                            asChild
                                            className="bg-primary shadow-sm shadow-indigo-500/25 hover:bg-primary/90"
                                        >
                                            <Link href={register()}>
                                                Start Learning
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <section className="mx-auto grid min-h-[calc(100svh-4rem)] w-full max-w-7xl items-center gap-10 px-5 py-14 lg:grid-cols-[1fr_0.95fr] lg:py-20">
                    <div>
                        <div className="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-white px-4 py-2 text-sm font-medium shadow-sm shadow-indigo-500/5 dark:border-indigo-900 dark:bg-slate-900">
                            <Sparkles className="size-4 text-indigo-600" />
                            The intelligent path to TOEFL success
                        </div>
                        <h1 className="mt-7 max-w-3xl text-5xl leading-[1.02] font-semibold tracking-normal text-slate-950 md:text-7xl dark:text-white">
                            Build your TOEFL habit in{' '}
                            <span className="relative inline-block text-primary">
                                60 days.
                                <span className="absolute -bottom-1 left-0 h-1 w-full rounded-full bg-emerald-400" />
                            </span>
                        </h1>
                        <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-700 dark:text-slate-300">
                            Practice{' '}
                            <span className="font-semibold text-indigo-700 dark:text-indigo-300">
                                Listening
                            </span>
                            ,{' '}
                            <span className="font-semibold text-indigo-700 dark:text-indigo-300">
                                Structure
                            </span>
                            , and{' '}
                            <span className="font-semibold text-indigo-700 dark:text-indigo-300">
                                Reading
                            </span>{' '}
                            through daily lessons, guided examples, practice,
                            and mistake review.
                        </p>
                        <div className="mt-9 flex flex-wrap gap-4">
                            <Button
                                asChild
                                size="lg"
                                className="h-12 bg-primary px-7 shadow-lg shadow-indigo-500/20 hover:bg-primary/90"
                            >
                                <Link href={primaryHref}>
                                    Start Learning Now{' '}
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="secondary"
                                className="h-12 px-7 text-primary"
                            >
                                <Link href={pathHref}>View Study Path</Link>
                            </Button>
                        </div>
                        <div className="mt-14 grid max-w-2xl grid-cols-3 gap-6 border-t border-violet-100 pt-8 dark:border-indigo-950">
                            {[
                                ['10', 'k+', 'Students'],
                                ['1400', '+', 'Questions'],
                                ['60', '', 'Day Path'],
                            ].map(([value, suffix, label]) => (
                                <div key={label}>
                                    <div className="text-3xl font-semibold">
                                        <CountUp value={Number(value)} />
                                        {suffix}
                                    </div>
                                    <p className="mt-1 text-sm font-medium tracking-wide text-slate-600 dark:text-slate-400">
                                        {label}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-lg border border-violet-100 bg-white p-6 shadow-[0_24px_70px_rgba(79,70,229,0.13)] dark:border-indigo-900 dark:bg-slate-950">
                        <div className="flex items-center justify-between border-b border-violet-100 pb-4 dark:border-indigo-950">
                            <h2 className="text-2xl font-semibold">
                                Today&apos;s Path
                            </h2>
                            <span className="rounded-full bg-indigo-50 px-4 py-1 text-sm font-semibold text-primary dark:bg-indigo-950">
                                Day 12
                            </span>
                        </div>
                        <div className="mt-5 grid gap-3">
                            <PathPreviewItem
                                icon={Headphones}
                                title="Listening Warm-up"
                                meta="Academic lectures - 15 mins"
                                tone="emerald"
                                progress={88}
                            />
                            <PathPreviewItem
                                icon={BookOpenCheck}
                                title="Parallel Structure Lesson"
                                meta="Guided examples - Up next"
                                tone="indigo"
                                active
                            />
                            <PathPreviewItem
                                icon={ClipboardCheck}
                                title="Lesson Practice"
                                meta="25 questions - Locked until lesson"
                                tone="slate"
                                locked
                            />
                        </div>
                    </div>
                </section>

                <section
                    id="features"
                    className="mx-auto w-full max-w-7xl px-5 py-20"
                >
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-semibold tracking-normal md:text-4xl">
                            Everything you need to score higher
                        </h2>
                        <p className="mt-4 text-slate-600 dark:text-slate-300">
                            A rigorous TOEFL path broken into daily learning
                            chunks, practice targets, and review decisions.
                        </p>
                    </div>

                    <div className="mt-16 grid gap-6 lg:grid-cols-3">
                        {features.map((feature) => (
                            <article
                                key={feature.title}
                                className="rounded-lg border border-violet-100 bg-white p-8 shadow-sm dark:border-indigo-950 dark:bg-slate-950"
                            >
                                <div
                                    className={`flex size-12 items-center justify-center rounded-md ${feature.accent}`}
                                >
                                    <feature.icon className="size-5" />
                                </div>
                                <h3 className="mt-7 text-2xl font-semibold">
                                    {feature.title}
                                </h3>
                                <p className="mt-3 leading-7 text-slate-600 dark:text-slate-300">
                                    {feature.text}
                                </p>
                            </article>
                        ))}
                    </div>

                    <div className="mt-6 grid gap-6 lg:grid-cols-[0.8fr_1.6fr]">
                        <article className="rounded-lg border border-violet-100 bg-white p-8 shadow-sm dark:border-indigo-950 dark:bg-slate-950">
                            <div className="flex size-12 items-center justify-center rounded-md bg-sky-100 text-sky-700">
                                <Rocket className="size-5" />
                            </div>
                            <h3 className="mt-7 text-2xl font-semibold">
                                Score Journey
                            </h3>
                            <p className="mt-3 leading-7 text-slate-600 dark:text-slate-300">
                                Every lesson ends in a focused practice loop, so
                                progress is earned through comprehension, not
                                guessing.
                            </p>
                        </article>
                        <article className="flex min-h-64 flex-col items-center justify-center rounded-lg border border-violet-100 bg-violet-50/80 p-8 text-center dark:border-indigo-950 dark:bg-indigo-950/30">
                            <h3 className="text-3xl font-semibold">
                                Ready to start your path?
                            </h3>
                            <p className="mt-4 max-w-xl text-slate-600 dark:text-slate-300">
                                Learn the concept, see the pattern, practice
                                under pressure, then review the exact mistake.
                            </p>
                            <Button
                                asChild
                                className="mt-8 h-12 bg-primary px-8 shadow-lg shadow-indigo-500/20 hover:bg-primary/90"
                            >
                                <Link href={primaryHref}>
                                    Create Free Account{' '}
                                    <Rocket className="size-4" />
                                </Link>
                            </Button>
                        </article>
                    </div>
                </section>
            </main>
        </>
    );
}

function PathPreviewItem({
    icon: Icon,
    title,
    meta,
    tone,
    progress,
    active = false,
    locked = false,
}: {
    icon: ElementType;
    title: string;
    meta: string;
    tone: 'emerald' | 'indigo' | 'slate';
    progress?: number;
    active?: boolean;
    locked?: boolean;
}) {
    const tones = {
        emerald: 'bg-emerald-100 text-emerald-700',
        indigo: 'bg-indigo-100 text-indigo-700',
        slate: 'bg-slate-100 text-slate-500',
    };

    return (
        <div
            className={`flex items-center gap-4 rounded-lg border p-4 transition ${
                active
                    ? 'border-indigo-400 bg-indigo-50/70 shadow-sm shadow-indigo-500/10'
                    : 'border-violet-100 bg-white dark:border-indigo-950 dark:bg-slate-950'
            }`}
        >
            <div
                className={`flex size-11 shrink-0 items-center justify-center rounded-full ${tones[tone]}`}
            >
                <Icon className="size-5" />
            </div>
            <div className="min-w-0 flex-1">
                <h3 className="font-semibold">{title}</h3>
                <p className="truncate text-sm text-slate-600 dark:text-slate-400">
                    {meta}
                </p>
            </div>
            {progress ? (
                <div className="h-2 w-16 rounded-full bg-violet-100">
                    <div
                        className="h-2 rounded-full bg-emerald-600"
                        style={{ width: `${progress}%` }}
                    />
                </div>
            ) : locked ? (
                <Lock className="size-5 text-slate-400" />
            ) : (
                <CirclePlay className="size-8 fill-primary text-primary" />
            )}
        </div>
    );
}
