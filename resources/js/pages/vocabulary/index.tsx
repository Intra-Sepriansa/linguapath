import { Head, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    BookOpenCheck,
    Brain,
    CheckCircle2,
    Flame,
    Gauge,
    Layers3,
    RotateCcw,
    Search,
    Sparkles,
    Target,
    Volume2,
    XCircle,
} from 'lucide-react';
import type { ComponentType, MutableRefObject } from 'react';
import { useMemo, useRef, useState } from 'react';
import { SpotlightCard } from '@/components/reactbits/spotlight-card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    index as vocabularyIndex,
    mark as markVocabulary,
} from '@/routes/vocabulary';

type VocabularyStatus = 'learning' | 'mastered' | 'review_later' | 'weak';
type ViewMode = 'deck' | 'quiz' | 'review';
type FilterStatus = 'all' | VocabularyStatus;

type Word = {
    id: number;
    word: string;
    pronunciation_text: string;
    pronunciation_lookup_terms: string[];
    pronunciation_locale: string;
    meaning: string;
    usage_note: string;
    example_sentence: string;
    example_translation: string;
    category: string;
    difficulty: string;
    status: VocabularyStatus;
    status_label: string;
    review_count: number;
    last_reviewed_at: string | null;
    quiz_options: string[];
};

type SummaryBucket = {
    label: string;
    count: number;
};

type VocabularySummary = {
    total: number;
    learning: number;
    mastered: number;
    weak: number;
    review_later: number;
    available: number;
    mastery_rate: number;
    review_queue: number;
    categories: SummaryBucket[];
    difficulties: SummaryBucket[];
};

type MetricTone = 'cyan' | 'emerald' | 'indigo' | 'rose' | 'amber';

type DictionaryPhonetic = {
    audio?: string;
};

type DictionaryEntry = {
    phonetics?: DictionaryPhonetic[];
};

type DictionaryAudioCache = Record<string, string | null>;

const speechVoiceLoadTimeoutMs = 500;
const defaultPronunciationLocale = 'en-US';
const dictionaryApiBaseUrl = 'https://api.dictionaryapi.dev/api/v2/entries/en';
const preferredVoiceNames = [
    'Google US English',
    'Microsoft Aria',
    'Microsoft Jenny',
    'Microsoft Ava',
    'Samantha',
    'Alex',
];

const modes: Array<{
    value: ViewMode;
    label: string;
    icon: ComponentType<{ className?: string }>;
}> = [
    { value: 'deck', label: 'Deck', icon: Layers3 },
    { value: 'quiz', label: 'Quiz', icon: Target },
    { value: 'review', label: 'Review', icon: RotateCcw },
];

const statusFilters: Array<{ value: FilterStatus; label: string }> = [
    { value: 'all', label: 'Semua' },
    { value: 'learning', label: 'Dipelajari' },
    { value: 'weak', label: 'Sulit' },
    { value: 'review_later', label: 'Nanti' },
    { value: 'mastered', label: 'Hafal' },
];

async function resolveDictionaryAudioUrl(
    terms: string[],
    cache: DictionaryAudioCache,
): Promise<string | null> {
    const lookupTerms = [...new Set(terms.map(normalizeLookupTerm))]
        .filter(Boolean)
        .slice(0, 2);

    for (const term of lookupTerms) {
        if (Object.hasOwn(cache, term)) {
            const cachedAudioUrl = cache[term];

            if (cachedAudioUrl) {
                return cachedAudioUrl;
            }

            continue;
        }

        const audioUrl = await fetchDictionaryAudioUrl(term);
        cache[term] = audioUrl;

        if (audioUrl) {
            return audioUrl;
        }
    }

    return null;
}

async function fetchDictionaryAudioUrl(term: string): Promise<string | null> {
    try {
        const response = await fetch(
            `${dictionaryApiBaseUrl}/${encodeURIComponent(term)}`,
        );

        if (!response.ok) {
            return null;
        }

        const entries = (await response.json()) as DictionaryEntry[];

        if (!Array.isArray(entries)) {
            return null;
        }

        return selectDictionaryAudioUrl(entries);
    } catch {
        return null;
    }
}

function selectDictionaryAudioUrl(entries: DictionaryEntry[]): string | null {
    const audioUrls = entries
        .flatMap((entry) => entry.phonetics ?? [])
        .map((phonetic) => normalizeAudioUrl(phonetic.audio))
        .filter((audioUrl): audioUrl is string => Boolean(audioUrl));

    return (
        audioUrls.find((audioUrl) => /[-_]us(?:[-_.]|$)/i.test(audioUrl)) ??
        audioUrls[0] ??
        null
    );
}

function normalizeAudioUrl(audioUrl?: string): string | null {
    if (!audioUrl) {
        return null;
    }

    if (audioUrl.startsWith('//')) {
        return `https:${audioUrl}`;
    }

    return audioUrl.replace(/^http:\/\//, 'https://');
}

function normalizeLookupTerm(term: string): string {
    return term.trim().replace(/\s+/g, ' ').toLowerCase();
}

async function playPronunciationAudio(
    audioUrl: string,
    audioRef: MutableRefObject<HTMLAudioElement | null>,
): Promise<boolean> {
    try {
        const audio = new Audio(audioUrl);
        audioRef.current = audio;
        await audio.play();

        return true;
    } catch {
        return false;
    }
}

function resolveSpeechVoices(): Promise<SpeechSynthesisVoice[]> {
    const speech = window.speechSynthesis;
    const voices = speech.getVoices();

    if (voices.length > 0) {
        return Promise.resolve(voices);
    }

    return new Promise((resolve) => {
        let settled = false;
        const finalize = () => {
            if (settled) {
                return;
            }

            settled = true;
            window.clearTimeout(timeout);
            speech.removeEventListener('voiceschanged', finalize);
            resolve(speech.getVoices());
        };
        const timeout = window.setTimeout(finalize, speechVoiceLoadTimeoutMs);

        speech.addEventListener('voiceschanged', finalize, { once: true });
    });
}

function selectPronunciationVoice(
    voices: SpeechSynthesisVoice[],
    locale: string,
): SpeechSynthesisVoice | null {
    return (
        voices
            .filter((voice) =>
                normalizeVoiceLocale(voice.lang).startsWith('en'),
            )
            .sort(
                (first, second) =>
                    scoreVoice(second, locale) - scoreVoice(first, locale),
            )[0] ?? null
    );
}

function scoreVoice(voice: SpeechSynthesisVoice, locale: string): number {
    const voiceLocale = normalizeVoiceLocale(voice.lang);
    const preferredLocale = normalizeVoiceLocale(locale);
    const voiceName = voice.name.toLowerCase();
    let score = 0;

    if (voiceLocale === preferredLocale) {
        score += 100;
    }

    if (voiceLocale === defaultPronunciationLocale.toLowerCase()) {
        score += 80;
    }

    if (voiceLocale.startsWith('en-')) {
        score += 40;
    }

    if (
        preferredVoiceNames.some((name) =>
            voiceName.includes(name.toLowerCase()),
        )
    ) {
        score += 20;
    }

    if (voice.localService) {
        score += 5;
    }

    return score;
}

function normalizeVoiceLocale(locale: string): string {
    return locale.replace(/_/g, '-').toLowerCase();
}

export default function VocabularyIndex({
    words,
    summary,
}: {
    words: Word[];
    summary: VocabularySummary;
}) {
    const [mode, setMode] = useState<ViewMode>('deck');
    const [query, setQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<FilterStatus>('all');
    const [categoryFilter, setCategoryFilter] = useState('all');
    const [currentIndex, setCurrentIndex] = useState(0);
    const [flipped, setFlipped] = useState(false);
    const [answers, setAnswers] = useState<Record<number, string>>({});
    const speechRequestRef = useRef(0);
    const pronunciationAudioRef = useRef<HTMLAudioElement | null>(null);
    const dictionaryAudioCacheRef = useRef<DictionaryAudioCache>({});

    const categories = useMemo(
        () => ['all', ...summary.categories.map((category) => category.label)],
        [summary.categories],
    );
    const visibleWords = useMemo(
        () =>
            words.filter((word) => {
                const matchesQuery = [
                    word.word,
                    word.meaning,
                    word.usage_note,
                    word.example_sentence,
                    word.example_translation,
                    word.category,
                ]
                    .join(' ')
                    .toLowerCase()
                    .includes(query.toLowerCase());
                const matchesStatus =
                    statusFilter === 'all' || word.status === statusFilter;
                const matchesCategory =
                    categoryFilter === 'all' ||
                    word.category === categoryFilter;

                return matchesQuery && matchesStatus && matchesCategory;
            }),
        [categoryFilter, query, statusFilter, words],
    );
    const safeIndex = Math.min(
        Math.max(currentIndex, 0),
        Math.max(visibleWords.length - 1, 0),
    );
    const activeWord = visibleWords[safeIndex] ?? words[0];
    const activeAnswer = activeWord ? answers[activeWord.id] : undefined;
    const isCorrect = activeWord ? activeAnswer === activeWord.meaning : false;

    const mark = (word: Word, status: VocabularyStatus) => {
        router.patch(
            markVocabulary.url(word.id),
            { status },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const chooseWord = (index: number) => {
        setCurrentIndex(index);
        setFlipped(false);
    };

    const nextWord = () => {
        chooseWord((safeIndex + 1) % Math.max(visibleWords.length, 1));
    };

    const previousWord = () => {
        chooseWord(
            safeIndex === 0
                ? Math.max(visibleWords.length - 1, 0)
                : safeIndex - 1,
        );
    };

    const speak = async (word: Word) => {
        const requestId = speechRequestRef.current + 1;
        speechRequestRef.current = requestId;
        const canUseSpeechSynthesis =
            'speechSynthesis' in window &&
            typeof SpeechSynthesisUtterance !== 'undefined';

        pronunciationAudioRef.current?.pause();
        pronunciationAudioRef.current = null;

        if (canUseSpeechSynthesis) {
            window.speechSynthesis.cancel();
        }

        const dictionaryAudioUrl = await resolveDictionaryAudioUrl(
            word.pronunciation_lookup_terms,
            dictionaryAudioCacheRef.current,
        );

        if (speechRequestRef.current !== requestId) {
            return;
        }

        if (
            dictionaryAudioUrl &&
            (await playPronunciationAudio(
                dictionaryAudioUrl,
                pronunciationAudioRef,
            ))
        ) {
            return;
        }

        if (!canUseSpeechSynthesis) {
            return;
        }

        const voices = await resolveSpeechVoices();

        if (speechRequestRef.current !== requestId) {
            return;
        }

        const speech = window.speechSynthesis;

        const voice = selectPronunciationVoice(
            voices,
            word.pronunciation_locale,
        );
        const utterance = new SpeechSynthesisUtterance(
            word.pronunciation_text || word.word,
        );

        utterance.lang =
            voice?.lang ??
            word.pronunciation_locale ??
            defaultPronunciationLocale;
        utterance.voice = voice;
        utterance.rate = 0.82;
        utterance.pitch = 1;
        utterance.volume = 1;

        speech.speak(utterance);
    };

    return (
        <>
            <Head title="Vocabulary" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <section className="grid gap-6 xl:grid-cols-[1fr_0.38fr]">
                    <SpotlightCard className="p-6 md:p-8">
                        <div className="flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="inline-flex items-center gap-2 rounded-md bg-cyan-100 px-3 py-1.5 text-sm font-semibold text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200">
                                    <Sparkles className="size-4" />
                                    Vocabulary Command Deck
                                </div>
                                <h1 className="mt-5 text-4xl leading-tight font-semibold text-slate-950 md:text-5xl dark:text-white">
                                    {words.length} kata aktif, arti Indonesia,
                                    drill langsung.
                                </h1>
                                <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                                    Setiap kata punya arti Indonesia, fungsi
                                    penggunaan, contoh kalimat lengkap, dan
                                    terjemahan contoh.
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3 lg:min-w-80 lg:grid-cols-1">
                                <Metric
                                    icon={BookOpenCheck}
                                    label="Bank"
                                    value={`${summary.total} kata`}
                                    tone="indigo"
                                />
                                <Metric
                                    icon={CheckCircle2}
                                    label="Mastery"
                                    value={`${summary.mastery_rate}%`}
                                    tone="emerald"
                                />
                                <Metric
                                    icon={Flame}
                                    label="Review"
                                    value={`${summary.review_queue} kata`}
                                    tone="amber"
                                />
                            </div>
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-6">
                        <p className="text-sm font-medium text-indigo-600">
                            Status
                        </p>
                        <div className="mt-4 grid grid-cols-2 gap-3">
                            <SummaryItem
                                label="Dipelajari"
                                value={summary.learning}
                            />
                            <SummaryItem label="Sulit" value={summary.weak} />
                            <SummaryItem
                                label="Nanti"
                                value={summary.review_later}
                            />
                            <SummaryItem
                                label="Hafal"
                                value={summary.mastered}
                            />
                        </div>
                    </SpotlightCard>
                </section>

                <section className="grid gap-4 lg:grid-cols-[1fr_auto]">
                    <SpotlightCard className="p-4">
                        <div className="grid gap-3 md:grid-cols-[1fr_auto] md:items-center">
                            <label className="relative block">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={query}
                                    onChange={(event) =>
                                        setQuery(event.target.value)
                                    }
                                    className="h-11 w-full rounded-md border border-slate-200 bg-white pr-3 pl-10 text-sm transition outline-none focus:border-primary focus:ring-3 focus:ring-primary/15 dark:border-slate-800 dark:bg-slate-950"
                                    placeholder="Cari kata Inggris, arti Indonesia, kategori"
                                />
                            </label>

                            <select
                                value={categoryFilter}
                                onChange={(event) =>
                                    setCategoryFilter(event.target.value)
                                }
                                className="h-11 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium transition outline-none focus:border-primary focus:ring-3 focus:ring-primary/15 dark:border-slate-800 dark:bg-slate-950"
                            >
                                {categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category === 'all'
                                            ? 'Semua kategori'
                                            : category}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            {statusFilters.map((filter) => (
                                <button
                                    key={filter.value}
                                    type="button"
                                    onClick={() =>
                                        setStatusFilter(filter.value)
                                    }
                                    className={cn(
                                        'h-9 rounded-md border px-3 text-sm font-semibold transition',
                                        statusFilter === filter.value
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300',
                                    )}
                                >
                                    {filter.label}
                                </button>
                            ))}
                        </div>
                    </SpotlightCard>

                    <SpotlightCard className="p-2">
                        <div className="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-900">
                            {modes.map((item) => {
                                const Icon = item.icon;

                                return (
                                    <button
                                        key={item.value}
                                        type="button"
                                        onClick={() => setMode(item.value)}
                                        className={cn(
                                            'inline-flex h-11 items-center justify-center gap-2 rounded-md px-3 text-sm font-semibold transition',
                                            mode === item.value
                                                ? 'bg-white text-primary shadow-sm dark:bg-slate-950'
                                                : 'text-slate-500 hover:text-slate-950 dark:hover:text-white',
                                        )}
                                    >
                                        <Icon className="size-4" />
                                        {item.label}
                                    </button>
                                );
                            })}
                        </div>
                    </SpotlightCard>
                </section>

                {activeWord ? (
                    <section className="grid gap-6 xl:grid-cols-[0.35fr_0.65fr]">
                        <SpotlightCard className="p-5">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium text-indigo-600">
                                        Deck
                                    </p>
                                    <h2 className="mt-1 text-xl font-semibold">
                                        {visibleWords.length} kata
                                    </h2>
                                </div>
                                <Gauge className="size-5 text-slate-400" />
                            </div>

                            <div className="mt-5 grid max-h-[34rem] gap-2 overflow-y-auto pr-1">
                                {visibleWords.map((word, index) => (
                                    <button
                                        key={word.id}
                                        type="button"
                                        onClick={() => chooseWord(index)}
                                        className={cn(
                                            'rounded-lg border p-3 text-left transition',
                                            index === safeIndex
                                                ? 'border-primary bg-indigo-50 dark:bg-indigo-950/30'
                                                : 'border-slate-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-indigo-950/20',
                                        )}
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="font-semibold">
                                                {word.word}
                                            </span>
                                            <StatusBadge status={word.status} />
                                        </div>
                                        <p className="mt-1 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                            {word.meaning}
                                        </p>
                                        <p className="mt-2 line-clamp-2 text-xs leading-5 text-slate-500">
                                            {word.usage_note}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500">
                                            {word.category} · {word.difficulty}
                                        </p>
                                    </button>
                                ))}
                            </div>
                        </SpotlightCard>

                        <AnimatePresence mode="wait">
                            <motion.div
                                key={`${mode}-${activeWord.id}`}
                                initial={{ opacity: 0, y: 14, scale: 0.98 }}
                                animate={{ opacity: 1, y: 0, scale: 1 }}
                                exit={{ opacity: 0, y: -10, scale: 0.98 }}
                                transition={{ duration: 0.22 }}
                            >
                                {mode === 'deck' && (
                                    <DeckPanel
                                        word={activeWord}
                                        flipped={flipped}
                                        onFlip={() => setFlipped(!flipped)}
                                        onSpeak={() => void speak(activeWord)}
                                        onPrevious={previousWord}
                                        onNext={nextWord}
                                        onMark={mark}
                                    />
                                )}

                                {mode === 'quiz' && (
                                    <QuizPanel
                                        word={activeWord}
                                        selected={activeAnswer}
                                        isCorrect={isCorrect}
                                        onSelect={(answer) =>
                                            setAnswers((current) => ({
                                                ...current,
                                                [activeWord.id]: answer,
                                            }))
                                        }
                                        onNext={nextWord}
                                        onMark={mark}
                                    />
                                )}

                                {mode === 'review' && (
                                    <ReviewPanel
                                        words={visibleWords}
                                        onMark={mark}
                                    />
                                )}
                            </motion.div>
                        </AnimatePresence>
                    </section>
                ) : (
                    <SpotlightCard className="grid min-h-64 place-items-center p-8 text-center">
                        <div>
                            <Brain className="mx-auto size-10 text-slate-300" />
                            <p className="mt-4 font-semibold">
                                Tidak ada kata pada filter ini.
                            </p>
                        </div>
                    </SpotlightCard>
                )}
            </div>
        </>
    );
}

function DeckPanel({
    word,
    flipped,
    onFlip,
    onSpeak,
    onPrevious,
    onNext,
    onMark,
}: {
    word: Word;
    flipped: boolean;
    onFlip: () => void;
    onSpeak: () => void;
    onPrevious: () => void;
    onNext: () => void;
    onMark: (word: Word, status: VocabularyStatus) => void;
}) {
    return (
        <SpotlightCard className="p-6 md:p-8">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2">
                    <StatusBadge status={word.status} />
                    <span className="rounded-md bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-600 capitalize dark:bg-slate-900 dark:text-slate-300">
                        {word.category}
                    </span>
                    <span className="rounded-md bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-600 capitalize dark:bg-slate-900 dark:text-slate-300">
                        {word.difficulty}
                    </span>
                </div>
                <button
                    type="button"
                    onClick={onSpeak}
                    aria-label={`Dengar pelafalan bahasa Inggris untuk ${word.word}`}
                    title={`Dengar pelafalan ${word.pronunciation_locale}`}
                    className="inline-flex size-10 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700 dark:border-slate-800 dark:bg-slate-950"
                >
                    <Volume2 className="size-4" />
                </button>
            </div>

            <motion.button
                type="button"
                onClick={onFlip}
                animate={{ rotateY: flipped ? 180 : 0 }}
                transition={{ duration: 0.35 }}
                className="relative mt-7 min-h-96 w-full overflow-hidden rounded-lg border border-indigo-100 bg-indigo-50/70 p-7 text-left shadow-inner dark:border-indigo-900 dark:bg-indigo-950/20"
                style={{ transformStyle: 'preserve-3d' }}
            >
                <div
                    className={cn(
                        'grid min-h-64 place-items-center',
                        flipped && 'opacity-0',
                    )}
                >
                    <div className="text-center">
                        <p className="text-sm font-semibold tracking-[0.16em] text-indigo-500 uppercase">
                            Kata Inggris
                        </p>
                        <h2 className="mt-4 text-5xl font-semibold text-slate-950 md:text-7xl dark:text-white">
                            {word.word}
                        </h2>
                        <div className="mx-auto mt-5 max-w-xl rounded-lg border border-emerald-200 bg-white/80 p-4 dark:border-emerald-900 dark:bg-slate-950/70">
                            <p className="text-xs font-semibold tracking-[0.14em] text-emerald-600 uppercase">
                                Arti Bahasa Indonesia
                            </p>
                            <p className="mt-2 text-3xl font-semibold text-emerald-800 dark:text-emerald-200">
                                {word.meaning}
                            </p>
                        </div>
                        <div className="mt-5 grid gap-3 text-left md:grid-cols-2">
                            <InfoBlock
                                label="Digunakan untuk"
                                value={word.usage_note}
                            />
                            <InfoBlock
                                label="Contoh kalimat lengkap"
                                value={word.example_sentence}
                            />
                        </div>
                        <InfoBlock
                            className="mt-3"
                            label="Terjemahan contoh"
                            value={word.example_translation}
                        />
                    </div>
                </div>
                <div
                    className={cn(
                        'absolute inset-7 grid place-items-center rounded-lg text-center opacity-0',
                        flipped && 'opacity-100',
                    )}
                    style={{ transform: 'rotateY(180deg)' }}
                >
                    <div>
                        <p className="text-sm font-semibold tracking-[0.16em] text-emerald-600 uppercase">
                            Arti Bahasa Indonesia
                        </p>
                        <h3 className="mt-4 text-4xl font-semibold text-slate-950 dark:text-white">
                            {word.meaning}
                        </h3>
                        <p className="mt-6 text-base leading-7 text-slate-600 dark:text-slate-300">
                            {word.usage_note}
                        </p>
                        <p className="mt-4 rounded-lg bg-white/80 p-4 text-base leading-7 text-slate-700 dark:bg-slate-950/70 dark:text-slate-200">
                            {word.example_sentence}
                        </p>
                        <p className="mt-3 rounded-lg bg-emerald-50 p-4 text-base leading-7 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100">
                            {word.example_translation}
                        </p>
                        <p className="mt-6 text-base leading-7 text-slate-600 dark:text-slate-300">
                            Jumlah review: {word.review_count}
                        </p>
                    </div>
                </div>
            </motion.button>

            <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                <div className="flex gap-2">
                    <Button variant="outline" onClick={onPrevious}>
                        <RotateCcw className="size-4" />
                        Sebelumnya
                    </Button>
                    <Button onClick={onNext}>Berikutnya</Button>
                </div>
                <StatusActions word={word} onMark={onMark} />
            </div>
        </SpotlightCard>
    );
}

function QuizPanel({
    word,
    selected,
    isCorrect,
    onSelect,
    onNext,
    onMark,
}: {
    word: Word;
    selected?: string;
    isCorrect: boolean;
    onSelect: (answer: string) => void;
    onNext: () => void;
    onMark: (word: Word, status: VocabularyStatus) => void;
}) {
    return (
        <SpotlightCard className="p-6 md:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-indigo-600">
                        Drill Arti Bahasa Indonesia
                    </p>
                    <h2 className="mt-2 text-4xl font-semibold text-slate-950 md:text-6xl dark:text-white">
                        {word.word}
                    </h2>
                </div>
                <StatusBadge status={word.status} />
            </div>

            <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                Pilih arti Bahasa Indonesia yang paling tepat untuk kata ini.
            </p>

            <div className="mt-8 grid gap-3">
                {word.quiz_options.map((option) => {
                    const active = selected === option;
                    const correct = selected && option === word.meaning;
                    const wrong = active && !isCorrect;

                    return (
                        <motion.button
                            key={option}
                            type="button"
                            whileHover={{ x: selected ? 0 : 4 }}
                            whileTap={{ scale: selected ? 1 : 0.99 }}
                            onClick={() => onSelect(option)}
                            className={cn(
                                'min-h-14 rounded-lg border p-4 text-left text-sm font-semibold transition',
                                'border-slate-200 bg-white hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-indigo-950/20',
                                correct &&
                                    'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100',
                                wrong &&
                                    'border-rose-300 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-100',
                            )}
                        >
                            {option}
                        </motion.button>
                    );
                })}
            </div>

            <AnimatePresence>
                {selected && (
                    <motion.div
                        initial={{ opacity: 0, y: 8, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -6 }}
                        className={cn(
                            'mt-6 rounded-lg border p-4',
                            isCorrect
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100'
                                : 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100',
                        )}
                    >
                        <div className="flex items-start gap-3">
                            {isCorrect ? (
                                <CheckCircle2 className="mt-0.5 size-5 text-emerald-600" />
                            ) : (
                                <XCircle className="mt-0.5 size-5 text-amber-600" />
                            )}
                            <div>
                                <p className="font-semibold">
                                    {isCorrect ? 'Benar' : 'Belum tepat'}
                                </p>
                                <p className="mt-2 text-sm leading-6">
                                    {word.word} berarti{' '}
                                    <span className="font-semibold">
                                        {word.meaning}
                                    </span>
                                    .
                                </p>
                                <p className="mt-2 text-sm leading-6">
                                    {word.usage_note}
                                </p>
                                <p className="mt-2 text-sm leading-6">
                                    {word.example_sentence}
                                </p>
                                <p className="mt-2 text-sm leading-6">
                                    {word.example_translation}
                                </p>
                            </div>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>

            <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                <Button onClick={onNext}>Drill berikutnya</Button>
                <StatusActions word={word} onMark={onMark} />
            </div>
        </SpotlightCard>
    );
}

function ReviewPanel({
    words,
    onMark,
}: {
    words: Word[];
    onMark: (word: Word, status: VocabularyStatus) => void;
}) {
    return (
        <SpotlightCard className="p-6">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-indigo-600">
                        Review Kata
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold">
                        {words.length} kata aktif
                    </h2>
                </div>
                <Brain className="size-6 text-indigo-500" />
            </div>

            <div className="mt-6 grid gap-3 md:grid-cols-2">
                {words.map((word, index) => (
                    <motion.article
                        key={word.id}
                        initial={{ opacity: 0, y: 8 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: Math.min(index * 0.02, 0.2) }}
                        className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="text-xl font-semibold">
                                    {word.word}
                                </h3>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    Arti Indonesia: {word.meaning}
                                </p>
                                <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    {word.usage_note}
                                </p>
                            </div>
                            <StatusBadge status={word.status} />
                        </div>
                        <p className="mt-4 rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                            {word.example_sentence}
                        </p>
                        <p className="mt-3 rounded-lg bg-emerald-50 p-3 text-sm leading-6 text-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100">
                            {word.example_translation}
                        </p>
                        <div className="mt-4">
                            <StatusActions
                                word={word}
                                onMark={onMark}
                                compact
                            />
                        </div>
                    </motion.article>
                ))}
            </div>
        </SpotlightCard>
    );
}

function StatusActions({
    word,
    onMark,
    compact = false,
}: {
    word: Word;
    onMark: (word: Word, status: VocabularyStatus) => void;
    compact?: boolean;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <Button
                size="sm"
                className={compact ? 'h-8' : undefined}
                onClick={() => onMark(word, 'mastered')}
            >
                <CheckCircle2 className="size-4" />
                Hafal
            </Button>
            <Button
                size="sm"
                variant="outline"
                className={compact ? 'h-8' : undefined}
                onClick={() => onMark(word, 'review_later')}
            >
                <RotateCcw className="size-4" />
                Nanti
            </Button>
            <Button
                size="sm"
                variant="ghost"
                className={compact ? 'h-8' : undefined}
                onClick={() => onMark(word, 'weak')}
            >
                Sulit
            </Button>
        </div>
    );
}

function InfoBlock({
    label,
    value,
    className,
}: {
    label: string;
    value: string;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'rounded-lg border border-slate-200 bg-white/85 p-4 dark:border-slate-800 dark:bg-slate-950/75',
                className,
            )}
        >
            <p className="text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                {label}
            </p>
            <p className="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">
                {value}
            </p>
        </div>
    );
}

function StatusBadge({ status }: { status: VocabularyStatus }) {
    const labels: Record<VocabularyStatus, string> = {
        learning: 'dipelajari',
        mastered: 'hafal',
        review_later: 'nanti',
        weak: 'sulit',
    };

    return (
        <span
            className={cn(
                'rounded-md px-2.5 py-1 text-xs font-semibold',
                status === 'learning' &&
                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
                status === 'mastered' &&
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
                status === 'review_later' &&
                    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
                status === 'weak' &&
                    'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
            )}
        >
            {labels[status]}
        </span>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
    value: string;
    tone: MetricTone;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
            <div className="flex items-center gap-3">
                <div
                    className={cn(
                        'flex size-9 items-center justify-center rounded-md',
                        tone === 'cyan' &&
                            'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-200',
                        tone === 'emerald' &&
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
                        tone === 'indigo' &&
                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200',
                        tone === 'rose' &&
                            'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200',
                        tone === 'amber' &&
                            'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
                    )}
                >
                    <Icon className="size-4" />
                </div>
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    {label}
                </p>
            </div>
            <p className="mt-3 font-semibold text-slate-950 dark:text-white">
                {value}
            </p>
        </div>
    );
}

function SummaryItem({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
            <p className="text-2xl font-semibold">{value}</p>
            <p className="text-sm text-slate-500">{label}</p>
        </div>
    );
}

VocabularyIndex.layout = {
    breadcrumbs: [{ title: 'Vocabulary', href: vocabularyIndex() }],
};
