import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    BookOpenCheck,
    Brain,
    Gauge,
    Headphones,
    Layers3,
    LibraryBig,
    Play,
    Shuffle,
    SlidersHorizontal,
    Sparkles,
    Target,
    Timer,
    Zap,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    start as startPractice,
    setup as practiceSetup,
} from '@/routes/practice';
import type { SkillType } from '@/types';

const schema = z.object({
    section_type: z.enum(['listening', 'structure', 'reading', 'mixed']),
    mode: z.enum(['quick', 'focus', 'weakness', 'review', 'lesson']),
    question_count: z.number().min(1).max(50),
});

type FormValues = z.infer<typeof schema>;

type PracticeSetupProps = {
    currentDay: {
        id: number;
        day_number: number;
        title: string;
        focus_skill: SkillType;
    } | null;
    sections: Array<{
        value: SkillType;
        label: string;
        total_questions: number;
    }>;
    modes: Array<{
        value: FormValues['mode'];
        label: string;
        description: string;
    }>;
};

type SectionMeta = {
    icon: ComponentType<{ className?: string }>;
    accent: string;
    panel: string;
};

const sectionMeta: Record<FormValues['section_type'], SectionMeta> = {
    structure: {
        icon: Brain,
        accent: 'bg-indigo-500',
        panel: 'border-indigo-200 bg-indigo-50/80 dark:border-indigo-900 dark:bg-indigo-950/20',
    },
    listening: {
        icon: Headphones,
        accent: 'bg-cyan-500',
        panel: 'border-cyan-200 bg-cyan-50/80 dark:border-cyan-900 dark:bg-cyan-950/20',
    },
    reading: {
        icon: LibraryBig,
        accent: 'bg-emerald-500',
        panel: 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900 dark:bg-emerald-950/20',
    },
    mixed: {
        icon: Layers3,
        accent: 'bg-amber-500',
        panel: 'border-amber-200 bg-amber-50/80 dark:border-amber-900 dark:bg-amber-950/20',
    },
};

const modeIcons: Record<
    FormValues['mode'],
    ComponentType<{ className?: string }>
> = {
    quick: Zap,
    focus: Target,
    weakness: Gauge,
    review: Shuffle,
    lesson: BookOpenCheck,
};

const questionCounts = [5, 10, 12, 20, 30, 50];

export default function PracticeSetup({
    currentDay,
    sections,
    modes,
}: PracticeSetupProps) {
    'use no memo';

    const defaultSection =
        currentDay?.focus_skill && currentDay.focus_skill !== 'vocabulary'
            ? currentDay.focus_skill
            : 'structure';

    const { handleSubmit, setValue, watch } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            section_type: defaultSection,
            mode: 'quick',
            question_count: 12,
        },
    });

    // eslint-disable-next-line react-hooks/incompatible-library
    const values = watch();
    const selectedSection = sections.find(
        (section) => section.value === values.section_type,
    );
    const selectedMode = modes.find((mode) => mode.value === values.mode);
    const selectedBankSize = selectedSection?.total_questions ?? 0;

    const submit = (data: FormValues) => {
        router.post(startPractice.url(), {
            ...data,
            ...(data.mode === 'lesson' && currentDay
                ? { study_day_id: currentDay.id }
                : {}),
        });
    };

    return (
        <>
            <Head title="Practice Setup" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section>
                    <SpotlightCard className="p-6 md:p-8">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-primary dark:bg-indigo-950 dark:text-indigo-200">
                                    <Sparkles className="size-4" />
                                    Adaptive Practice Lab
                                </div>
                                <h1 className="mt-5 text-4xl leading-tight font-semibold text-slate-950 md:text-5xl dark:text-white">
                                    Bangun sesi latihan yang tepat untuk target
                                    hari ini.
                                </h1>
                                <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                                    Pilih skill, mode, dan jumlah soal. Setiap
                                    sesi mengambil soal secara acak dari bank
                                    latihan agar pola yang muncul terus
                                    berganti.
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3 lg:min-w-80 lg:grid-cols-1">
                                <SetupMetric
                                    icon={Timer}
                                    label="Tempo"
                                    value={`${values.question_count} soal`}
                                />
                                <SetupMetric
                                    icon={Shuffle}
                                    label="Bank"
                                    value={`${selectedBankSize} soal`}
                                />
                                <SetupMetric
                                    icon={Target}
                                    label="Mode"
                                    value={selectedMode?.label ?? 'Quick'}
                                />
                            </div>
                        </div>
                    </SpotlightCard>
                </section>

                <form
                    onSubmit={handleSubmit(submit)}
                    className="grid gap-6 xl:grid-cols-[1fr_0.9fr]"
                >
                    <SpotlightCard className="p-6">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-indigo-600">
                                    Kategori
                                </p>
                                <h2 className="mt-1 text-2xl font-semibold">
                                    Kategori latihan
                                </h2>
                            </div>
                            <SlidersHorizontal className="size-5 text-slate-400" />
                        </div>

                        <div className="mt-6 grid grid-cols-1 gap-3">
                            {sections.map((section) => {
                                const value =
                                    section.value as FormValues['section_type'];
                                const meta = sectionMeta[value];
                                const Icon = meta.icon;
                                const active = values.section_type === value;

                                return (
                                    <motion.button
                                        key={section.value}
                                        type="button"
                                        aria-pressed={active}
                                        whileHover={{ y: active ? 0 : -3 }}
                                        whileTap={{ scale: 0.98 }}
                                        onClick={() =>
                                            setValue('section_type', value)
                                        }
                                        className={cn(
                                            'rounded-lg border p-4 text-left transition',
                                            active
                                                ? `${meta.panel} ring-2 ring-primary/20`
                                                : 'border-slate-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-indigo-950/20',
                                        )}
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="flex items-start gap-3">
                                                <div
                                                    className={cn(
                                                        'flex size-10 items-center justify-center rounded-md text-white',
                                                        meta.accent,
                                                    )}
                                                >
                                                    <Icon className="size-5" />
                                                </div>
                                                <div>
                                                    <p className="font-semibold">
                                                        {section.label}
                                                    </p>
                                                    <p className="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                                        {
                                                            section.total_questions
                                                        }{' '}
                                                        soal tersedia
                                                    </p>
                                                </div>
                                            </div>
                                            <span className="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                                                {section.total_questions}
                                            </span>
                                        </div>
                                    </motion.button>
                                );
                            })}
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-6">
                        <div className="grid gap-6">
                            <div>
                                <Label className="text-sm font-medium text-indigo-600">
                                    Mode
                                </Label>
                                <div className="mt-3 grid gap-3">
                                    {modes.map((mode) => {
                                        const Icon = modeIcons[mode.value];
                                        const active =
                                            values.mode === mode.value;

                                        return (
                                            <button
                                                key={mode.value}
                                                type="button"
                                                aria-pressed={active}
                                                onClick={() =>
                                                    setValue('mode', mode.value)
                                                }
                                                className={cn(
                                                    'flex items-start gap-3 rounded-lg border p-3 text-left transition',
                                                    active
                                                        ? 'border-primary bg-indigo-50 text-slate-950 dark:bg-indigo-950/30 dark:text-white'
                                                        : 'border-slate-200 hover:border-indigo-200 hover:bg-indigo-50/50 dark:border-slate-800 dark:hover:bg-indigo-950/20',
                                                )}
                                            >
                                                <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-slate-100 text-primary dark:bg-slate-900">
                                                    <Icon className="size-4" />
                                                </div>
                                                <div>
                                                    <p className="font-semibold">
                                                        {mode.label}
                                                    </p>
                                                    <p className="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                                        {mode.description}
                                                    </p>
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <Label className="text-sm font-medium text-indigo-600">
                                    Jumlah soal
                                </Label>
                                <div className="mt-3 grid grid-cols-3 gap-2">
                                    {questionCounts.map((count) => (
                                        <button
                                            key={count}
                                            type="button"
                                            aria-pressed={
                                                values.question_count === count
                                            }
                                            onClick={() =>
                                                setValue(
                                                    'question_count',
                                                    count,
                                                )
                                            }
                                            className={cn(
                                                'h-11 rounded-md border text-sm font-semibold transition',
                                                values.question_count === count
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-slate-200 bg-white hover:border-indigo-200 hover:bg-indigo-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-indigo-950/20',
                                            )}
                                        >
                                            {count}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <Button className="h-12 w-full bg-primary text-base hover:bg-primary/90">
                                <Play className="size-4" />
                                Mulai Practice
                            </Button>
                        </div>
                    </SpotlightCard>
                </form>
            </div>
        </>
    );
}

function SetupMetric({
    icon: Icon,
    label,
    value,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-lg border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
            <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-md bg-white text-primary dark:bg-slate-950">
                    <Icon className="size-4" />
                </div>
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    {label}
                </p>
            </div>
            <p className="mt-3 leading-6 font-semibold text-slate-950 dark:text-white">
                {value}
            </p>
        </div>
    );
}

PracticeSetup.layout = {
    breadcrumbs: [{ title: 'Practice', href: practiceSetup() }],
};
