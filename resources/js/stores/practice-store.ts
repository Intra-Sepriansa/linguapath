import { create } from 'zustand';

type PracticeState = {
    sessionId: number | null;
    currentIndex: number;
    selectedOptions: Record<number, number>;
    flaggedQuestions: Record<number, boolean>;
    startedAt: number;
    syncSession: (
        sessionId: number,
        selectedOptions: Record<number, number>,
    ) => void;
    setCurrentIndex: (index: number) => void;
    selectOption: (questionId: number, optionId: number) => void;
    toggleFlag: (questionId: number) => void;
    elapsedSeconds: () => number;
};

export const usePracticeStore = create<PracticeState>((set, get) => ({
    sessionId: null,
    currentIndex: 0,
    selectedOptions: {},
    flaggedQuestions: {},
    startedAt: Date.now(),
    syncSession: (sessionId, selectedOptions) =>
        set((state) => {
            if (state.sessionId === sessionId) {
                return {
                    selectedOptions: {
                        ...selectedOptions,
                        ...state.selectedOptions,
                    },
                };
            }

            return {
                sessionId,
                currentIndex: 0,
                selectedOptions,
                flaggedQuestions: {},
                startedAt: Date.now(),
            };
        }),
    setCurrentIndex: (index) => set({ currentIndex: index }),
    selectOption: (questionId, optionId) =>
        set((state) => ({
            selectedOptions: {
                ...state.selectedOptions,
                [questionId]: optionId,
            },
        })),
    toggleFlag: (questionId) =>
        set((state) => ({
            flaggedQuestions: {
                ...state.flaggedQuestions,
                [questionId]: !state.flaggedQuestions[questionId],
            },
        })),
    elapsedSeconds: () => Math.floor((Date.now() - get().startedAt) / 1000),
}));
