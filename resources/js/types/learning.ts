export type SkillType =
    | 'listening'
    | 'structure'
    | 'reading'
    | 'vocabulary'
    | 'mixed';

export type StudyDaySummary = {
    id: number;
    day_number: number;
    title: string;
    focus_skill: SkillType;
    focus_label: string;
    objective: string;
    estimated_minutes: number;
    completed: boolean;
    lesson_id: number | null;
    assessment: {
        status: 'pending' | 'passed' | 'failed';
        label: string;
        score: number | null;
        correct_answers: number;
        total_questions: number;
        passing_score: number;
        requires_all_correct: boolean;
        attempted_at: string | null;
    };
};

export type PracticeQuestion = {
    id: number;
    answer_id: number;
    position: number;
    section_type: SkillType;
    question_type: string;
    difficulty: string;
    question_text: string;
    passage_text: string | null;
    transcript: string | null;
    selected_option_id: number | null;
    is_answered: boolean;
    is_correct: boolean | null;
    correct_option_id: number | null;
    correct_option_text: string | null;
    explanation: string | null;
    options: Array<{ id: number; label: string; text: string }>;
};

export type PracticeSessionPayload = {
    id: number;
    section_type: SkillType;
    mode: string;
    total_questions: number;
    answered_count: number;
    correct_count: number;
    progress_percent: number;
    finished_at: string | null;
    study_day: { id: number; day_number: number; title: string } | null;
    questions: PracticeQuestion[];
};
