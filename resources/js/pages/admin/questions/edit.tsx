import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { QuestionForm } from './form';
import type { QuestionFormData, QuestionFormOptions } from './form';

type AdminQuestion = Partial<QuestionFormData> & {
    id: number;
    options: QuestionFormData['options'];
};

export default function AdminQuestionsEdit({
    question,
    options,
}: {
    question: AdminQuestion;
    options: QuestionFormOptions;
}) {
    return (
        <>
            <Head title="Edit Question" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <Link
                        href={`/admin/questions/${question.id}`}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                    >
                        <ArrowLeft className="size-4" />
                        Question preview
                    </Link>
                    <p className="mt-5 text-sm font-semibold text-indigo-600">
                        Question CMS
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        Edit question
                    </h1>
                </section>

                <QuestionForm
                    mode="edit"
                    action={`/admin/questions/${question.id}`}
                    options={options}
                    initialValues={{
                        section_type: question.section_type ?? 'reading',
                        question_type: question.question_type ?? '',
                        difficulty: question.difficulty ?? '',
                        status: question.status ?? 'draft',
                        passage_id: question.passage_id
                            ? String(question.passage_id)
                            : '',
                        audio_asset_id: question.audio_asset_id
                            ? String(question.audio_asset_id)
                            : '',
                        skill_tag_id: question.skill_tag_id
                            ? String(question.skill_tag_id)
                            : '',
                        question_text: question.question_text ?? '',
                        explanation: question.explanation ?? '',
                        evidence_sentence: question.evidence_sentence ?? '',
                        options: question.options,
                    }}
                    cancelHref={`/admin/questions/${question.id}`}
                />
            </div>
        </>
    );
}
