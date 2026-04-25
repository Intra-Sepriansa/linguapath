<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Lesson;
use App\Models\Passage;
use App\Models\Question;
use App\Models\SkillTag;
use App\Models\SpeakingPrompt;
use App\Models\StudyDay;
use App\Models\StudyPath;
use App\Models\Vocabulary;
use App\Models\WritingPrompt;
use App\Services\ReadingContentImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LinguaPathSeeder extends Seeder
{
    public function run(): void
    {
        $path = StudyPath::query()->updateOrCreate(
            ['title' => 'TOEFL ITP 60-Day Path'],
            [
                'description' => 'A focused daily plan for Listening, Structure, and Reading.',
                'duration_days' => 60,
                'level' => 'basic',
                'is_active' => true,
            ]
        );

        foreach ($this->days() as $dayNumber => $title) {
            $skill = $this->skillForDay($dayNumber);

            $studyDay = StudyDay::query()->updateOrCreate(
                ['study_path_id' => $path->id, 'day_number' => $dayNumber],
                [
                    'title' => $title,
                    'focus_skill' => $skill,
                    'objective' => $this->objectiveFor($title, $skill),
                    'estimated_minutes' => $dayNumber % 7 === 0 ? 120 : 90,
                ]
            );

            $lesson = Lesson::query()->updateOrCreate(
                ['study_day_id' => $studyDay->id],
                [
                    'title' => $title,
                    'summary' => $this->summaryFor($title, $skill),
                    'content' => $this->lessonContentFor($title, $skill),
                    'skill_type' => $skill,
                    'difficulty' => $dayNumber <= 28 ? 'beginner' : 'intermediate',
                ]
            );

            $this->seedQuestions($lesson, $dayNumber, $skill);
        }

        foreach ($this->vocabularies() as $vocabulary) {
            Vocabulary::query()->updateOrCreate(['word' => $vocabulary['word']], $vocabulary);
        }

        $this->seedAdvancedLearningAssets();

        app(ReadingContentImporter::class)->importDirectory(database_path('content/reading'));
    }

    /**
     * @return array<int, string>
     */
    private function days(): array
    {
        return [
            1 => 'Subject + Verb',
            2 => 'To be',
            3 => 'Simple Present',
            4 => 'Subject Verb Agreement',
            5 => 'Simple Past',
            6 => 'Future Tense',
            7 => 'Week 1 Review',
            8 => 'Missing Subject',
            9 => 'Missing Verb',
            10 => 'Double Verb',
            11 => 'Gerund & Infinitive',
            12 => 'Parallel Structure',
            13 => 'Preposition',
            14 => 'Week 2 Review',
            15 => 'Listening Strategy',
            16 => 'Short Conversation',
            17 => 'Listening Synonym',
            18 => 'Common Expression',
            19 => 'Long Conversation',
            20 => 'Talks and Lectures',
            21 => 'Listening Review',
            22 => 'Reading Main Idea',
            23 => 'Detail Question',
            24 => 'Vocabulary in Context',
            25 => 'Reference Question',
            26 => 'Inference Question',
            27 => 'Reading Speed',
            28 => 'Month 1 Mini Test',
            29 => 'Passive Voice',
            30 => 'Adjective Clause',
            31 => 'Noun Clause',
            32 => 'Adverb Clause',
            33 => 'Comparison',
            34 => 'Conditional Sentence',
            35 => 'Grammar Review',
            36 => 'Listening Part A',
            37 => 'Listening Synonym Drill',
            38 => 'Listening Part B',
            39 => 'Listening Part C',
            40 => 'Dictation',
            41 => 'Listening Mini Simulation',
            42 => 'Listening Review',
            43 => 'Reading Main Idea Drill',
            44 => 'Reading Detail Drill',
            45 => 'Reading Vocabulary Drill',
            46 => 'Reference & Inference',
            47 => 'Reading Timer Practice',
            48 => 'Half Simulation',
            49 => 'Simulation Review',
            50 => 'Full Simulation 1',
            51 => 'Review Simulation 1',
            52 => 'Structure Repair',
            53 => 'Listening Repair',
            54 => 'Reading Repair',
            55 => 'Full Simulation 2',
            56 => 'Review Simulation 2',
            57 => 'Mixed Drill',
            58 => 'Final Grammar Review',
            59 => 'Light Practice',
            60 => 'Final Exam Day',
        ];
    }

    private function skillForDay(int $dayNumber): SkillType
    {
        return match (true) {
            $dayNumber <= 14 => SkillType::Structure,
            $dayNumber <= 21 => SkillType::Listening,
            $dayNumber <= 28 => SkillType::Reading,
            $dayNumber <= 35 => SkillType::Structure,
            $dayNumber <= 42 => SkillType::Listening,
            $dayNumber <= 49 => SkillType::Reading,
            in_array($dayNumber, [52, 58], true) => SkillType::Structure,
            $dayNumber === 53 => SkillType::Listening,
            $dayNumber === 54 => SkillType::Reading,
            default => SkillType::Mixed,
        };
    }

    private function objectiveFor(string $title, SkillType $skill): string
    {
        return match ($skill) {
            SkillType::Listening => "Understand TOEFL listening cues for {$title}.",
            SkillType::Reading => "Answer TOEFL reading questions focused on {$title}.",
            SkillType::Structure => "Apply the grammar pattern for {$title} accurately.",
            SkillType::Vocabulary => 'Use academic vocabulary in short TOEFL-style examples.',
            SkillType::Mixed => 'Combine Listening, Structure, and Reading under timed practice.',
        };
    }

    private function summaryFor(string $title, SkillType $skill): string
    {
        return match ($skill) {
            SkillType::Listening => "{$title} trains you to catch meaning, not isolated words.",
            SkillType::Reading => "{$title} helps you scan for evidence before choosing an answer.",
            SkillType::Structure => "{$title} builds sentence accuracy for the Structure section.",
            SkillType::Vocabulary => "{$title} strengthens recall through short examples.",
            SkillType::Mixed => "{$title} keeps the full TOEFL ITP rhythm active.",
        };
    }

    private function patternFor(SkillType $skill): string
    {
        return match ($skill) {
            SkillType::Listening => 'Listen for intent, synonym, and final response.',
            SkillType::Reading => 'Question keyword -> passage evidence -> answer.',
            SkillType::Structure => 'Subject + Verb + Complete Thought.',
            SkillType::Vocabulary => 'Word -> meaning -> example -> recall.',
            SkillType::Mixed => 'Practice, review, repeat.',
        };
    }

    /**
     * @return array{
     *     goal: string,
     *     concept: string,
     *     coach_note: string,
     *     pattern: string,
     *     guided_steps: array<int, string>,
     *     examples: array<int, array{label: string, incorrect?: string, correct: string, why: string}>,
     *     advanced_notes: array<int, string>,
     *     common_traps: array<int, string>,
     *     tasks: array<int, string>,
     *     checklist: array<int, string>,
     *     practice_items?: array<int, array<string, mixed>>
     * }|null
     */
    private function grammarFoundationLessonContentFor(string $title): ?array
    {
        $content = [
            'Subject + Verb' => [
                'goal' => 'Pahami inti kalimat bahasa Inggris: setiap independent clause harus punya subject yang jelas dan main verb yang valid.',
                'concept' => 'Di TOEFL Structure, banyak pilihan salah terlihat panjang dan akademik, tetapi gagal karena inti kalimatnya hilang. Intinya: cari siapa/apa yang dibicarakan, lalu cari aksi atau keadaan utamanya.',
                'coach_note' => 'Jangan tertipu frasa tambahan. Setelah prepositional phrase, adjective phrase, atau appositive dibuang sementara, kalimat tetap harus punya subject dan verb.',
                'pattern' => 'SUBJECT + MAIN VERB + COMPLETE THOUGHT',
                'guided_steps' => [
                    'Cari noun phrase utama yang melakukan aksi atau memiliki keadaan.',
                    'Cari verb yang benar-benar terkonjugasi: is, are, was, were, studies, developed, has improved, dan sejenisnya.',
                    'Abaikan dulu frasa pembuka seperti in the library, during the lecture, atau with several assistants karena frasa itu bukan inti kalimat.',
                ],
                'examples' => [
                    [
                        'label' => 'Subject Ada, Verb Hilang',
                        'incorrect' => 'The researcher in the laboratory every morning.',
                        'correct' => 'The researcher works in the laboratory every morning.',
                        'why' => 'The researcher adalah subject. Frasa in the laboratory bukan verb, jadi works dibutuhkan sebagai main verb.',
                    ],
                    [
                        'label' => 'Frasa Panjang Mengecoh',
                        'incorrect' => 'The results of the recent survey about campus transportation important.',
                        'correct' => 'The results of the recent survey about campus transportation are important.',
                        'why' => 'Subject utamanya results, bukan survey atau transportation. Karena results plural, verb yang tepat adalah are.',
                    ],
                ],
                'advanced_notes' => [
                    'Gerund seperti studying bisa menjadi subject jika berfungsi sebagai noun: Studying regularly improves accuracy.',
                    'A noun phrase after a preposition tidak boleh dianggap subject utama untuk agreement.',
                    'Kalimat TOEFL sering menyisipkan modifier panjang di antara subject dan verb untuk menguji apakah kamu masih melihat inti kalimat.',
                ],
                'common_traps' => [
                    'Mengira noun terakhir sebelum titik kosong sebagai subject.',
                    'Menganggap prepositional phrase sebagai verb karena posisinya setelah subject.',
                    'Memilih verb -ing tanpa auxiliary, padahal studying sendiri bukan main verb untuk pola simple sentence.',
                ],
                'tasks' => [
                    'Tulis ulang setiap contoh menjadi hanya subject dan main verb.',
                    'Lingkari semua preposition lalu abaikan frasanya saat mengecek inti kalimat.',
                    'Sebutkan apakah kalimat butuh verb aksi, be, atau auxiliary sebelum membuka mini-test.',
                ],
                'checklist' => [
                    'Setiap independent clause punya subject.',
                    'Setiap subject punya main verb yang terkonjugasi.',
                    'Frasa tambahan tidak menggantikan main verb.',
                    'Subject yang dipakai untuk agreement adalah head noun, bukan noun terdekat.',
                ],
            ],
            'To be' => [
                'goal' => 'Gunakan be sebagai main verb dan auxiliary dengan benar: am, is, are, was, were, be, being, dan been.',
                'concept' => 'Be bisa menghubungkan subject dengan identitas, keadaan, lokasi, atau adjective. Be juga menjadi auxiliary dalam continuous dan passive. Di TOEFL, kesalahan umum muncul ketika be hilang, berlebihan, atau salah bentuk.',
                'coach_note' => 'Tanyakan dulu fungsi be: apakah be menjadi main verb, membentuk progressive, membentuk passive, atau tidak dibutuhkan sama sekali.',
                'pattern' => 'SUBJECT + BE + COMPLEMENT / BE + V-ING / BE + V3',
                'guided_steps' => [
                    'Jika setelah subject ada adjective, noun, atau place phrase, biasanya butuh be sebagai main verb.',
                    'Jika setelah be ada verb-ing, maknanya progressive: aksi sedang berlangsung.',
                    'Jika setelah be ada past participle, maknanya passive: subject menerima aksi.',
                ],
                'examples' => [
                    [
                        'label' => 'Be Sebagai Main Verb',
                        'incorrect' => 'The campus library quiet during exam week.',
                        'correct' => 'The campus library is quiet during exam week.',
                        'why' => 'Quiet adalah adjective, jadi subject membutuhkan be untuk menghubungkan subject dengan adjective.',
                    ],
                    [
                        'label' => 'Be Tidak Selalu Dipakai',
                        'incorrect' => 'The professor is explains the assignment clearly.',
                        'correct' => 'The professor explains the assignment clearly.',
                        'why' => 'Explains sudah menjadi main verb simple present. Be tidak boleh ditambahkan sebelum base/s-form verb.',
                    ],
                ],
                'advanced_notes' => [
                    'There is/there are mengikuti noun setelahnya: there is a reason, there are several reasons.',
                    'Passive membutuhkan be + V3, bukan be + base verb.',
                    'Continuous membutuhkan be + V-ing, bukan be + simple verb.',
                ],
                'common_traps' => [
                    'Menambahkan is sebelum verb simple present.',
                    'Memakai is untuk plural subject karena noun terdekat singular.',
                    'Mengira been bisa berdiri sendiri tanpa auxiliary have/has/had.',
                ],
                'tasks' => [
                    'Tandai apakah be pada contoh berfungsi sebagai main verb, progressive, atau passive.',
                    'Ubah tiga kalimat simple present menjadi passive jika memungkinkan.',
                    'Latih there is/there are dengan noun singular dan plural sebelum mini-test.',
                ],
                'checklist' => [
                    'Be cocok dengan subject dan tense.',
                    'Be + V-ing berarti progressive.',
                    'Be + V3 berarti passive.',
                    'Jangan tambahkan be sebelum verb yang sudah menjadi main verb.',
                ],
            ],
            'Simple Present' => [
                'goal' => 'Kuasai simple present untuk fakta umum, kebiasaan, instruksi, dan pola akademik yang sering muncul di Structure.',
                'concept' => 'Simple present terlihat mudah, tetapi TOEFL sering menguji -s/-es, do/does, adverb of frequency, dan fakta akademik. Fokusnya bukan hanya hafalan, tetapi mengenali fungsi waktunya.',
                'coach_note' => 'Jika subject orang ketiga tunggal memakai verb aksi, tambahkan -s atau -es. Jika ada does, verb utama kembali ke base form.',
                'pattern' => 'I/YOU/WE/THEY + BASE VERB / HE/SHE/IT + VERB-S',
                'guided_steps' => [
                    'Tentukan subject: singular third person atau bukan.',
                    'Cari penanda kebiasaan/fakta seperti often, usually, every year, generally, atau scientific facts.',
                    'Jika kalimat negatif atau pertanyaan memakai does, jangan beri -s pada verb utama.',
                ],
                'examples' => [
                    [
                        'label' => 'Third Person Singular',
                        'incorrect' => 'The climate affect agricultural planning.',
                        'correct' => 'The climate affects agricultural planning.',
                        'why' => 'Climate singular, sehingga verb simple present membutuhkan -s: affects.',
                    ],
                    [
                        'label' => 'Does Mengambil -s',
                        'incorrect' => 'The experiment does not requires expensive equipment.',
                        'correct' => 'The experiment does not require expensive equipment.',
                        'why' => 'Does sudah membawa tanda third-person singular. Verb utama setelah does kembali ke base form.',
                    ],
                ],
                'advanced_notes' => [
                    'Present simple sering dipakai dalam teks akademik untuk menyatakan fakta umum dan hasil penelitian yang dianggap stabil.',
                    'Adverb seperti always dan often biasanya diletakkan sebelum main verb, tetapi setelah be.',
                    'Verb have berubah menjadi has untuk subject third person singular.',
                ],
                'common_traps' => [
                    'Melupakan -s karena subject terlalu jauh dari verb.',
                    'Memakai studies setelah does.',
                    'Mencampur present simple dengan present continuous ketika kalimat menyatakan fakta umum.',
                ],
                'tasks' => [
                    'Kelompokkan contoh menjadi fakta umum, kebiasaan, atau instruksi.',
                    'Latih lima subject berbeda dengan verb yang sama.',
                    'Perbaiki kalimat negatif dengan do/does sebelum mini-test.',
                ],
                'checklist' => [
                    'Third person singular memakai verb-s atau verb-es.',
                    'Do/does diikuti base verb.',
                    'Present simple cocok untuk fakta dan kebiasaan.',
                    'Adverb of frequency tidak mengubah agreement.',
                ],
            ],
            'Subject Verb Agreement' => [
                'goal' => 'Cocokkan subject dan verb meskipun ada phrase panjang, compound subject, indefinite pronoun, atau there is/there are.',
                'concept' => 'Agreement bukan mencari noun terdekat. Agreement berarti verb mengikuti head subject. TOEFL sering menaruh of-phrase, relative clause, atau appositive untuk membuat kamu memilih verb yang salah.',
                'coach_note' => 'Potong kalimat menjadi subject inti dan verb. Frasa seperti of the students, with the documents, dan including the charts tidak mengubah jumlah subject.',
                'pattern' => 'HEAD SUBJECT -> AGREES WITH -> MAIN VERB',
                'guided_steps' => [
                    'Temukan head noun subject, bukan modifier.',
                    'Coret sementara frasa with, of, including, as well as, dan together with.',
                    'Pilih singular/plural verb berdasarkan subject inti dan aturan compound subject.',
                ],
                'examples' => [
                    [
                        'label' => 'Of-Phrase',
                        'incorrect' => 'The list of research topics are long.',
                        'correct' => 'The list of research topics is long.',
                        'why' => 'Subject inti adalah list, singular. Of research topics hanya modifier, jadi verb harus is.',
                    ],
                    [
                        'label' => 'Compound Subject',
                        'incorrect' => 'The instructor and the assistant prepares the materials.',
                        'correct' => 'The instructor and the assistant prepare the materials.',
                        'why' => 'Dua subject yang dihubungkan and biasanya plural, jadi verb memakai prepare.',
                    ],
                ],
                'advanced_notes' => [
                    'Each, every, either, neither, someone, and everyone biasanya singular.',
                    'Pada or/nor, verb mengikuti noun yang paling dekat dengan verb.',
                    'There is/there are mengikuti noun setelah be, bukan kata there.',
                ],
                'common_traps' => [
                    'Mengikuti noun dalam of-phrase.',
                    'Menganggap as well as sama dengan and.',
                    'Memakai are setelah there hanya karena kalimat terasa panjang.',
                ],
                'tasks' => [
                    'Garisbawahi head noun dan coret modifier.',
                    'Tulis S atau P di atas subject sebelum memilih verb.',
                    'Latih pola and, or/nor, each/every, dan there is/are sebelum mini-test.',
                ],
                'checklist' => [
                    'Verb mengikuti head subject.',
                    'Prepositional phrase tidak mengubah jumlah subject.',
                    'And biasanya plural; or/nor mengikuti noun terdekat.',
                    'Indefinite pronoun seperti everyone dan each biasanya singular.',
                ],
            ],
            'Simple Past' => [
                'goal' => 'Gunakan simple past untuk kejadian selesai pada waktu lampau dan bedakan regular, irregular, serta did + base verb.',
                'concept' => 'Simple past dipakai saat waktu lampau jelas atau konteksnya selesai. TOEFL menguji bentuk V2, penggunaan did, dan konsistensi tense dalam kalimat akademik.',
                'coach_note' => 'Jika ada did/did not, verb utama harus base form. Jika tidak ada auxiliary, main verb harus bentuk past.',
                'pattern' => 'SUBJECT + V2 / DID + SUBJECT + BASE VERB',
                'guided_steps' => [
                    'Cari time signal: yesterday, last year, in 1998, during the experiment, atau when the study began.',
                    'Tentukan apakah verb butuh V2 regular/irregular atau base form setelah did.',
                    'Pastikan semua verb utama dalam rangkaian kejadian lampau konsisten.',
                ],
                'examples' => [
                    [
                        'label' => 'Irregular Verb',
                        'incorrect' => 'The researchers find a pattern in the data last month.',
                        'correct' => 'The researchers found a pattern in the data last month.',
                        'why' => 'Last month menunjukkan waktu lampau selesai, jadi find berubah menjadi found.',
                    ],
                    [
                        'label' => 'Did + Base Verb',
                        'incorrect' => 'The committee did not approved the proposal.',
                        'correct' => 'The committee did not approve the proposal.',
                        'why' => 'Did sudah menandai past tense, sehingga verb utama harus approve.',
                    ],
                ],
                'advanced_notes' => [
                    'Past simple sering dipakai untuk metode penelitian yang selesai dilakukan.',
                    'Be di past tense menjadi was atau were, bukan did be.',
                    'Past simple berbeda dari present perfect karena fokusnya waktu lampau yang selesai.',
                ],
                'common_traps' => [
                    'Memakai V2 setelah did.',
                    'Memakai present tense meskipun ada past time signal.',
                    'Salah memilih was/were karena noun terdekat mengecoh.',
                ],
                'tasks' => [
                    'Tandai semua time signal dalam contoh.',
                    'Buat daftar irregular verb yang muncul di soal hari ini.',
                    'Ubah kalimat positif menjadi negatif dengan did not sebelum mini-test.',
                ],
                'checklist' => [
                    'Past time signal biasanya membutuhkan simple past.',
                    'Did diikuti base verb.',
                    'Was untuk singular; were untuk plural dan you.',
                    'Irregular verbs harus dihafal sebagai bentuk V2.',
                ],
            ],
            'Future Tense' => [
                'goal' => 'Bedakan will, be going to, present continuous for future, dan present simple setelah time connector.',
                'concept' => 'Future dalam bahasa Inggris tidak hanya will. TOEFL sering menguji pola modal + base verb, be going to + base verb, dan larangan memakai will setelah when, before, after, as soon as, atau if dalam dependent clause.',
                'coach_note' => 'Will dan modal lain selalu diikuti base verb. Be going to membutuhkan bentuk be yang cocok dengan subject.',
                'pattern' => 'WILL + BASE VERB / BE GOING TO + BASE VERB',
                'guided_steps' => [
                    'Tentukan apakah kalimat menyatakan prediksi, keputusan, rencana, jadwal, atau dependent time clause.',
                    'Jika memakai will, cek bahwa verb setelahnya base form.',
                    'Jika ada when/before/after/as soon as/if untuk masa depan, gunakan present simple di clause tersebut.',
                ],
                'examples' => [
                    [
                        'label' => 'Modal + Base Verb',
                        'incorrect' => 'The students will submits the final report tomorrow.',
                        'correct' => 'The students will submit the final report tomorrow.',
                        'why' => 'Will selalu diikuti base verb, jadi submit tidak boleh memakai -s.',
                    ],
                    [
                        'label' => 'Time Clause',
                        'incorrect' => 'The advisor will review the draft when she will receive it.',
                        'correct' => 'The advisor will review the draft when she receives it.',
                        'why' => 'Dalam future time clause setelah when, gunakan present simple: receives.',
                    ],
                ],
                'advanced_notes' => [
                    'Be going to sering menekankan rencana atau bukti yang sudah terlihat sekarang.',
                    'Present continuous bisa dipakai untuk rencana yang sudah terjadwal: The team is meeting tomorrow.',
                    'Be about to + base verb berarti sesuatu akan terjadi sangat segera.',
                ],
                'common_traps' => [
                    'Menambahkan -s setelah will.',
                    'Memakai will dua kali dalam main clause dan time clause.',
                    'Lupa bentuk be dalam be going to.',
                ],
                'tasks' => [
                    'Klasifikasikan contoh menjadi prediction, plan, schedule, atau time clause.',
                    'Perbaiki semua verb setelah modal menjadi base form.',
                    'Latih pola when/as soon as + present simple sebelum mini-test.',
                ],
                'checklist' => [
                    'Will diikuti base verb.',
                    'Be going to membutuhkan am/is/are/was/were.',
                    'Future time clause memakai present simple.',
                    'Pilih future form berdasarkan makna, bukan satu rumus untuk semua kalimat.',
                ],
            ],
            'Week 1 Review' => [
                'goal' => 'Gabungkan inti minggu pertama: subject, main verb, be, present, agreement, past, dan future dalam satu strategi Structure.',
                'concept' => 'Review minggu pertama bukan mengulang hafalan. Tujuannya membangun urutan berpikir: temukan inti kalimat, tentukan tense, cek agreement, lalu baru pilih bentuk kata.',
                'coach_note' => 'Kalau kamu bingung, kembali ke tiga pertanyaan: subject-nya apa, verb utamanya apa, dan tense-nya apa.',
                'pattern' => 'CORE SENTENCE -> TENSE -> AGREEMENT -> WORD FORM',
                'guided_steps' => [
                    'Identifikasi subject dan main verb untuk setiap clause.',
                    'Tentukan tense dari time signal dan konteks.',
                    'Cek agreement, lalu pastikan tidak ada auxiliary yang dobel atau hilang.',
                ],
                'examples' => [
                    [
                        'label' => 'Review Agreement + Present',
                        'incorrect' => 'The quality of the samples vary from region to region.',
                        'correct' => 'The quality of the samples varies from region to region.',
                        'why' => 'Subject inti adalah quality, singular. Of the samples tidak mengubah agreement.',
                    ],
                    [
                        'label' => 'Review Future Clause',
                        'incorrect' => 'The class will begin after the instructor will arrive.',
                        'correct' => 'The class will begin after the instructor arrives.',
                        'why' => 'After memperkenalkan future time clause, jadi verb di clause itu memakai present simple.',
                    ],
                ],
                'advanced_notes' => [
                    'Soal review sering menggabungkan dua jebakan sekaligus, misalnya agreement dan tense.',
                    'Pilihan jawaban yang paling panjang belum tentu benar jika merusak inti kalimat.',
                    'Error recognition biasanya meminta satu bagian yang harus diubah, bukan menulis ulang seluruh kalimat.',
                ],
                'common_traps' => [
                    'Langsung memilih opsi berdasarkan arti tanpa mengecek struktur.',
                    'Mengabaikan auxiliary seperti did, will, has, atau be.',
                    'Tidak melihat bahwa frasa panjang hanya modifier.',
                ],
                'tasks' => [
                    'Buat diagnosis singkat untuk setiap contoh: core, tense, agreement.',
                    'Kerjakan ulang contoh hari 1-6 tanpa melihat catatan.',
                    'Masuk mini-test hanya setelah kamu bisa menjelaskan jebakan tiap opsi.',
                ],
                'checklist' => [
                    'Subject dan verb selalu ditemukan lebih dulu.',
                    'Tense dipilih dari waktu dan konteks.',
                    'Agreement mengikuti head subject.',
                    'Auxiliary menentukan bentuk verb setelahnya.',
                ],
            ],
            'Missing Subject' => [
                'goal' => 'Kenali kalimat yang kehilangan subject dan bedakan subject asli dari modifier, prepositional phrase, dan dependent clause.',
                'concept' => 'Missing subject muncul ketika kalimat punya verb, tetapi tidak punya pelaku/hal utama yang melakukan aksi. TOEFL sering memberi pembuka panjang sehingga kamu merasa kalimat sudah lengkap.',
                'coach_note' => 'Jika kamu menemukan main verb tetapi tidak ada noun phrase sebelum atau sesudahnya yang bisa menjadi subject, kalimat perlu subject.',
                'pattern' => 'SUBJECT + VERB, NOT MODIFIER + VERB ONLY',
                'guided_steps' => [
                    'Cari main verb yang sudah terkonjugasi.',
                    'Tanyakan siapa atau apa yang melakukan verb tersebut.',
                    'Jangan hitung prepositional phrase atau dependent clause sebagai subject utama.',
                ],
                'examples' => [
                    [
                        'label' => 'Prepositional Phrase Bukan Subject',
                        'incorrect' => 'During the seminar discussed several research methods.',
                        'correct' => 'During the seminar, the speaker discussed several research methods.',
                        'why' => 'During the seminar adalah prepositional phrase. Verb discussed membutuhkan subject seperti the speaker.',
                    ],
                    [
                        'label' => 'Dependent Clause Butuh Main Subject',
                        'incorrect' => 'Although was difficult, the assignment improved student accuracy.',
                        'correct' => 'Although it was difficult, the assignment improved student accuracy.',
                        'why' => 'Was membutuhkan subject di dalam although-clause. It melengkapi clause tersebut.',
                    ],
                ],
                'advanced_notes' => [
                    'There bisa menjadi dummy subject dalam there is/there are, tetapi agreement tetap mengikuti noun setelah be.',
                    'It sering dipakai sebagai dummy subject untuk cuaca, waktu, jarak, dan evaluasi umum.',
                    'Participial phrase di awal kalimat biasanya menjelaskan subject main clause, bukan menjadi subject sendiri.',
                ],
                'common_traps' => [
                    'Mengira during, after, before, atau in membuat subject.',
                    'Memilih connector padahal kalimat butuh noun phrase subject.',
                    'Melewatkan subject kosong setelah although, because, atau when.',
                ],
                'tasks' => [
                    'Tandai semua verb, lalu tanyakan subject untuk masing-masing verb.',
                    'Ubah tiga prepositional phrase pembuka menjadi kalimat lengkap.',
                    'Latih penggunaan it dan there sebagai dummy subject sebelum mini-test.',
                ],
                'checklist' => [
                    'Verb terkonjugasi membutuhkan subject.',
                    'Prepositional phrase bukan subject.',
                    'Dependent clause tetap membutuhkan subject dan verb sendiri.',
                    'Dummy subject it/there boleh dipakai jika strukturnya tepat.',
                ],
            ],
            'Missing Verb' => [
                'goal' => 'Kenali kalimat yang punya subject tetapi kehilangan main verb, terutama setelah noun phrase panjang atau reduced modifier.',
                'concept' => 'Missing verb terjadi saat kalimat hanya punya subject dan modifier. TOEFL menaruh noun phrase akademik yang panjang sehingga kamu merasa ada aksi, padahal belum ada verb utama.',
                'coach_note' => 'Cari verb yang bisa menjadi predikat utama. Verb-ing, to + verb, dan past participle tidak selalu bisa berdiri sendiri sebagai main verb.',
                'pattern' => 'SUBJECT + FINITE VERB + COMPLEMENT',
                'guided_steps' => [
                    'Temukan subject inti.',
                    'Periksa apakah ada finite verb: is, are, has, develops, improved, dan sejenisnya.',
                    'Jika hanya ada V-ing, infinitive, atau participle, tambahkan auxiliary atau main verb yang tepat.',
                ],
                'examples' => [
                    [
                        'label' => 'Noun Phrase Panjang',
                        'incorrect' => 'The analysis of climate data from several coastal cities.',
                        'correct' => 'The analysis of climate data from several coastal cities reveals a pattern.',
                        'why' => 'Subject panjang itu belum punya verb. Reveals melengkapi predikat kalimat.',
                    ],
                    [
                        'label' => 'Verb-ing Tanpa Auxiliary',
                        'incorrect' => 'The students preparing for the exam in the library.',
                        'correct' => 'The students are preparing for the exam in the library.',
                        'why' => 'Preparing butuh auxiliary are untuk menjadi progressive verb.',
                    ],
                ],
                'advanced_notes' => [
                    'Past participle bisa menjadi adjective, tetapi kalimat tetap butuh main verb.',
                    'Infinitive phrase seperti to improve accuracy bisa menjelaskan tujuan, bukan main verb.',
                    'Main verb harus sesuai tense dan agreement, bukan sekadar kata kerja apa pun.',
                ],
                'common_traps' => [
                    'Menganggap V-ing sebagai main verb tanpa be.',
                    'Menganggap to + verb sebagai main verb.',
                    'Tidak menyadari bahwa noun phrase panjang belum menjadi kalimat.',
                ],
                'tasks' => [
                    'Pisahkan subject, modifier, dan calon verb pada contoh.',
                    'Ubah V-ing menjadi progressive dengan be jika maknanya sedang berlangsung.',
                    'Tambahkan main verb yang logis untuk noun phrase panjang sebelum mini-test.',
                ],
                'checklist' => [
                    'Setiap subject membutuhkan finite verb.',
                    'V-ing butuh be jika menjadi progressive.',
                    'To + verb biasanya bukan main verb.',
                    'Past participle butuh be/have atau berfungsi sebagai modifier.',
                ],
            ],
            'Double Verb' => [
                'goal' => 'Hindari dua main verb dalam satu clause tanpa connector, relative pronoun, atau struktur verb phrase yang benar.',
                'concept' => 'Double verb terjadi ketika satu subject diikuti dua verb terkonjugasi secara langsung. Bahasa Inggris tidak mengizinkan dua finite verbs dalam satu clause tanpa hubungan grammar yang jelas.',
                'coach_note' => 'Jika ada dua verb utama, kamu perlu connector, relative clause, infinitive/gerund, atau menghapus salah satunya.',
                'pattern' => 'ONE CLAUSE = ONE FINITE VERB',
                'guided_steps' => [
                    'Hitung finite verb dalam satu clause.',
                    'Jika ada dua finite verb, cari connector yang menghubungkannya.',
                    'Ubah verb kedua menjadi infinitive, gerund, participle, atau clause terpisah jika diperlukan.',
                ],
                'examples' => [
                    [
                        'label' => 'Two Main Verbs',
                        'incorrect' => 'The professor explained described the procedure.',
                        'correct' => 'The professor explained the procedure.',
                        'why' => 'Explained dan described sama-sama finite verb. Satu clause hanya membutuhkan satu main verb.',
                    ],
                    [
                        'label' => 'Butuh Relative Clause',
                        'incorrect' => 'The device measures records temperature changes.',
                        'correct' => 'The device measures and records temperature changes.',
                        'why' => 'Jika dua aksi sejajar, connector and membuat dua verb berbagi subject yang sama.',
                    ],
                ],
                'advanced_notes' => [
                    'Auxiliary + main verb bukan double verb jika polanya benar: has completed, is studying, will review.',
                    'Causative verbs punya pola khusus seperti make + object + base verb.',
                    'Reduced relative clause bisa membuat participle muncul setelah noun tanpa menjadi main verb.',
                ],
                'common_traps' => [
                    'Menghapus connector and ketika dua verb sebenarnya sejajar.',
                    'Mengira has completed adalah double verb, padahal has adalah auxiliary.',
                    'Menaruh is sebelum verb simple present sehingga muncul dua predikat.',
                ],
                'tasks' => [
                    'Hitung finite verb pada setiap clause.',
                    'Bedakan auxiliary + main verb dari dua main verb.',
                    'Latih memperbaiki double verb dengan connector, infinitive, atau deletion sebelum mini-test.',
                ],
                'checklist' => [
                    'Satu clause hanya punya satu finite verb utama.',
                    'Dua verb sejajar butuh connector.',
                    'Auxiliary tidak dihitung sebagai main verb kedua.',
                    'Verb kedua bisa diubah bentuknya agar strukturnya benar.',
                ],
            ],
            'Gerund & Infinitive' => [
                'goal' => 'Gunakan gerund dan infinitive sesuai fungsi: subject/object, tujuan, complement, dan pola verb tertentu.',
                'concept' => 'Gerund adalah verb-ing yang berfungsi seperti noun. Infinitive adalah to + base verb. TOEFL sering menguji bentuk setelah verb tertentu, preposition, adjective, dan expression of purpose.',
                'coach_note' => 'Setelah preposition, pakai gerund. Setelah banyak adjective atau verb seperti decide, plan, hope, gunakan infinitive.',
                'pattern' => 'PREPOSITION + GERUND / VERB + TO-INFINITIVE',
                'guided_steps' => [
                    'Tentukan fungsi kosong: subject, object, purpose, atau complement.',
                    'Cek kata sebelum kosong: preposition biasanya meminta gerund.',
                    'Hafalkan verb pattern umum: enjoy/avoid/suggest + gerund; decide/hope/plan + infinitive.',
                ],
                'examples' => [
                    [
                        'label' => 'After Preposition',
                        'incorrect' => 'The students are interested in to join the research team.',
                        'correct' => 'The students are interested in joining the research team.',
                        'why' => 'In adalah preposition, jadi bentuk setelahnya harus gerund: joining.',
                    ],
                    [
                        'label' => 'Purpose',
                        'incorrect' => 'The class met early for review the assignment.',
                        'correct' => 'The class met early to review the assignment.',
                        'why' => 'Untuk menyatakan tujuan, gunakan infinitive: to review.',
                    ],
                ],
                'advanced_notes' => [
                    'Some verbs can take both forms with different meaning: remember to do vs remember doing.',
                    'Gerund as subject biasanya singular: Studying daily improves accuracy.',
                    'Infinitive of purpose lebih formal dan ringkas daripada for + verb base.',
                ],
                'common_traps' => [
                    'Memakai to + verb setelah preposition.',
                    'Memakai gerund setelah decide, hope, atau plan.',
                    'Menganggap semua -ing sebagai progressive verb.',
                ],
                'tasks' => [
                    'Buat tabel verb yang diikuti gerund dan infinitive.',
                    'Tandai semua preposition lalu ubah verb setelahnya menjadi gerund.',
                    'Latih purpose phrase dengan to + base verb sebelum mini-test.',
                ],
                'checklist' => [
                    'Preposition diikuti gerund.',
                    'Purpose sering memakai to + base verb.',
                    'Verb pattern harus dipelajari sebagai pasangan.',
                    'Gerund bisa menjadi subject atau object seperti noun.',
                ],
            ],
            'Parallel Structure' => [
                'goal' => 'Gunakan bentuk grammar yang sejajar dalam daftar, perbandingan, dan ide yang terhubung agar kalimat jelas dan seimbang.',
                'concept' => 'Parallel structure sering muncul di TOEFL karena tes ini ingin melihat apakah kamu bisa menjaga bentuk ide tetap sama. Intinya: kalau item pertama memakai gerund, item berikutnya juga gerund. Kalau item pertama noun, item berikutnya juga noun.',
                'coach_note' => 'Saat kalimat membuat daftar atau membandingkan beberapa hal, semua bagian yang punya fungsi sama harus memakai bentuk grammar yang sama.',
                'pattern' => 'NOUN + NOUN + NOUN / GERUND + GERUND + GERUND',
                'guided_steps' => [
                    'Cari sinyal penghubung: and, but, or, both/and, either/or, neither/nor, not only/but also, more than, less than, atau as much as.',
                    'Garisbawahi setiap item yang dihubungkan oleh sinyal itu, lalu beri label bentuknya: noun, adjective, infinitive, gerund, verb phrase, atau clause.',
                    'Perbaiki bagian yang tidak sejajar, lalu baca ulang seluruh kalimat untuk memastikan artinya tetap logis.',
                ],
                'examples' => [
                    [
                        'label' => 'Bentuk Daftar',
                        'incorrect' => 'She likes to run, swimming, and hiking.',
                        'correct' => 'She likes running, swimming, and hiking.',
                        'why' => 'Tiga aktivitas memakai bentuk gerund: running, swimming, dan hiking. Karena bentuknya sama, kalimat menjadi sejajar.',
                    ],
                    [
                        'label' => 'Correlative Conjunction',
                        'incorrect' => 'The lecture was not only informative but also inspired the class.',
                        'correct' => 'The lecture was not only informative but also inspiring.',
                        'why' => 'Not only dan but also menghubungkan dua bentuk adjective: informative dan inspiring.',
                    ],
                ],
                'advanced_notes' => [
                    'Di TOEFL Structure, pilihan yang salah sering mencampur infinitive dengan gerund, atau adjective dengan verb phrase.',
                    'Pada comparison, dua sisi yang dibandingkan harus punya fungsi grammar yang sama. Jangan membandingkan noun dengan clause atau adjective dengan verb phrase.',
                    'Parallelism juga membantu Writing dan Speaking karena kalimat yang seimbang lebih mudah dipahami ketika waktu terbatas.',
                ],
                'common_traps' => [
                    'Memilih jawaban yang terdengar formal, tetapi merusak pola daftar.',
                    'Mengabaikan pasangan not only/but also, either/or, atau neither/nor.',
                    'Menganggap semua kata -ing sebagai verb, padahal bisa berfungsi sebagai gerund.',
                ],
                'tasks' => [
                    'Identifikasi setiap connector dalam kalimat.',
                    'Labeli bentuk grammar pada kedua sisi connector.',
                    'Tulis ulang kalimat dengan bentuk yang sejajar sebelum mulai mini-test.',
                ],
                'checklist' => [
                    'Cek daftar yang dihubungkan oleh coordinating conjunction.',
                    'Pastikan correlative conjunction membingkai bentuk yang sejajar.',
                    'Pastikan elemen yang dibandingkan punya bentuk grammar yang sama.',
                    'Baca ulang kalimat yang sudah diperbaiki untuk menangkap pola yang masih janggal.',
                ],
            ],
            'Preposition' => [
                'goal' => 'Pahami preposition sebagai penghubung hubungan tempat, waktu, arah, sebab, dan pasangan kata akademik.',
                'concept' => 'Preposition bukan sekadar arti kata seperti di/ke/dari. Dalam TOEFL, preposition sering diuji sebagai collocation: responsible for, interested in, effect on, reason for, increase in, dan caused by.',
                'coach_note' => 'Setelah preposition, gunakan noun phrase, pronoun, atau gerund. Jangan memakai subject + verb lengkap langsung setelah preposition.',
                'pattern' => 'PREPOSITION + NOUN PHRASE / GERUND',
                'guided_steps' => [
                    'Tentukan fungsi preposition: time, place, direction, cause, agent, atau collocation.',
                    'Cek kata setelah preposition: harus noun/pronoun/gerund, bukan finite clause.',
                    'Hafalkan pasangan akademik yang sering muncul dalam teks: effect on, cause of, increase in, solution to.',
                ],
                'examples' => [
                    [
                        'label' => 'Preposition + Gerund',
                        'incorrect' => 'The committee focused on to improve student access.',
                        'correct' => 'The committee focused on improving student access.',
                        'why' => 'On adalah preposition, jadi bentuk setelahnya adalah gerund: improving.',
                    ],
                    [
                        'label' => 'Academic Collocation',
                        'incorrect' => 'The report discussed the effect in air pollution on health.',
                        'correct' => 'The report discussed the effect of air pollution on health.',
                        'why' => 'Effect memakai of untuk sumber/penyebab dan on untuk dampak terhadap sesuatu.',
                    ],
                ],
                'advanced_notes' => [
                    'By sering menandai agent dalam passive: was caused by storms.',
                    'During diikuti noun phrase, sedangkan while diikuti clause.',
                    'Despite dan in spite of diikuti noun phrase/gerund, bukan full clause biasa.',
                ],
                'common_traps' => [
                    'Memakai to + verb setelah preposition selain to sebagai infinitive marker.',
                    'Mencampur during dan while.',
                    'Memilih preposition berdasarkan terjemahan Indonesia satu kata.',
                ],
                'tasks' => [
                    'Buat daftar collocation preposition dari contoh hari ini.',
                    'Ubah verb setelah preposition menjadi gerund.',
                    'Latih membedakan during + noun dan while + clause sebelum mini-test.',
                ],
                'checklist' => [
                    'Preposition diikuti noun phrase, pronoun, atau gerund.',
                    'Collocation harus dipelajari sebagai pasangan kata.',
                    'During tidak sama dengan while.',
                    'Passive agent sering memakai by.',
                ],
            ],
            'Week 2 Review' => [
                'goal' => 'Satukan skill minggu kedua: missing subject, missing verb, double verb, gerund/infinitive, parallelism, dan preposition.',
                'concept' => 'Review minggu kedua berfokus pada diagnosis cepat. Kamu harus bisa melihat apakah masalahnya kekurangan komponen, kelebihan verb, salah verb pattern, tidak sejajar, atau salah preposition.',
                'coach_note' => 'Jangan memperbaiki kata per kata. Diagnosis dulu jenis error-nya, lalu pilih perbaikan yang paling kecil tetapi benar.',
                'pattern' => 'DIAGNOSE ERROR -> APPLY RULE -> VERIFY MEANING',
                'guided_steps' => [
                    'Tentukan apakah kalimat kehilangan subject atau verb.',
                    'Hitung finite verb untuk menemukan double verb.',
                    'Cek connector, preposition, gerund/infinitive, dan parallelism sebelum memilih jawaban akhir.',
                ],
                'examples' => [
                    [
                        'label' => 'Mixed Structure',
                        'incorrect' => 'After reviewing the notes was easier to answer the questions.',
                        'correct' => 'After reviewing the notes, it was easier to answer the questions.',
                        'why' => 'After reviewing the notes adalah phrase pembuka. Main clause membutuhkan subject it sebelum was.',
                    ],
                    [
                        'label' => 'Pattern + Preposition',
                        'incorrect' => 'The students succeeded to complete the project by work together.',
                        'correct' => 'The students succeeded in completing the project by working together.',
                        'why' => 'Succeed memakai in + gerund, dan by sebagai preposition juga diikuti gerund.',
                    ],
                ],
                'advanced_notes' => [
                    'Soal campuran biasanya punya satu error utama, tetapi beberapa opsi tampak benar jika tidak melihat fungsi grammar.',
                    'Gerund, participle, dan progressive terlihat sama-sama -ing, tetapi fungsinya berbeda.',
                    'Preposition dan parallelism sering diuji bersama dalam Written Expression.',
                ],
                'common_traps' => [
                    'Memperbaiki arti tetapi merusak struktur.',
                    'Menghitung auxiliary sebagai double verb.',
                    'Mengabaikan preposition kecil seperti by, of, for, in, dan to.',
                ],
                'tasks' => [
                    'Diagnosis setiap contoh dengan label: missing subject, missing verb, double verb, gerund/infinitive, parallel, atau preposition.',
                    'Tulis alasan satu kalimat untuk setiap jawaban benar.',
                    'Kerjakan mini-test sebagai simulasi akhir minggu kedua.',
                ],
                'checklist' => [
                    'Kalimat lengkap punya subject dan finite verb.',
                    'Satu clause tidak boleh punya dua main verb tanpa penghubung.',
                    'Verb pattern dan preposition menentukan bentuk kata setelahnya.',
                    'Parallel structure menjaga item setara tetap dalam bentuk yang sama.',
                ],
            ],
        ][$title] ?? null;

        if ($content === null) {
            return null;
        }

        return [
            ...$content,
            'practice_items' => $this->grammarFoundationPracticeItemsFor($title),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function grammarFoundationPracticeItemsFor(string $title): array
    {
        return [
            'Subject + Verb' => [
                $this->choicePractice(
                    'Pilih kalimat yang sudah punya subject dan main verb lengkap.',
                    [
                        'The report about coastal cities.',
                        'The report about coastal cities explains the risk clearly.',
                        'Explaining the report about coastal cities.',
                    ],
                    'The report about coastal cities explains the risk clearly.',
                    'Kalimat benar punya subject inti the report dan main verb explains. About coastal cities hanya modifier, bukan verb.',
                ),
                $this->rewritePractice(
                    'Tulis ulang menjadi kalimat lengkap.',
                    'The results of the survey important.',
                    'The results of the survey are important.',
                    'Subject intinya results, plural. Karena complement-nya adjective important, kalimat butuh be plural: are.',
                ),
            ],
            'To be' => [
                $this->choicePractice(
                    'Pilih penggunaan be yang paling tepat.',
                    [
                        'The examples are clear enough for beginners.',
                        'The examples is clear enough for beginners.',
                        'The examples be clear enough for beginners.',
                    ],
                    'The examples are clear enough for beginners.',
                    'Examples plural, jadi be present yang tepat adalah are. Be menghubungkan subject dengan adjective clear.',
                ),
                $this->rewritePractice(
                    'Perbaiki kalimat yang memakai be secara berlebihan.',
                    'The teacher is explains the pattern slowly.',
                    'The teacher explains the pattern slowly.',
                    'Explains sudah menjadi main verb simple present. Is harus dihapus, kecuali bentuknya diubah menjadi is explaining.',
                ),
            ],
            'Simple Present' => [
                $this->choicePractice(
                    'Pilih kalimat simple present yang agreement-nya benar.',
                    [
                        'The platform provide daily grammar practice.',
                        'The platform provides daily grammar practice.',
                        'The platform providing daily grammar practice.',
                    ],
                    'The platform provides daily grammar practice.',
                    'Platform singular dan kalimat menyatakan kebiasaan/fungsi umum, jadi verb simple present membutuhkan -s: provides.',
                ),
                $this->rewritePractice(
                    'Perbaiki kalimat setelah does not.',
                    'The lesson does not includes every topic at once.',
                    'The lesson does not include every topic at once.',
                    'Does sudah membawa tanda third-person singular, sehingga verb utama harus kembali ke base form: include.',
                ),
            ],
            'Subject Verb Agreement' => [
                $this->choicePractice(
                    'Pilih kalimat yang agreement-nya benar.',
                    [
                        'The list of examples are useful.',
                        'The list of examples is useful.',
                        'The examples in the list is useful.',
                    ],
                    'The list of examples is useful.',
                    'Head subject-nya list, singular. Of examples tidak mengubah jumlah subject, jadi verb yang tepat adalah is.',
                ),
                $this->rewritePractice(
                    'Perbaiki agreement pada kalimat ini.',
                    'Every learner in the sessions receive feedback.',
                    'Every learner in the sessions receives feedback.',
                    'Every learner singular. Frasa in the sessions hanya modifier, jadi verb harus receives.',
                ),
            ],
            'Simple Past' => [
                $this->choicePractice(
                    'Pilih kalimat simple past yang benar.',
                    [
                        'The researchers found a pattern last month.',
                        'The researchers find a pattern last month.',
                        'The researchers finding a pattern last month.',
                    ],
                    'The researchers found a pattern last month.',
                    'Last month menunjukkan waktu lampau selesai. Find berubah menjadi found.',
                ),
                $this->rewritePractice(
                    'Perbaiki verb setelah did not.',
                    'The committee did not approved the proposal.',
                    'The committee did not approve the proposal.',
                    'Did not sudah menandai past tense, jadi verb utama harus base form: approve.',
                ),
            ],
            'Future Tense' => [
                $this->choicePractice(
                    'Pilih kalimat future yang bentuk verb-nya benar.',
                    [
                        'The class will begins after the break.',
                        'The class will begin after the break.',
                        'The class will beginning after the break.',
                    ],
                    'The class will begin after the break.',
                    'Will selalu diikuti base verb. Karena itu begin benar, bukan begins atau beginning.',
                ),
                $this->rewritePractice(
                    'Perbaiki future time clause.',
                    'The tutor will review the answer when she will receive the file.',
                    'The tutor will review the answer when she receives the file.',
                    'Dalam future time clause setelah when, gunakan present simple: receives. Will cukup muncul di main clause.',
                ),
            ],
            'Week 1 Review' => [
                $this->choicePractice(
                    'Pilih kalimat review yang paling akurat.',
                    [
                        'The quality of the notes improves every week.',
                        'The quality of the notes improve every week.',
                        'The quality of the notes improving every week.',
                    ],
                    'The quality of the notes improves every week.',
                    'Subject inti adalah quality, singular. Every week menunjukkan simple present, jadi improves.',
                ),
                $this->rewritePractice(
                    'Perbaiki gabungan future dan time clause.',
                    'The practice will start after the timer will reset.',
                    'The practice will start after the timer resets.',
                    'After memperkenalkan future time clause. Clause itu memakai present simple, bukan will.',
                ),
            ],
            'Missing Subject' => [
                $this->choicePractice(
                    'Pilih kalimat yang subject-nya lengkap.',
                    [
                        'During the review explained the rule.',
                        'During the review, the mentor explained the rule.',
                        'During the review explaining the rule.',
                    ],
                    'During the review, the mentor explained the rule.',
                    'During the review adalah prepositional phrase. Main verb explained membutuhkan subject: the mentor.',
                ),
                $this->rewritePractice(
                    'Tambahkan subject yang hilang.',
                    'Although was difficult, the exercise helped.',
                    'Although it was difficult, the exercise helped.',
                    'Was membutuhkan subject di dalam although-clause. It menjadi dummy subject yang melengkapi clause.',
                ),
            ],
            'Missing Verb' => [
                $this->choicePractice(
                    'Pilih kalimat yang punya finite verb.',
                    [
                        'The explanation of the answer includes a clear reason.',
                        'The explanation of the answer including a clear reason.',
                        'The explanation of the answer to include a clear reason.',
                    ],
                    'The explanation of the answer includes a clear reason.',
                    'Subject explanation membutuhkan finite verb. Includes adalah main verb yang sesuai dengan subject singular.',
                ),
                $this->rewritePractice(
                    'Tambahkan verb utama yang hilang.',
                    'The students preparing for the mini-test.',
                    'The students are preparing for the mini-test.',
                    'Preparing membutuhkan auxiliary be agar menjadi progressive verb. Karena students plural, gunakan are.',
                ),
            ],
            'Double Verb' => [
                $this->choicePractice(
                    'Pilih kalimat yang tidak memiliki double verb salah.',
                    [
                        'The app helps learners track progress.',
                        'The app is helps learners track progress.',
                        'The app helps tracks learner progress.',
                    ],
                    'The app helps learners track progress.',
                    'Helps adalah main verb. Track adalah base verb setelah object learners dalam pola help + object + base verb.',
                ),
                $this->rewritePractice(
                    'Hilangkan double verb yang salah.',
                    'The teacher is explains the pattern.',
                    'The teacher explains the pattern.',
                    'Is explains berisi dua predikat yang tidak cocok. Gunakan explains untuk simple present.',
                ),
            ],
            'Gerund & Infinitive' => [
                $this->choicePractice(
                    'Pilih bentuk setelah preposition yang benar.',
                    [
                        'The learners are interested in reviewing mistakes.',
                        'The learners are interested in to review mistakes.',
                        'The learners are interested in review mistakes.',
                    ],
                    'The learners are interested in reviewing mistakes.',
                    'In adalah preposition, sehingga verb setelahnya harus menjadi gerund: reviewing.',
                ),
                $this->rewritePractice(
                    'Perbaiki verb pattern setelah plan.',
                    'The students plan improving their accuracy.',
                    'The students plan to improve their accuracy.',
                    'Plan diikuti to-infinitive. Bentuk yang tepat adalah plan to improve.',
                ),
            ],
            'Parallel Structure' => [
                $this->choicePractice(
                    'Pilih kalimat yang bentuk daftarnya sejajar.',
                    [
                        'The lesson teaches reading, analyzing, and correcting sentences.',
                        'The lesson teaches reading, to analyze, and correcting sentences.',
                        'The lesson teaches reading, analysis, and correcting sentences.',
                    ],
                    'The lesson teaches reading, analyzing, and correcting sentences.',
                    'Tiga item dalam daftar memakai bentuk gerund: reading, analyzing, correcting. Itu membuat struktur sejajar.',
                ),
                $this->rewritePractice(
                    'Buat daftar ini sejajar.',
                    'Students should identify the subject, checking the verb, and confirm the answer.',
                    'Students should identify the subject, check the verb, and confirm the answer.',
                    'Setelah should, semua verb harus base form dan sejajar: identify, check, confirm.',
                ),
            ],
            'Preposition' => [
                $this->choicePractice(
                    'Pilih preposition collocation yang benar.',
                    [
                        'The team focused on reducing repeated errors.',
                        'The team focused in reducing repeated errors.',
                        'The team focused to reducing repeated errors.',
                    ],
                    'The team focused on reducing repeated errors.',
                    'Focus on adalah collocation. Setelah on, gunakan gerund: reducing.',
                ),
                $this->rewritePractice(
                    'Perbaiki preposition dan bentuk setelahnya.',
                    'The learners succeeded to improve by review old mistakes.',
                    'The learners succeeded in improving by reviewing old mistakes.',
                    'Succeed memakai in + gerund. By juga preposition, jadi diikuti gerund: reviewing.',
                ),
            ],
            'Week 2 Review' => [
                $this->choicePractice(
                    'Pilih kalimat yang paling benar secara struktur.',
                    [
                        'After reviewing the notes, it was easier to answer the questions.',
                        'After reviewing the notes was easier to answer the questions.',
                        'After to review the notes, it was easier answer the questions.',
                    ],
                    'After reviewing the notes, it was easier to answer the questions.',
                    'After reviewing the notes adalah phrase pembuka. Main clause membutuhkan subject it dan be was.',
                ),
                $this->rewritePractice(
                    'Perbaiki pola campuran gerund, preposition, dan parallelism.',
                    'The checklist helps learners finding errors, correct them, and to explain the rule.',
                    'The checklist helps learners find errors, correct them, and explain the rule.',
                    'Dalam pola help + object + base verb, semua aksi dibuat sejajar: find, correct, explain.',
                ),
            ],
        ][$title] ?? [];
    }

    /**
     * @param  array<int, string>  $options
     * @return array<string, mixed>
     */
    private function choicePractice(string $prompt, array $options, string $correctAnswer, string $explanation): array
    {
        return [
            'type' => 'choice',
            'prompt' => $prompt,
            'instruction' => 'Baca semua pilihan, cari inti kalimat, lalu pilih satu jawaban yang paling benar.',
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'accepted_answers' => [$correctAnswer],
            'explanation' => $explanation,
            'success_message' => 'Bagus. Kamu sudah melihat inti grammar-nya, bukan hanya menebak dari bunyi kalimat.',
            'retry_message' => 'Belum tepat, tapi ini bagian penting dari proses belajar. Cek lagi subject, verb, dan kata sebelum bagian yang diuji.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rewritePractice(string $prompt, string $source, string $correctAnswer, string $explanation): array
    {
        return [
            'type' => 'rewrite',
            'prompt' => $prompt,
            'instruction' => 'Ketik ulang kalimat yang benar. Fokus pada perubahan grammar yang diminta, bukan mengganti makna.',
            'source' => $source,
            'correct_answer' => $correctAnswer,
            'accepted_answers' => [$correctAnswer],
            'explanation' => $explanation,
            'success_message' => 'Mantap. Kamu bukan cuma memilih jawaban, tapi sudah bisa membangun ulang kalimat yang benar.',
            'retry_message' => 'Masih belum pas. Tidak apa-apa: baca ulang pola hari ini, lalu samakan bentuk subject, verb, atau preposition-nya.',
        ];
    }

    /**
     * @return array{
     *     goal: string,
     *     concept: string,
     *     coach_note: string,
     *     pattern: string,
     *     guided_steps: array<int, string>,
     *     examples: array<int, array{label: string, incorrect?: string, correct: string, why: string}>,
     *     advanced_notes: array<int, string>,
     *     common_traps: array<int, string>,
     *     tasks: array<int, string>,
     *     checklist: array<int, string>,
     *     practice_items?: array<int, array<string, mixed>>
     * }
     */
    private function lessonContentFor(string $title, SkillType $skill): array
    {
        if ($grammarContent = $this->grammarFoundationLessonContentFor($title)) {
            return $grammarContent;
        }

        return match ($skill) {
            SkillType::Listening => $this->listeningLessonContentFor($title),
            SkillType::Reading => $this->readingLessonContentFor($title),
            SkillType::Mixed => $this->mixedLessonContentFor($title),
            SkillType::Vocabulary => $this->vocabularyLessonContentFor($title),
            SkillType::Structure => $this->structureLessonContentFor($title),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listeningLessonContentFor(string $title): array
    {
        return [
            'goal' => "Kuasai {$title} dengan cara mendengar maksud, nada, dan hubungan ide, bukan hanya mengejar kata yang terdengar sama.",
            'concept' => "Pada {$title}, jawaban benar biasanya berupa paraphrase. Artinya, opsi yang benar menyampaikan makna yang sama dengan transcript, tetapi memakai kata berbeda. Untuk pemula, urutannya sederhana: pahami situasi, tangkap maksud pembicara terakhir, lalu cocokkan dengan opsi yang paling menjaga makna.",
            'coach_note' => 'Dengarkan seperti sedang memahami orang berbicara, bukan seperti sedang mencatat setiap kata. Kata kunci penting, tetapi sikap pembicara dan konteks kalimat sering lebih menentukan.',
            'pattern' => 'CONTEXT -> SPEAKER INTENT -> PARAPHRASE -> TRAP CHECK',
            'guided_steps' => [
                'Tentukan situasinya dulu: siapa yang bicara, masalahnya apa, dan respons terakhir mengarah ke setuju, menolak, ragu, atau menyarankan sesuatu.',
                'Ucapkan ulang maknanya dengan bahasa sendiri dalam satu kalimat pendek sebelum melihat opsi jawaban.',
                'Eliminasi opsi yang hanya mengulang kata dari transcript tetapi mengubah maksud, waktu, atau sikap pembicara.',
            ],
            'examples' => [
                [
                    'label' => 'Paraphrase Meaning',
                    'incorrect' => 'Transcript: "I can hardly keep up." Pilihan jebakan: The speaker is keeping up easily.',
                    'correct' => 'The speaker is having difficulty following.',
                    'why' => 'Hardly berarti hampir tidak. Jadi makna kalimatnya adalah kesulitan mengikuti, bukan mengikuti dengan mudah.',
                ],
                [
                    'label' => 'Suggestion Signal',
                    'incorrect' => 'Transcript: "Why do not we review the notes first?" Pilihan jebakan: They already finished the review.',
                    'correct' => 'The speaker suggests reviewing the notes before continuing.',
                    'why' => 'Why do not we sering dipakai untuk memberi saran. Jawaban benar harus menangkap fungsi saran, bukan menganggap review sudah selesai.',
                ],
            ],
            'advanced_notes' => [
                'TOEFL Listening sering memakai negative expression seperti hardly, barely, not really, dan I wish untuk membalik makna permukaan.',
                'Pada long conversation, pertanyaan biasanya muncul dari masalah, solusi, alasan, atau rencana berikutnya. Catat hubungan ide itu secara mental.',
                'Pada talks dan lectures, transisi seperti however, for example, as a result, dan the main point is memberi sinyal bagian penting.',
            ],
            'common_traps' => [
                'Memilih opsi karena ada kata yang sama persis dengan transcript.',
                'Mengabaikan nada ragu, kecewa, setuju, atau saran halus dari pembicara.',
                'Melewatkan kata negatif kecil sehingga arti jawaban menjadi kebalikan.',
            ],
            'tasks' => [
                'Tulis satu paraphrase dari respons utama sebelum memilih jawaban.',
                'Tandai kata sinyal seperti but, actually, hardly, probably, dan I suggest.',
                'Setelah menjawab, bandingkan opsi benar dengan transcript dan jelaskan bagian mana yang diparaphrase.',
            ],
            'checklist' => [
                'Saya tahu situasi percakapannya.',
                'Saya tahu maksud pembicara terakhir.',
                'Saya memilih opsi karena maknanya sama, bukan karena katanya sama.',
                'Saya sudah mengecek kata negatif, saran, dan perubahan nada.',
            ],
            'practice_items' => $this->listeningPracticeItemsFor($title),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readingLessonContentFor(string $title): array
    {
        return [
            'goal' => "Kuasai {$title} dengan memilih jawaban berdasarkan bukti passage, bukan berdasarkan tebakan atau pengetahuan umum.",
            'concept' => "Reading TOEFL selalu kembali ke bukti. Untuk {$title}, kamu perlu mengubah pertanyaan menjadi target yang bisa dicari, menemukan kalimat bukti, lalu memilih opsi yang paling setia pada makna passage. Jawaban yang terdengar benar secara umum tetap salah kalau tidak didukung teks.",
            'coach_note' => 'Urutan aman untuk pemula: baca pertanyaan, cari keyword, temukan bukti, baru baca opsi dengan kritis.',
            'pattern' => 'QUESTION TYPE -> KEYWORD -> EVIDENCE -> ANSWER',
            'guided_steps' => [
                'Klasifikasikan jenis pertanyaan: main idea, detail, vocabulary, reference, inference, atau purpose.',
                'Cari keyword atau sinonimnya di passage, lalu baca satu kalimat sebelum dan sesudah bukti.',
                'Pilih opsi yang menjaga makna teks tanpa menambah informasi baru, tanpa kata ekstrem, dan tanpa mengubah scope.',
            ],
            'examples' => [
                [
                    'label' => 'Detail Evidence',
                    'incorrect' => 'Passage menyebut review helps students notice repeated errors. Pilihan jebakan: Review guarantees a perfect score.',
                    'correct' => 'Review helps students identify repeated errors.',
                    'why' => 'Opsi benar tetap dekat dengan bukti. Kata guarantees a perfect score terlalu ekstrem dan tidak disebutkan passage.',
                ],
                [
                    'label' => 'Main Idea Scope',
                    'incorrect' => 'Satu paragraf membahas daily plan, review mistakes, dan next action. Pilihan jebakan: The paragraph is only about dictionaries.',
                    'correct' => 'The paragraph explains how a study plan improves learning.',
                    'why' => 'Main idea harus mencakup keseluruhan paragraf, bukan mengambil detail kecil atau topik yang tidak dominan.',
                ],
            ],
            'advanced_notes' => [
                'Main idea harus cukup luas untuk mencakup paragraf, tetapi tidak terlalu luas sampai keluar dari passage.',
                'Vocabulary in context tidak selalu memakai arti kamus pertama; pilih arti yang cocok dengan kalimat sekitar.',
                'Inference tetap harus punya bukti. Inference bukan menebak bebas, tetapi menyimpulkan hal yang logis dari teks.',
            ],
            'common_traps' => [
                'Memilih opsi yang benar secara umum, tetapi tidak muncul di passage.',
                'Tertipu kata ekstrem seperti always, never, completely, only, dan must.',
                'Mengambil satu detail kecil sebagai main idea seluruh paragraf.',
            ],
            'tasks' => [
                'Tuliskan jenis pertanyaan sebelum mencari jawaban.',
                'Garisbawahi kalimat bukti dan sebutkan kata yang mendukung jawaban.',
                'Eliminasi minimal dua opsi salah dengan alasan spesifik.',
            ],
            'checklist' => [
                'Saya menemukan keyword atau sinonimnya.',
                'Saya punya kalimat bukti di passage.',
                'Jawaban saya tidak menambah asumsi luar.',
                'Saya sudah menolak opsi ekstrem atau terlalu sempit.',
            ],
            'practice_items' => $this->readingPracticeItemsFor($title),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function structureLessonContentFor(string $title): array
    {
        $topic = $this->structureTopicFor($title);

        return [
            'goal' => $topic['goal'],
            'concept' => $topic['concept'],
            'coach_note' => $topic['coach_note'],
            'pattern' => $topic['pattern'],
            'guided_steps' => [
                'Temukan subject, finite verb, dan connector sebelum melihat pilihan jawaban secara detail.',
                $topic['diagnosis_step'],
                'Baca ulang kalimat setelah diperbaiki untuk memastikan struktur lengkap, makna tetap sama, dan tidak ada verb ganda.',
            ],
            'examples' => [
                [
                    'label' => $topic['example_label'],
                    'incorrect' => $topic['incorrect'],
                    'correct' => $topic['correct'],
                    'why' => $topic['why'],
                ],
                [
                    'label' => 'Diagnosis TOEFL',
                    'incorrect' => 'Because the experiment was repeated produced consistent results.',
                    'correct' => 'Because the experiment was repeated, it produced consistent results.',
                    'why' => 'Because membuat klausa pertama dependent. Main clause tetap membutuhkan subject dan verb sendiri: it produced.',
                ],
            ],
            'advanced_notes' => [
                $topic['advanced_note'],
                'Pilihan jawaban yang terlihat formal tetap salah kalau membuat clause kehilangan subject, verb, atau connector.',
                'Written Expression sering menguji dua hal sekaligus: bentuk kata dan hubungan antar-clause.',
            ],
            'common_traps' => [
                $topic['trap'],
                'Memilih jawaban berdasarkan arti umum tanpa mengecek grammar inti.',
                'Menganggap phrase panjang sebagai clause lengkap.',
            ],
            'tasks' => [
                'Beri label S untuk subject, V untuk finite verb, dan C untuk connector pada contoh.',
                'Tulis alasan satu kalimat mengapa opsi benar memperbaiki struktur.',
                'Kerjakan mini-test setelah kamu bisa menyebutkan jenis error-nya.',
            ],
            'checklist' => [
                'Subject dan verb utama sudah ditemukan.',
                'Connector tidak membuat kalimat utama hilang.',
                'Bentuk kata sesuai fungsi grammar yang dibutuhkan.',
                'Jawaban benar memperbaiki struktur tanpa mengubah makna utama.',
            ],
            'practice_items' => $this->structurePracticeItemsFor($title, $topic),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mixedLessonContentFor(string $title): array
    {
        return [
            'goal' => "Gunakan {$title} untuk menggabungkan Listening, Structure, dan Reading dalam satu ritme latihan yang terukur.",
            'concept' => 'Mixed practice bukan sekadar mengerjakan banyak soal. Tujuannya adalah mengatur energi, membaca instruksi cepat, memilih strategi sesuai section, lalu mencatat pola salah agar sesi berikutnya lebih tajam.',
            'coach_note' => 'Kerjakan dengan urutan yang disiplin: jawab dulu dengan strategi section, koreksi langsung, lalu tulis satu penyebab salah yang bisa diperbaiki.',
            'pattern' => 'ATTEMPT -> CHECK -> EXPLAIN -> REPAIR',
            'guided_steps' => [
                'Mulai setiap soal dengan mengenali section dan jenis pertanyaannya.',
                'Gunakan strategi khusus: paraphrase untuk Listening, core sentence untuk Structure, evidence untuk Reading.',
                'Setelah koreksi, tulis apakah salahnya karena konsep, terburu-buru, vocabulary, atau salah membaca instruksi.',
            ],
            'examples' => [
                [
                    'label' => 'Mixed Diagnosis',
                    'incorrect' => 'Salah dicatat hanya sebagai "kurang teliti".',
                    'correct' => 'Salah dicatat sebagai: memilih opsi yang mengulang kata transcript, tetapi mengubah maksud pembicara.',
                    'why' => 'Catatan yang spesifik membantu menentukan latihan berikutnya. Kurang teliti terlalu umum dan sulit diperbaiki.',
                ],
                [
                    'label' => 'Repair Plan',
                    'incorrect' => 'Langsung mengulang full simulation tanpa melihat pola salah.',
                    'correct' => 'Review 3 kesalahan terbesar, ulangi konsepnya, lalu kerjakan drill pendek sebelum simulasi lagi.',
                    'why' => 'Simulasi tanpa review sering mengulang kesalahan yang sama. Repair kecil membuat sesi berikutnya lebih efektif.',
                ],
            ],
            'advanced_notes' => [
                'Simulasi penuh berguna setelah dasar kuat; kalau akurasi turun, kembali ke drill fokus.',
                'Kesalahan berulang biasanya lebih penting daripada satu kesalahan acak.',
                'Kecepatan harus mengikuti akurasi. Jangan mengejar waktu kalau alasan jawaban benar belum bisa dijelaskan.',
            ],
            'common_traps' => [
                'Mengukur progres hanya dari jumlah soal, bukan kualitas koreksi.',
                'Mencampur strategi antar-section, misalnya menjawab Reading tanpa bukti passage.',
                'Mengulang simulasi saat tubuh lelah tanpa review yang jelas.',
            ],
            'tasks' => [
                'Kerjakan soal campuran dengan timer ringan.',
                'Catat satu alasan benar atau salah setelah setiap soal.',
                'Pilih satu skill paling lemah untuk drill berikutnya.',
            ],
            'checklist' => [
                'Saya tahu section setiap soal sebelum menjawab.',
                'Saya memakai strategi yang sesuai section.',
                'Saya membaca koreksi sampai bisa menjelaskan alasannya.',
                'Saya punya satu tindakan perbaikan setelah latihan selesai.',
            ],
            'practice_items' => $this->mixedPracticeItemsFor($title),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vocabularyLessonContentFor(string $title): array
    {
        return [
            'goal' => "Bangun vocabulary akademik untuk {$title} dengan memahami makna dari konteks, word family, dan contoh kalimat.",
            'concept' => 'Vocabulary TOEFL tidak cukup dihafal sebagai daftar arti. Kamu perlu melihat fungsi kata dalam kalimat: apakah noun, verb, adjective, atau adverb, lalu memilih arti yang cocok dengan konteks.',
            'coach_note' => 'Kalau tidak tahu arti kata, baca kalimat sebelum dan sesudahnya. Context clue sering memberi arah positif, negatif, sebab, akibat, atau definisi.',
            'pattern' => 'WORD FORM -> CONTEXT CLUE -> MEANING -> NEW SENTENCE',
            'guided_steps' => [
                'Tentukan bentuk kata dari posisinya dalam kalimat.',
                'Cari context clue seperti definition, example, contrast, cause, atau result.',
                'Gunakan kata itu dalam kalimat baru agar maknanya tidak hanya dihafal sementara.',
            ],
            'examples' => [
                [
                    'label' => 'Context Meaning',
                    'incorrect' => 'Assume selalu berarti membuat asumsi.',
                    'correct' => 'Dalam kalimat "The manager assumed responsibility", assume berarti mengambil atau menerima tanggung jawab.',
                    'why' => 'Makna kata mengikuti konteks. Satu kata akademik bisa punya beberapa arti.',
                ],
                [
                    'label' => 'Word Family',
                    'incorrect' => 'The analysis was accuracy.',
                    'correct' => 'The analysis was accurate.',
                    'why' => 'Setelah was dibutuhkan adjective. Accurate adalah adjective, sedangkan accuracy adalah noun.',
                ],
            ],
            'advanced_notes' => [
                'Prefix dan suffix membantu menebak arti, tetapi context tetap penentu akhir.',
                'Word family sering muncul di Structure dan Reading sekaligus.',
                'Synonym trap muncul ketika opsi punya arti mirip tetapi tidak cocok dengan konteks kalimat.',
            ],
            'common_traps' => [
                'Menghafal satu arti dan memaksakannya ke semua kalimat.',
                'Tidak melihat apakah kata yang dibutuhkan noun, verb, adjective, atau adverb.',
                'Memilih sinonim yang benar secara kamus tetapi salah dalam konteks passage.',
            ],
            'tasks' => [
                'Tulis word family dari kata target.',
                'Cari context clue dalam kalimat contoh.',
                'Buat satu kalimat baru yang memakai kata itu dengan benar.',
            ],
            'checklist' => [
                'Saya tahu bentuk kata yang dibutuhkan.',
                'Saya menemukan clue dalam konteks.',
                'Saya memilih arti yang cocok dengan kalimat.',
                'Saya bisa memakai kata itu dalam contoh baru.',
            ],
            'practice_items' => $this->vocabularyPracticeItemsFor($title),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function structureTopicFor(string $title): array
    {
        return [
            'Passive Voice' => [
                'goal' => 'Gunakan passive voice saat subject menerima aksi, terutama dalam kalimat akademik yang fokus pada proses atau hasil.',
                'concept' => 'Passive voice memakai be + past participle. TOEFL sering menguji apakah kamu bisa membedakan subject yang melakukan aksi dan subject yang menerima aksi.',
                'coach_note' => 'Tanyakan: apakah subject melakukan aksi atau menerima aksi? Kalau menerima aksi, cari be yang sesuai tense lalu V3.',
                'pattern' => 'SUBJECT + BE + PAST PARTICIPLE',
                'diagnosis_step' => 'Jika subject menerima aksi, pasangkan be dengan V3 dan jangan memakai base verb setelah be.',
                'example_label' => 'Passive Form',
                'incorrect' => 'The report wrote by the assistant.',
                'correct' => 'The report was written by the assistant.',
                'why' => 'Report menerima aksi. Passive past tense membutuhkan was + written.',
                'advanced_note' => 'Agent by-phrase boleh dihapus jika pelakunya tidak penting atau sudah jelas.',
                'trap' => 'Memakai by tetapi lupa be + V3.',
            ],
            'Adjective Clause' => [
                'goal' => 'Gunakan adjective clause untuk menjelaskan noun tanpa membuat kalimat memiliki dua main verb yang bertabrakan.',
                'concept' => 'Adjective clause dimulai dengan who, which, that, where, atau whose dan berfungsi menjelaskan noun sebelumnya.',
                'coach_note' => 'Relative pronoun menghubungkan modifier dengan noun. Jangan hilangkan connector kalau ada dua verb.',
                'pattern' => 'NOUN + WHO/WHICH/THAT + VERB',
                'diagnosis_step' => 'Cari noun yang dijelaskan dan pastikan relative clause punya connector yang tepat.',
                'example_label' => 'Relative Pronoun',
                'incorrect' => 'The tutor explained the rule helps beginners.',
                'correct' => 'The tutor explained the rule that helps beginners.',
                'why' => 'That mengubah helps beginners menjadi adjective clause yang menjelaskan rule.',
                'advanced_note' => 'Which biasanya untuk benda, who untuk orang, dan whose untuk kepemilikan.',
                'trap' => 'Menghapus relative pronoun sehingga dua finite verb muncul dalam satu clause.',
            ],
            'Noun Clause' => [
                'goal' => 'Gunakan noun clause sebagai subject, object, atau complement dengan word order yang benar.',
                'concept' => 'Noun clause memakai connector seperti what, whether, why, how, dan that. Setelah connector, susunan biasanya subject + verb, bukan question order.',
                'coach_note' => 'Jika noun clause ada di dalam kalimat, ubah pertanyaan menjadi statement order.',
                'pattern' => 'CONNECTOR + SUBJECT + VERB',
                'diagnosis_step' => 'Cek apakah clause berfungsi sebagai noun dan gunakan statement word order.',
                'example_label' => 'Statement Order',
                'incorrect' => 'The instructor explained why was the answer correct.',
                'correct' => 'The instructor explained why the answer was correct.',
                'why' => 'Di dalam noun clause, urutannya the answer was, bukan was the answer.',
                'advanced_note' => 'That boleh memperkenalkan noun clause meskipun sering tidak diterjemahkan langsung.',
                'trap' => 'Memakai question order di dalam noun clause.',
            ],
            'Adverb Clause' => [
                'goal' => 'Gunakan adverb clause untuk menunjukkan waktu, sebab, kontras, kondisi, atau hasil tanpa kehilangan main clause.',
                'concept' => 'Adverb clause dimulai dengan because, although, when, while, if, atau since. Clause ini belum menjadi kalimat lengkap jika berdiri sendiri.',
                'coach_note' => 'Connector seperti because dan although membuat clause menjadi dependent, jadi masih perlu main clause.',
                'pattern' => 'ADVERB CONNECTOR + S + V, MAIN CLAUSE',
                'diagnosis_step' => 'Setelah menemukan adverb connector, pastikan ada main clause yang lengkap.',
                'example_label' => 'Dependent Clause',
                'incorrect' => 'Although the passage was difficult.',
                'correct' => 'Although the passage was difficult, the student found the main idea.',
                'why' => 'Although-clause membutuhkan main clause agar kalimat lengkap.',
                'advanced_note' => 'Future time clause setelah when atau after memakai present simple, bukan will.',
                'trap' => 'Mengira dependent clause sudah menjadi kalimat lengkap.',
            ],
            'Comparison' => [
                'goal' => 'Gunakan comparison untuk membandingkan dua hal dengan bentuk grammar yang jelas dan sejajar.',
                'concept' => 'Comparison memakai -er than, more/less than, as ... as, atau the same as. Dua sisi yang dibandingkan harus logis dan setara.',
                'coach_note' => 'Tentukan dua hal yang dibandingkan, lalu cek adjective/adverb dan struktur setelah than atau as.',
                'pattern' => 'MORE/LESS + ADJECTIVE + THAN / AS + ADJECTIVE + AS',
                'diagnosis_step' => 'Pastikan kedua sisi comparison punya fungsi grammar yang sama.',
                'example_label' => 'Balanced Comparison',
                'incorrect' => 'This passage is more clear than the previous one.',
                'correct' => 'This passage is clearer than the previous one.',
                'why' => 'Clear adalah adjective pendek, jadi bentuk comparative yang umum adalah clearer.',
                'advanced_note' => 'Double comparative seperti more clearer tidak boleh dipakai.',
                'trap' => 'Membandingkan noun dengan clause atau memakai double comparative.',
            ],
            'Conditional Sentence' => [
                'goal' => 'Gunakan conditional sentence untuk menunjukkan kondisi nyata, kemungkinan, atau situasi hipotetis.',
                'concept' => 'Conditional memakai if-clause dan result clause. Tense kedua clause harus cocok dengan jenis kondisi.',
                'coach_note' => 'Jangan hanya menerjemahkan kalau. Tentukan apakah kondisinya real, future possible, atau hypothetical.',
                'pattern' => 'IF + PRESENT, WILL + BASE VERB / IF + PAST, WOULD + BASE VERB',
                'diagnosis_step' => 'Cocokkan tense dalam if-clause dengan result clause.',
                'example_label' => 'Future Condition',
                'incorrect' => 'If the tutor will arrive early, the review will begin soon.',
                'correct' => 'If the tutor arrives early, the review will begin soon.',
                'why' => 'Future condition setelah if memakai present simple: arrives.',
                'advanced_note' => 'Were sering dipakai untuk hypothetical condition: If I were ready, I would take the test.',
                'trap' => 'Memakai will di if-clause untuk kondisi masa depan.',
            ],
        ][$title] ?? [
            'goal' => "Perkuat {$title} dengan diagnosis grammar yang rapi: subject, verb, connector, word form, dan makna.",
            'concept' => "{$title} membantu kamu melihat struktur kalimat seperti peta. Untuk pemula, jangan langsung menebak dari bunyi kalimat; cari inti kalimat dulu, lalu cek bagian yang diuji.",
            'coach_note' => 'Kalimat yang benar harus lengkap secara struktur dan masuk akal secara makna.',
            'pattern' => $this->patternFor(SkillType::Structure),
            'diagnosis_step' => 'Tentukan apakah masalahnya missing subject, missing verb, double verb, agreement, connector, atau word form.',
            'example_label' => 'Sentence Core',
            'incorrect' => 'The students in the library every morning.',
            'correct' => 'The students study in the library every morning.',
            'why' => 'Kalimat awal punya subject, tetapi tidak punya main verb. Study melengkapi predikat kalimat.',
            'advanced_note' => 'Modifier panjang sering membuat subject dan verb tampak jauh, tetapi agreement tetap mengikuti head subject.',
            'trap' => 'Mengira frasa panjang sudah cukup menjadi kalimat lengkap.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listeningPracticeItemsFor(string $title): array
    {
        return [
            $this->choicePractice(
                "Pilih paraphrase yang paling tepat untuk {$title}.",
                [
                    'The speaker cannot attend the review session.',
                    'The speaker already finished the review session.',
                    'The speaker wants to cancel every future session.',
                ],
                'The speaker cannot attend the review session.',
                'Kalimat "I cannot make it to the review session" berarti pembicara tidak bisa hadir. Jawaban benar menjaga makna itu tanpa menambah informasi baru.',
            ),
            $this->rewritePractice(
                'Tulis ulang maksud transcript dengan kalimat sederhana.',
                'Speaker B: I can hardly keep up with the lecture.',
                'The speaker is having difficulty following the lecture.',
                'Hardly berarti hampir tidak. Jadi pembicara kesulitan mengikuti lecture, bukan mengikutinya dengan mudah.',
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readingPracticeItemsFor(string $title): array
    {
        return [
            $this->choicePractice(
                "Pilih jawaban yang paling didukung passage untuk {$title}.",
                [
                    'Review helps learners notice repeated mistakes.',
                    'Review guarantees that learners never make mistakes again.',
                    'Review is unrelated to learning progress.',
                ],
                'Review helps learners notice repeated mistakes.',
                'Jawaban benar didukung langsung oleh ide passage: review membantu melihat kesalahan yang berulang. Opsi guarantees terlalu ekstrem.',
            ),
            $this->rewritePractice(
                'Tulis inference yang masih aman berdasarkan bukti.',
                'Passage: Students who review mistakes can choose the next skill to practice.',
                'Reviewing mistakes helps students decide what to practice next.',
                'Inference ini aman karena tetap mengikuti bukti passage: review kesalahan membantu memilih latihan berikutnya.',
            ),
        ];
    }

    /**
     * @param  array<string, string>  $topic
     * @return array<int, array<string, mixed>>
     */
    private function structurePracticeItemsFor(string $title, array $topic): array
    {
        return [
            $this->choicePractice(
                "Pilih kalimat {$title} yang paling benar.",
                [
                    $topic['correct'],
                    $topic['incorrect'],
                    'Because the answer was correct explained the tutor.',
                ],
                $topic['correct'],
                $topic['why'],
            ),
            $this->rewritePractice(
                'Tulis ulang kalimat yang salah menjadi benar.',
                $topic['incorrect'],
                $topic['correct'],
                $topic['why'],
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mixedPracticeItemsFor(string $title): array
    {
        return [
            $this->choicePractice(
                "Pilih tindakan review terbaik setelah {$title}.",
                [
                    'Record the mistake type, read the explanation, and repeat a focused drill.',
                    'Ignore the explanation and start another full simulation immediately.',
                    'Only count the score without checking why answers were wrong.',
                ],
                'Record the mistake type, read the explanation, and repeat a focused drill.',
                'Mixed practice efektif kalau kesalahan diubah menjadi tindakan perbaikan yang spesifik.',
            ),
            $this->rewritePractice(
                'Tulis catatan review yang spesifik.',
                'I was wrong because I was careless.',
                'I chose an option that repeated transcript words but changed the meaning.',
                'Catatan spesifik membantu kamu tahu apa yang harus dilatih. Careless terlalu umum dan sulit diperbaiki.',
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function vocabularyPracticeItemsFor(string $title): array
    {
        return [
            $this->choicePractice(
                "Pilih word form yang tepat untuk {$title}.",
                [
                    'The explanation was accurate.',
                    'The explanation was accuracy.',
                    'The explanation was accurately the answer.',
                ],
                'The explanation was accurate.',
                'Setelah was dibutuhkan adjective. Accurate adalah adjective, sedangkan accuracy adalah noun.',
            ),
            $this->rewritePractice(
                'Perbaiki word form dalam kalimat.',
                'The tutor explained the concept clear.',
                'The tutor explained the concept clearly.',
                'Explained adalah verb, jadi kata yang menjelaskan cara menjelaskan harus adverb: clearly.',
            ),
        ];
    }

    private function seedQuestions(Lesson $lesson, int $dayNumber, SkillType $skill): void
    {
        if ($grammarQuestions = $this->grammarFoundationQuestionsFor($lesson->title)) {
            $this->removeUnusedLegacyStructureQuestions($lesson, $dayNumber);

            foreach ($grammarQuestions as $data) {
                $this->seedQuestionFromData($lesson, $dayNumber, $data);
            }

            return;
        }

        $this->removeUnusedLegacySkillQuestions($lesson, $dayNumber);

        foreach ($this->practiceQuestionsFor($dayNumber, $skill) as $data) {
            $this->seedQuestionFromData($lesson, $dayNumber, $data);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function practiceQuestionsFor(int $dayNumber, SkillType $skill): array
    {
        if ($skill === SkillType::Mixed) {
            return collect(range(1, 12))
                ->map(function (int $variant) use ($dayNumber): array {
                    $skillVariant = intdiv($variant - 1, 3) + 1;

                    return match (($variant - 1) % 3) {
                        0 => $this->structureQuestion($dayNumber, $skillVariant),
                        1 => $this->listeningQuestion($dayNumber, $skillVariant),
                        default => $this->readingQuestion($dayNumber, $skillVariant),
                    };
                })
                ->all();
        }

        return collect(range(1, 12))
            ->map(fn (int $variant): array => match ($skill) {
                SkillType::Listening => $this->listeningQuestion($dayNumber, $variant),
                SkillType::Reading => $this->readingQuestion($dayNumber, $variant),
                default => $this->structureQuestion($dayNumber, $variant),
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedQuestionFromData(Lesson $lesson, int $dayNumber, array $data): void
    {
        $question = Question::query()->updateOrCreate(
            ['lesson_id' => $lesson->id, 'question_text' => $data['question_text']],
            [
                'section_type' => $data['section_type'],
                'question_type' => $data['question_type'],
                'difficulty' => $dayNumber <= 28 ? 'beginner' : 'intermediate',
                'transcript' => $data['transcript'] ?? null,
                'passage_text' => $data['passage_text'] ?? null,
                'explanation' => $data['explanation'],
                'why_correct' => $data['explanation'],
                'why_wrong' => 'Pilihan yang salah biasanya mengabaikan konteks, pola grammar, atau bukti utama pada soal.',
                'core_sentence' => $data['section_type'] === SkillType::Structure ? 'Cari subject, main verb, connector, dan bentuk kata sebelum memilih jawaban.' : null,
            ]
        );

        foreach ($data['options'] as $option) {
            $question->options()->updateOrCreate(
                ['option_label' => $option['label']],
                ['option_text' => $option['text'], 'is_correct' => $option['correct']]
            );
        }
    }

    private function seedAdvancedLearningAssets(): void
    {
        $tags = collect($this->skillTags())
            ->mapWithKeys(fn (array $tag): array => [
                $tag['code'] => SkillTag::query()->updateOrCreate(['code' => $tag['code']], $tag),
            ]);

        Question::query()
            ->with(['audioAsset', 'passage'])
            ->get()
            ->each(function (Question $question) use ($tags): void {
                $updates = [
                    'skill_tag_id' => $tags[$this->tagCodeForQuestion($question)]?->id ?? null,
                    'exam_eligible' => true,
                    'why_correct' => $question->why_correct ?: $question->explanation,
                    'why_wrong' => $question->why_wrong ?: 'Bandingkan pilihanmu dengan pola dan bukti utama. Distractor biasanya hanya mengambil kata yang terlihat familiar.',
                ];

                if ($question->section_type === SkillType::Listening && $question->transcript) {
                    $audio = AudioAsset::query()->updateOrCreate(
                        ['title' => 'Listening '.md5($question->question_text)],
                        [
                            'transcript' => $question->transcript,
                            'speaker_notes' => 'Browser speech fallback is available until recorded audio is uploaded.',
                            'duration_seconds' => max(18, (int) round(str_word_count($question->transcript) * 0.55)),
                            'accent' => 'american',
                            'speed' => 1.00,
                            'source' => 'seeded-transcript',
                        ]
                    );
                    $updates['audio_asset_id'] = $audio->id;
                }

                if ($question->section_type === SkillType::Reading && $question->passage_text) {
                    $passage = Passage::query()->updateOrCreate(
                        ['title' => 'Reading '.md5($question->passage_text)],
                        [
                            'topic' => 'TOEFL reading strategy',
                            'body' => $question->passage_text,
                            'word_count' => str_word_count($question->passage_text),
                            'difficulty' => $question->difficulty,
                            'source' => 'seeded-passage',
                        ]
                    );
                    $updates['passage_id'] = $passage->id;
                }

                $question->update($updates);
            });

        Vocabulary::query()
            ->get()
            ->each(fn (Vocabulary $vocabulary) => $vocabulary->update([
                'pronunciation' => $vocabulary->pronunciation ?: Str::of($vocabulary->word)->replace(['-', '_', '/'], ' ')->squish()->toString(),
                'frequency_rank' => $vocabulary->frequency_rank ?: $vocabulary->id,
                'synonyms' => $vocabulary->synonyms ?: [],
                'antonyms' => $vocabulary->antonyms ?: [],
                'word_family' => $vocabulary->word_family ?: [$vocabulary->word],
                'collocations' => $vocabulary->collocations ?: [],
            ]));

        foreach ($this->speakingPrompts() as $prompt) {
            SpeakingPrompt::query()->updateOrCreate(['title' => $prompt['title']], $prompt);
        }

        foreach ($this->writingPrompts() as $prompt) {
            WritingPrompt::query()->updateOrCreate(['title' => $prompt['title']], $prompt);
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function skillTags(): array
    {
        return [
            ['code' => 'listening-main-idea', 'name' => 'Listening Main Idea', 'domain' => 'listening', 'description' => 'Understand the central message of a conversation or talk.', 'difficulty' => 'intermediate'],
            ['code' => 'listening-detail', 'name' => 'Listening Detail', 'domain' => 'listening', 'description' => 'Catch factual details and implied references in audio.', 'difficulty' => 'intermediate'],
            ['code' => 'listening-inference', 'name' => 'Listening Inference', 'domain' => 'listening', 'description' => 'Infer speaker intent, attitude, and function.', 'difficulty' => 'upper_intermediate'],
            ['code' => 'structure-sentence-core', 'name' => 'Sentence Core', 'domain' => 'structure', 'description' => 'Identify subject, verb, connector, and complete thought.', 'difficulty' => 'beginner'],
            ['code' => 'structure-error-recognition', 'name' => 'Error Recognition', 'domain' => 'structure', 'description' => 'Find the part that violates standard written English.', 'difficulty' => 'intermediate'],
            ['code' => 'reading-main-idea', 'name' => 'Reading Main Idea', 'domain' => 'reading', 'description' => 'Find the broad claim of a passage.', 'difficulty' => 'intermediate'],
            ['code' => 'reading-evidence', 'name' => 'Reading Evidence', 'domain' => 'reading', 'description' => 'Use passage evidence for detail, reference, and inference questions.', 'difficulty' => 'intermediate'],
            ['code' => 'vocabulary-context', 'name' => 'Vocabulary in Context', 'domain' => 'vocabulary', 'description' => 'Choose meaning based on sentence and paragraph context.', 'difficulty' => 'intermediate'],
            ['code' => 'speaking-fluency', 'name' => 'Speaking Fluency', 'domain' => 'speaking', 'description' => 'Speak clearly within a timed response.', 'difficulty' => 'beginner'],
            ['code' => 'writing-coherence', 'name' => 'Writing Coherence', 'domain' => 'writing', 'description' => 'Build clear sentences and connected paragraphs.', 'difficulty' => 'beginner'],
        ];
    }

    private function tagCodeForQuestion(Question $question): string
    {
        return match ($question->section_type) {
            SkillType::Listening => in_array($question->question_type, [QuestionType::ShortConversation, QuestionType::LongConversation], true)
                ? 'listening-inference'
                : 'listening-main-idea',
            SkillType::Reading => match ($question->question_type) {
                QuestionType::MainIdea, QuestionType::Summary => 'reading-main-idea',
                QuestionType::VocabularyContext => 'vocabulary-context',
                default => 'reading-evidence',
            },
            SkillType::Structure => $question->question_type === QuestionType::ErrorRecognition
                ? 'structure-error-recognition'
                : 'structure-sentence-core',
            default => 'vocabulary-context',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function speakingPrompts(): array
    {
        return [
            [
                'title' => 'Self Introduction Starter',
                'prompt_type' => 'self_introduction',
                'skill_level' => 'beginner',
                'prompt' => 'Introduce yourself. Say your name, your daily activity, and one reason you want to improve your English.',
                'sample_answer' => 'My name is Rina. I am a university student. I want to improve my English because I need it for my TOEFL test and future career.',
                'focus_points' => ['clear opening', 'simple present', 'confidence'],
                'preparation_seconds' => 20,
                'response_seconds' => 60,
                'is_active' => true,
            ],
            [
                'title' => 'Daily Life Roleplay',
                'prompt_type' => 'roleplay',
                'skill_level' => 'elementary',
                'prompt' => 'You are asking a classmate to study TOEFL together this weekend. Make a polite invitation and suggest a time.',
                'sample_answer' => 'Hi, would you like to study TOEFL together this weekend? We can review grammar on Saturday morning and practice listening after lunch.',
                'focus_points' => ['polite invitation', 'future plan', 'natural rhythm'],
                'preparation_seconds' => 20,
                'response_seconds' => 60,
                'is_active' => true,
            ],
            [
                'title' => 'Academic Opinion',
                'prompt_type' => 'opinion',
                'skill_level' => 'intermediate',
                'prompt' => 'Do you think students learn better alone or in groups? Give one reason and one example.',
                'sample_answer' => 'I think students learn better in groups because they can explain ideas to each other. For example, when I do not understand a grammar rule, a friend can show me another example.',
                'focus_points' => ['opinion', 'reason', 'example'],
                'preparation_seconds' => 30,
                'response_seconds' => 90,
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function writingPrompts(): array
    {
        return [
            [
                'title' => 'Sentence Building: Study Habit',
                'prompt_type' => 'sentence_building',
                'skill_level' => 'beginner',
                'prompt' => 'Write five clear sentences about your English study habit. Use simple present tense.',
                'suggested_minutes' => 8,
                'min_words' => 40,
                'rubric' => ['grammar', 'clarity', 'complete sentences'],
                'sample_response' => 'I study English every evening. I review vocabulary for ten minutes. I practice grammar with short questions. I listen to English audio slowly. I write my mistakes in a journal.',
                'is_active' => true,
            ],
            [
                'title' => 'Opinion Paragraph: TOEFL Practice',
                'prompt_type' => 'opinion_paragraph',
                'skill_level' => 'intermediate',
                'prompt' => 'Write one paragraph explaining whether daily TOEFL practice is better than studying only before the exam.',
                'suggested_minutes' => 15,
                'min_words' => 100,
                'rubric' => ['topic sentence', 'reason', 'example', 'conclusion'],
                'sample_response' => 'Daily TOEFL practice is better because it builds consistency. When learners practice every day, they can notice repeated mistakes and fix them earlier. For example, a student who misses subject-verb agreement questions can review that pattern before it becomes a habit. Therefore, short daily practice is more useful than studying only before the exam.',
                'is_active' => true,
            ],
        ];
    }

    private function removeUnusedLegacyStructureQuestions(Lesson $lesson, int $dayNumber): void
    {
        Question::query()
            ->where('lesson_id', $lesson->id)
            ->where('question_text', 'like', "Day {$dayNumber}: The % English every morning.")
            ->each(function (Question $question): void {
                if ($question->practiceAnswers()->exists()) {
                    $question->update(['lesson_id' => null]);

                    return;
                }

                $question->delete();
            });
    }

    private function removeUnusedLegacySkillQuestions(Lesson $lesson, int $dayNumber): void
    {
        Question::query()
            ->where('lesson_id', $lesson->id)
            ->where(function ($query) use ($dayNumber): void {
                $query
                    ->where('question_text', 'like', "Day {$dayNumber}: The % English every morning.")
                    ->orWhere('question_text', "Day {$dayNumber}: What does the speaker mean?")
                    ->orWhere('question_text', "Day {$dayNumber}: What is the passage mainly about?")
                    ->orWhere('question_text', "Day {$dayNumber}: According to the passage, what should students review?");
            })
            ->each(function (Question $question): void {
                if ($question->practiceAnswers()->exists()) {
                    $question->update(['lesson_id' => null]);

                    return;
                }

                $question->delete();
            });
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function grammarFoundationQuestionsFor(?string $title): ?array
    {
        if ($title === null) {
            return null;
        }

        $questions = [
            'Subject + Verb' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 1 Mini-test 1: The committee ___ budget proposals every Friday.',
                    'Subject committee dapat diperlakukan sebagai satu unit dalam American English. Untuk simple present third-person singular, verb yang tepat adalah reviews.',
                    [
                        ['A', 'review', false],
                        ['B', 'reviews', true],
                        ['C', 'reviewing', false],
                        ['D', 'to review', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 1 Mini-test 2: During the lecture, ___ several examples of renewable energy.',
                    'During the lecture hanyalah prepositional phrase pembuka. Kalimat membutuhkan subject dan verb lengkap: the professor gave.',
                    [
                        ['A', 'the professor gave', true],
                        ['B', 'gave the professor', false],
                        ['C', 'the professor giving', false],
                        ['D', 'because the professor gave', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 1 Mini-test 3: The analysis of river samples ___ a clear pattern.',
                    'Subject inti adalah analysis, singular. Of river samples tidak mengubah agreement, jadi verb yang benar adalah reveals.',
                    [
                        ['A', 'reveal', false],
                        ['B', 'revealing', false],
                        ['C', 'reveals', true],
                        ['D', 'to reveal', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 1 Mini-test 4: In the archive ___ several rare manuscripts from the nineteenth century.',
                    'Pola inversi setelah place phrase membutuhkan there are karena several rare manuscripts plural.',
                    [
                        ['A', 'there are', true],
                        ['B', 'there is', false],
                        ['C', 'are there', false],
                        ['D', 'they are', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 1 Mini-test 5: Identify the part that must be changed: The collection of maps from the old library remain valuable to historians.',
                    'Subject inti adalah collection, singular. Verb remain harus diubah menjadi remains.',
                    [
                        ['A', 'The collection', false],
                        ['B', 'from the old library', false],
                        ['C', 'remain', true],
                        ['D', 'to historians', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 1 Mini-test 6: Each chapter in the handbook ___ with a short review.',
                    'Each membuat subject singular, sehingga verb simple present membutuhkan -s: ends.',
                    [
                        ['A', 'end', false],
                        ['B', 'ends', true],
                        ['C', 'ending', false],
                        ['D', 'to end', false],
                    ]
                ),
            ],
            'To be' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 2 Mini-test 1: The instructions for the experiment ___ clear enough for beginners.',
                    'Subject instructions plural, jadi be yang tepat dalam present tense adalah are.',
                    [
                        ['A', 'is', false],
                        ['B', 'are', true],
                        ['C', 'be', false],
                        ['D', 'being', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 2 Mini-test 2: There ___ a detailed explanation at the end of the lesson.',
                    'Noun setelah there adalah a detailed explanation, singular, jadi gunakan there is.',
                    [
                        ['A', 'are', false],
                        ['B', 'is', true],
                        ['C', 'were', false],
                        ['D', 'be', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 2 Mini-test 3: The samples ___ collected by the research team last week.',
                    'Kalimat passive past tense membutuhkan be past plural + V3: were collected.',
                    [
                        ['A', 'were', true],
                        ['B', 'was', false],
                        ['C', 'be', false],
                        ['D', 'being', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 2 Mini-test 4: The tutor ___ explaining the strategy when the class began.',
                    'Past continuous untuk singular subject memakai was + V-ing.',
                    [
                        ['A', 'is', false],
                        ['B', 'was', true],
                        ['C', 'were', false],
                        ['D', 'be', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 2 Mini-test 5: Identify the part that must be changed: The professor is explains the assignment before the practice begins.',
                    'Explains sudah menjadi main verb simple present. Is harus dihapus karena membuat struktur double verb.',
                    [
                        ['A', 'is explains', true],
                        ['B', 'the assignment', false],
                        ['C', 'before', false],
                        ['D', 'begins', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 2 Mini-test 6: The final report has ___ reviewed by two advisors.',
                    'Present perfect passive membutuhkan has been + V3.',
                    [
                        ['A', 'be', false],
                        ['B', 'been', true],
                        ['C', 'being', false],
                        ['D', 'is', false],
                    ]
                ),
            ],
            'Simple Present' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 3 Mini-test 1: Academic writing often ___ clear evidence for each claim.',
                    'Academic writing singular, jadi verb simple present harus uses.',
                    [
                        ['A', 'use', false],
                        ['B', 'uses', true],
                        ['C', 'using', false],
                        ['D', 'used', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 3 Mini-test 2: The students usually ___ the instructions before answering.',
                    'Subject students plural memakai base verb dalam simple present: read.',
                    [
                        ['A', 'read', true],
                        ['B', 'reads', false],
                        ['C', 'reading', false],
                        ['D', 'to read', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 3 Mini-test 3: The online platform does not ___ audio files during grammar practice.',
                    'Setelah does not, verb utama kembali ke base form: include.',
                    [
                        ['A', 'includes', false],
                        ['B', 'included', false],
                        ['C', 'include', true],
                        ['D', 'including', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 3 Mini-test 4: Water ___ at 100 degrees Celsius at sea level.',
                    'Fakta umum memakai simple present. Subject water singular, jadi boils.',
                    [
                        ['A', 'boil', false],
                        ['B', 'boils', true],
                        ['C', 'boiling', false],
                        ['D', 'boiled', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 3 Mini-test 5: Identify the part that must be changed: The guide explain each step before students start the timed drill.',
                    'Subject guide singular, sehingga explain harus menjadi explains.',
                    [
                        ['A', 'The guide', false],
                        ['B', 'explain', true],
                        ['C', 'before', false],
                        ['D', 'start', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 3 Mini-test 6: Before the timer starts, each learner ___ the example carefully.',
                    'Each learner singular, jadi verb simple present harus studies.',
                    [
                        ['A', 'study', false],
                        ['B', 'studies', true],
                        ['C', 'studying', false],
                        ['D', 'to study', false],
                    ]
                ),
            ],
            'Subject Verb Agreement' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 4 Mini-test 1: The list of recommended sources ___ available on the course website.',
                    'Subject inti adalah list, singular. Of recommended sources hanya modifier, jadi is.',
                    [
                        ['A', 'are', false],
                        ['B', 'is', true],
                        ['C', 'were', false],
                        ['D', 'be', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 4 Mini-test 2: Neither the textbook nor the lecture notes ___ enough detail.',
                    'Dengan neither/nor, verb mengikuti subject terdekat. Lecture notes plural, jadi provide.',
                    [
                        ['A', 'provides', false],
                        ['B', 'provide', true],
                        ['C', 'providing', false],
                        ['D', 'has provided', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 4 Mini-test 3: Every participant in the workshops ___ a progress report.',
                    'Every participant singular, jadi receives.',
                    [
                        ['A', 'receive', false],
                        ['B', 'receives', true],
                        ['C', 'receiving', false],
                        ['D', 'have received', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 4 Mini-test 4: The instructor, together with two assistants, ___ the answer key.',
                    'Together with two assistants tidak membuat subject plural. Subject inti instructor singular, jadi prepares.',
                    [
                        ['A', 'prepare', false],
                        ['B', 'prepares', true],
                        ['C', 'are preparing', false],
                        ['D', 'have prepared', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 4 Mini-test 5: Identify the part that must be changed: There is several reasons for reviewing mistakes immediately.',
                    'Several reasons plural, jadi there is harus menjadi there are.',
                    [
                        ['A', 'There is', true],
                        ['B', 'several reasons', false],
                        ['C', 'reviewing', false],
                        ['D', 'immediately', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 4 Mini-test 6: The data from the survey ___ difficult to interpret without context.',
                    'Data sering dipakai sebagai plural dalam konteks akademik. Verb yang tepat di sini adalah are.',
                    [
                        ['A', 'is', false],
                        ['B', 'are', true],
                        ['C', 'was', false],
                        ['D', 'be', false],
                    ]
                ),
            ],
            'Simple Past' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 5 Mini-test 1: The researchers ___ the results in a public database last year.',
                    'Last year menunjukkan waktu lampau selesai, jadi publish menjadi published.',
                    [
                        ['A', 'publish', false],
                        ['B', 'published', true],
                        ['C', 'publishing', false],
                        ['D', 'have published', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 5 Mini-test 2: The committee did not ___ the schedule until Monday.',
                    'Setelah did not, verb utama harus base form: approve.',
                    [
                        ['A', 'approved', false],
                        ['B', 'approves', false],
                        ['C', 'approve', true],
                        ['D', 'approving', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 5 Mini-test 3: When the system failed, the technician ___ the server manually.',
                    'Kejadian lampau selesai membutuhkan simple past. Restart menjadi restarted.',
                    [
                        ['A', 'restart', false],
                        ['B', 'restarts', false],
                        ['C', 'restarted', true],
                        ['D', 'restarting', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 5 Mini-test 4: The first version of the report ___ fewer charts than the final version.',
                    'Past form dari have adalah had.',
                    [
                        ['A', 'has', false],
                        ['B', 'have', false],
                        ['C', 'had', true],
                        ['D', 'having', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 5 Mini-test 5: Identify the part that must be changed: The students did submitted their essays before the deadline.',
                    'Setelah did, verb utama harus base form. Did submitted harus menjadi did submit.',
                    [
                        ['A', 'The students', false],
                        ['B', 'did submitted', true],
                        ['C', 'their essays', false],
                        ['D', 'before the deadline', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 5 Mini-test 6: The lecture ___ at 9:00 and ended before noon.',
                    'Ended menunjukkan past tense, sehingga verb pertama juga simple past: began.',
                    [
                        ['A', 'begin', false],
                        ['B', 'begins', false],
                        ['C', 'began', true],
                        ['D', 'beginning', false],
                    ]
                ),
            ],
            'Future Tense' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 6 Mini-test 1: The students will ___ the practice set after the lesson.',
                    'Will selalu diikuti base verb, jadi complete.',
                    [
                        ['A', 'complete', true],
                        ['B', 'completes', false],
                        ['C', 'completed', false],
                        ['D', 'completing', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 6 Mini-test 2: The advisor is going to ___ the draft tomorrow.',
                    'Be going to diikuti base verb, jadi review.',
                    [
                        ['A', 'reviews', false],
                        ['B', 'reviewed', false],
                        ['C', 'review', true],
                        ['D', 'reviewing', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 6 Mini-test 3: As soon as the results ___ available, the team will update the chart.',
                    'Future time clause setelah as soon as memakai present simple, bukan will.',
                    [
                        ['A', 'will become', false],
                        ['B', 'become', true],
                        ['C', 'became', false],
                        ['D', 'becoming', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 6 Mini-test 4: The class ___ at 8 a.m. next Monday according to the schedule.',
                    'Jadwal resmi dapat memakai present simple untuk future meaning: begins.',
                    [
                        ['A', 'begins', true],
                        ['B', 'will begins', false],
                        ['C', 'beginning', false],
                        ['D', 'began', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 6 Mini-test 5: Identify the part that must be changed: The instructor will explains the new format before the test starts.',
                    'Will harus diikuti base verb. Will explains harus menjadi will explain.',
                    [
                        ['A', 'will explains', true],
                        ['B', 'the new format', false],
                        ['C', 'before', false],
                        ['D', 'starts', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 6 Mini-test 6: The train is about ___ from the station.',
                    'Be about to + base verb menyatakan kejadian sangat dekat di masa depan.',
                    [
                        ['A', 'leave', false],
                        ['B', 'leaving', false],
                        ['C', 'to leave', true],
                        ['D', 'left', false],
                    ]
                ),
            ],
            'Week 1 Review' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 7 Mini-test 1: The quality of the online exercises ___ every month.',
                    'Subject inti quality singular, dan every month memakai simple present. Jawaban benar: improves.',
                    [
                        ['A', 'improve', false],
                        ['B', 'improves', true],
                        ['C', 'improved', false],
                        ['D', 'improving', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 7 Mini-test 2: The practice session ___ completed by most students yesterday.',
                    'Passive past tense singular membutuhkan was + V3.',
                    [
                        ['A', 'is', false],
                        ['B', 'was', true],
                        ['C', 'were', false],
                        ['D', 'be', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 7 Mini-test 3: Before the course begins, each student ___ a diagnostic quiz.',
                    'Future time clause memakai present simple. Each student singular, jadi takes.',
                    [
                        ['A', 'take', false],
                        ['B', 'takes', true],
                        ['C', 'will take', false],
                        ['D', 'taking', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 7 Mini-test 4: The teacher did not ___ the answers until everyone finished.',
                    'Setelah did not, gunakan base verb: reveal.',
                    [
                        ['A', 'revealed', false],
                        ['B', 'reveals', false],
                        ['C', 'reveal', true],
                        ['D', 'revealing', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 7 Mini-test 5: Identify the part that must be changed: There are a reason why daily review improves accuracy.',
                    'A reason singular, jadi there are harus menjadi there is.',
                    [
                        ['A', 'There are', true],
                        ['B', 'a reason', false],
                        ['C', 'daily review', false],
                        ['D', 'improves', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 7 Mini-test 6: The study notes ___ useful because they summarize each lesson.',
                    'Subject notes plural, jadi be present yang tepat adalah are.',
                    [
                        ['A', 'is', false],
                        ['B', 'are', true],
                        ['C', 'was', false],
                        ['D', 'be', false],
                    ]
                ),
            ],
            'Missing Subject' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 8 Mini-test 1: During the workshop, ___ explained the scoring criteria.',
                    'Frasa during the workshop bukan subject. Kalimat butuh subject + verb: the instructor explained.',
                    [
                        ['A', 'explained', false],
                        ['B', 'the instructor explained', true],
                        ['C', 'explaining the instructor', false],
                        ['D', 'because the instructor explained', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 8 Mini-test 2: Although ___ difficult, the passage contained useful evidence.',
                    'Although-clause membutuhkan subject dan be: it was.',
                    [
                        ['A', 'was', false],
                        ['B', 'it was', true],
                        ['C', 'being', false],
                        ['D', 'there was', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 8 Mini-test 3: ___ are several ways to identify the main verb.',
                    'Pola existential membutuhkan dummy subject there: There are.',
                    [
                        ['A', 'There', true],
                        ['B', 'They', false],
                        ['C', 'It', false],
                        ['D', 'These', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 8 Mini-test 4: After reviewing the transcript, ___ corrected their mistakes.',
                    'Phrase pembuka after reviewing the transcript harus diikuti subject main clause. The learners corrected adalah lengkap.',
                    [
                        ['A', 'corrected', false],
                        ['B', 'the learners corrected', true],
                        ['C', 'correcting the learners', false],
                        ['D', 'because the learners corrected', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 8 Mini-test 5: Identify the part that must be changed: In the final section discusses several common grammar errors.',
                    'In the final section adalah prepositional phrase. Kalimat perlu subject sebelum discusses, misalnya the author discusses.',
                    [
                        ['A', 'In the final section', false],
                        ['B', 'discusses', true],
                        ['C', 'several common', false],
                        ['D', 'grammar errors', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 8 Mini-test 6: Because ___ arrived late, the review began ten minutes after schedule.',
                    'Because-clause membutuhkan subject dan verb. The speaker arrived adalah struktur lengkap.',
                    [
                        ['A', 'arrived', false],
                        ['B', 'the speaker arrived', true],
                        ['C', 'the speaker arriving', false],
                        ['D', 'to arrive', false],
                    ]
                ),
            ],
            'Missing Verb' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 9 Mini-test 1: The explanation of the new grammar pattern ___ several examples.',
                    'Subject explanation membutuhkan finite verb. Includes cocok dengan subject singular.',
                    [
                        ['A', 'including', false],
                        ['B', 'include', false],
                        ['C', 'includes', true],
                        ['D', 'to include', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 9 Mini-test 2: The students ___ for the timed practice when the bell rang.',
                    'Kalimat past continuous membutuhkan were + V-ing.',
                    [
                        ['A', 'preparing', false],
                        ['B', 'were preparing', true],
                        ['C', 'to prepare', false],
                        ['D', 'prepared by', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 9 Mini-test 3: A collection of practice notes from several teachers ___ on the desk.',
                    'Subject inti collection singular. Kalimat membutuhkan be past singular: was.',
                    [
                        ['A', 'was', true],
                        ['B', 'were', false],
                        ['C', 'being', false],
                        ['D', 'to be', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 9 Mini-test 4: The strategy taught in the lesson ___ learners avoid repeated errors.',
                    'Subject strategy membutuhkan main verb. Helps cocok dengan singular subject.',
                    [
                        ['A', 'help', false],
                        ['B', 'helps', true],
                        ['C', 'helping', false],
                        ['D', 'to help', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 9 Mini-test 5: Identify the part that must be changed: The diagram showing the relationship between clauses on the screen.',
                    'Kalimat punya subject diagram dan modifier showing..., tetapi tidak punya main verb. Perlu is/appears, sehingga bagian showing... menandai masalah missing verb.',
                    [
                        ['A', 'The diagram', false],
                        ['B', 'showing', true],
                        ['C', 'between clauses', false],
                        ['D', 'on the screen', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 9 Mini-test 6: The article about online education ___ published in 2025.',
                    'Passive past tense singular membutuhkan was + V3.',
                    [
                        ['A', 'was', true],
                        ['B', 'were', false],
                        ['C', 'being', false],
                        ['D', 'to be', false],
                    ]
                ),
            ],
            'Double Verb' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 10 Mini-test 1: The app ___ students track their daily progress.',
                    'Help dapat diikuti object + base verb: helps students track. Ini bukan double verb karena track adalah complement setelah object.',
                    [
                        ['A', 'helps', true],
                        ['B', 'is helps', false],
                        ['C', 'helping', false],
                        ['D', 'to helps', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 10 Mini-test 2: The researcher ___ the data and wrote a short summary.',
                    'Dua verb sejajar dalam past tense dapat dihubungkan oleh and: analyzed and wrote.',
                    [
                        ['A', 'analyzed', true],
                        ['B', 'analyzed described', false],
                        ['C', 'was analyzed', false],
                        ['D', 'analyzing', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 10 Mini-test 3: The report, which ___ several charts, explains the trend clearly.',
                    'Relative clause membutuhkan finite verb contains. Verb explains adalah main verb untuk subject report.',
                    [
                        ['A', 'contains', true],
                        ['B', 'containing', false],
                        ['C', 'is contains', false],
                        ['D', 'contain', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 10 Mini-test 4: The tutor encouraged the class ___ the explanation before answering.',
                    'Encourage + object + to-infinitive. Bentuk yang tepat adalah to review.',
                    [
                        ['A', 'review', false],
                        ['B', 'reviewed', false],
                        ['C', 'to review', true],
                        ['D', 'reviews', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 10 Mini-test 5: Identify the part that must be changed: The teacher is explains how connectors change sentence structure.',
                    'Is explains adalah double verb salah. Gunakan explains atau is explaining.',
                    [
                        ['A', 'The teacher', false],
                        ['B', 'is explains', true],
                        ['C', 'how connectors', false],
                        ['D', 'sentence structure', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 10 Mini-test 6: The software can ___ errors and suggest corrections.',
                    'Modal can diikuti base verb. Detect and suggest adalah dua base verbs sejajar.',
                    [
                        ['A', 'detect', true],
                        ['B', 'detects', false],
                        ['C', 'detected', false],
                        ['D', 'detecting', false],
                    ]
                ),
            ],
            'Gerund & Infinitive' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 11 Mini-test 1: The students avoided ___ the same mistake twice.',
                    'Avoid diikuti gerund, jadi repeating.',
                    [
                        ['A', 'repeat', false],
                        ['B', 'to repeat', false],
                        ['C', 'repeating', true],
                        ['D', 'repeated', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 11 Mini-test 2: The advisor agreed ___ the draft before Friday.',
                    'Agree diikuti to-infinitive, jadi to review.',
                    [
                        ['A', 'reviewing', false],
                        ['B', 'to review', true],
                        ['C', 'review', false],
                        ['D', 'reviewed', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 11 Mini-test 3: After ___ the examples, the class started the mini-test.',
                    'After adalah preposition/connector yang di sini diikuti gerund phrase, jadi studying.',
                    [
                        ['A', 'study', false],
                        ['B', 'to study', false],
                        ['C', 'studying', true],
                        ['D', 'studied', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 11 Mini-test 4: The purpose of the warm-up is ___ learners notice the pattern.',
                    'Purpose sering memakai to + base verb. Bentuk yang tepat adalah to help.',
                    [
                        ['A', 'help', false],
                        ['B', 'helping', false],
                        ['C', 'to help', true],
                        ['D', 'helped', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 11 Mini-test 5: Identify the part that must be changed: The group is interested in to compare several answer choices.',
                    'Setelah preposition in, gunakan gerund. To compare harus menjadi comparing.',
                    [
                        ['A', 'is interested', false],
                        ['B', 'in to compare', true],
                        ['C', 'several', false],
                        ['D', 'answer choices', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 11 Mini-test 6: ___ every explanation carefully improves long-term accuracy.',
                    'Gerund phrase dapat menjadi subject. Studying every explanation... berfungsi sebagai subject singular.',
                    [
                        ['A', 'Study', false],
                        ['B', 'To studying', false],
                        ['C', 'Studying', true],
                        ['D', 'Studied', false],
                    ]
                ),
            ],
            'Parallel Structure' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 12 Mini-test 1: The course teaches students to analyze sentences, identify patterns, and ___ mistakes.',
                    'Item dalam daftar harus sejajar: to analyze, identify, and correct. Setelah to pertama, base verbs dapat berbagi to.',
                    [
                        ['A', 'correct', true],
                        ['B', 'correcting', false],
                        ['C', 'correction of', false],
                        ['D', 'to correcting', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 12 Mini-test 2: The lecture was not only informative but also ___.',
                    'Not only...but also menghubungkan dua adjective: informative dan practical.',
                    [
                        ['A', 'practice', false],
                        ['B', 'practical', true],
                        ['C', 'practiced', false],
                        ['D', 'to practice', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 12 Mini-test 3: The new method is faster than ___ every question manually.',
                    'Perbandingan harus sejajar. Faster than checking... membandingkan method dengan activity yang dinyatakan sebagai gerund phrase.',
                    [
                        ['A', 'check', false],
                        ['B', 'to check', false],
                        ['C', 'checking', true],
                        ['D', 'checked', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 12 Mini-test 4: Students should read the question carefully, eliminate weak choices, and ___ the answer with evidence.',
                    'Tiga verb sejajar setelah should: read, eliminate, and confirm.',
                    [
                        ['A', 'confirm', true],
                        ['B', 'confirming', false],
                        ['C', 'confirmation', false],
                        ['D', 'to confirm', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 12 Mini-test 5: Identify the part that must be changed: The program helps learners review mistakes, tracking progress, and build confidence.',
                    'Daftar setelah helps learners harus sejajar: review, track, and build. Tracking harus menjadi track.',
                    [
                        ['A', 'helps learners', false],
                        ['B', 'review mistakes', false],
                        ['C', 'tracking progress', true],
                        ['D', 'build confidence', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 12 Mini-test 6: The checklist asks learners to find the subject, ___ the verb, and check agreement.',
                    'Find, identify, and check adalah verb sejajar setelah asks learners to.',
                    [
                        ['A', 'identifying', false],
                        ['B', 'identify', true],
                        ['C', 'identification', false],
                        ['D', 'identified', false],
                    ]
                ),
            ],
            'Preposition' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 13 Mini-test 1: The students focused ___ identifying the main verb first.',
                    'Focus on adalah collocation. Setelah on, gunakan gerund identifying.',
                    [
                        ['A', 'in', false],
                        ['B', 'on', true],
                        ['C', 'at', false],
                        ['D', 'for', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 13 Mini-test 2: The increase ___ online learning changed the way students practice.',
                    'Increase in adalah collocation untuk kenaikan dalam suatu area atau jumlah.',
                    [
                        ['A', 'in', true],
                        ['B', 'for', false],
                        ['C', 'by', false],
                        ['D', 'to', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 13 Mini-test 3: The error was caused ___ a missing auxiliary verb.',
                    'Passive agent atau cause dalam passive memakai by.',
                    [
                        ['A', 'with', false],
                        ['B', 'for', false],
                        ['C', 'by', true],
                        ['D', 'of', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 13 Mini-test 4: During ___, students are expected to manage their time carefully.',
                    'During diikuti noun phrase, bukan clause subject + verb. The simulation adalah noun phrase.',
                    [
                        ['A', 'the simulation', true],
                        ['B', 'students simulate', false],
                        ['C', 'they simulate', false],
                        ['D', 'to simulate', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 13 Mini-test 5: Identify the part that must be changed: The solution of the problem was explained in the final paragraph.',
                    'Collocation yang tepat adalah solution to the problem, bukan solution of the problem.',
                    [
                        ['A', 'The solution of', true],
                        ['B', 'was explained', false],
                        ['C', 'in', false],
                        ['D', 'final paragraph', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 13 Mini-test 6: The team succeeded ___ reducing repeated grammar errors.',
                    'Succeed in + gerund adalah pola yang tepat.',
                    [
                        ['A', 'to', false],
                        ['B', 'for', false],
                        ['C', 'in', true],
                        ['D', 'by', false],
                    ]
                ),
            ],
            'Week 2 Review' => [
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 14 Mini-test 1: After ___ the explanation, learners should complete the mini-test.',
                    'After sebagai preposition/connector diikuti gerund phrase: reviewing.',
                    [
                        ['A', 'review', false],
                        ['B', 'to review', false],
                        ['C', 'reviewing', true],
                        ['D', 'reviewed', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 14 Mini-test 2: The lesson ___ students identify missing subjects and verbs.',
                    'Help + object + base verb. Subject lesson singular, jadi helps.',
                    [
                        ['A', 'help', false],
                        ['B', 'helps', true],
                        ['C', 'is helps', false],
                        ['D', 'helping', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 14 Mini-test 3: The checklist includes finding the subject, checking the verb, and ___ agreement.',
                    'Daftar gerund harus sejajar: finding, checking, and verifying.',
                    [
                        ['A', 'verify', false],
                        ['B', 'to verify', false],
                        ['C', 'verifying', true],
                        ['D', 'verified', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 14 Mini-test 4: In the practice room, ___ several questions about prepositions.',
                    'In the practice room adalah place phrase. Pola there are cocok dengan several questions.',
                    [
                        ['A', 'there are', true],
                        ['B', 'there is', false],
                        ['C', 'are there', false],
                        ['D', 'they are', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::ErrorRecognition,
                    'Day 14 Mini-test 5: Identify the part that must be changed: The students planned improving their accuracy by reviewing old mistakes.',
                    'Plan harus diikuti to-infinitive. Planned improving harus menjadi planned to improve.',
                    [
                        ['A', 'The students', false],
                        ['B', 'planned improving', true],
                        ['C', 'their accuracy', false],
                        ['D', 'by reviewing', false],
                    ]
                ),
                $this->grammarQuestion(
                    QuestionType::IncompleteSentence,
                    'Day 14 Mini-test 6: The guide recommends ___ each wrong answer before moving to the next lesson.',
                    'Recommend diikuti gerund, jadi analyzing.',
                    [
                        ['A', 'analyze', false],
                        ['B', 'to analyze', false],
                        ['C', 'analyzing', true],
                        ['D', 'analyzed', false],
                    ]
                ),
            ],
        ];

        return isset($questions[$title])
            ? array_merge($questions[$title], $this->advancedGrammarQuestionsFor($title))
            : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function advancedGrammarQuestionsFor(string $title): array
    {
        $dayNumber = $this->dayNumberForTitle($title);

        return [
            $this->grammarQuestion(
                QuestionType::IncompleteSentence,
                "Day {$dayNumber} Advanced {$title} 1: The set of diagnostic notes ___ the reason for each error.",
                'Subject inti adalah set, singular. Of diagnostic notes hanya modifier, jadi verb simple present yang tepat adalah explains.',
                [
                    ['A', 'explain', false],
                    ['B', 'explains', true],
                    ['C', 'explaining', false],
                    ['D', 'to explain', false],
                ]
            ),
            $this->grammarQuestion(
                QuestionType::ErrorRecognition,
                "Day {$dayNumber} Advanced {$title} 2: Identify the part that must be changed: After reviewing the answer was easier to identify the pattern.",
                'After reviewing the answer adalah phrase pembuka. Main clause membutuhkan subject it sebelum was.',
                [
                    ['A', 'After reviewing', false],
                    ['B', 'the answer was', true],
                    ['C', 'to identify', false],
                    ['D', 'the pattern', false],
                ]
            ),
            $this->grammarQuestion(
                QuestionType::IncompleteSentence,
                "Day {$dayNumber} Advanced {$title} 3: The practice routine includes identifying subjects, checking verbs, and ___ explanations.",
                'Daftar setelah includes harus sejajar dalam bentuk gerund: identifying, checking, and writing.',
                [
                    ['A', 'write', false],
                    ['B', 'to write', false],
                    ['C', 'writing', true],
                    ['D', 'written', false],
                ]
            ),
            $this->grammarQuestion(
                QuestionType::IncompleteSentence,
                "Day {$dayNumber} Advanced {$title} 4: The instructor encouraged learners ___ the trap before selecting an answer.",
                'Encourage + object diikuti to-infinitive, jadi bentuk yang benar adalah to identify.',
                [
                    ['A', 'identify', false],
                    ['B', 'identifying', false],
                    ['C', 'to identify', true],
                    ['D', 'identified', false],
                ]
            ),
            $this->grammarQuestion(
                QuestionType::ErrorRecognition,
                "Day {$dayNumber} Advanced {$title} 5: Identify the part that must be changed: The worksheet is contains examples that compare correct and incorrect forms.",
                'Is contains adalah double verb. Gunakan contains untuk simple present atau is containing untuk progressive.',
                [
                    ['A', 'The worksheet', false],
                    ['B', 'is contains', true],
                    ['C', 'that compare', false],
                    ['D', 'incorrect forms', false],
                ]
            ),
            $this->grammarQuestion(
                QuestionType::IncompleteSentence,
                "Day {$dayNumber} Advanced {$title} 6: Because the audio moved quickly, ___ the transcript twice after practice.",
                'Because-clause sudah lengkap. Main clause membutuhkan subject dan verb lengkap: the learner reviewed.',
                [
                    ['A', 'the learner reviewed', true],
                    ['B', 'reviewed the learner', false],
                    ['C', 'the learner reviewing', false],
                    ['D', 'to review the learner', false],
                ]
            ),
        ];
    }

    private function dayNumberForTitle(string $title): int
    {
        $dayNumber = array_search($title, $this->days(), true);

        return is_int($dayNumber) ? $dayNumber : 0;
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: bool}>  $options
     * @return array<string, mixed>
     */
    private function grammarQuestion(QuestionType $type, string $questionText, string $explanation, array $options): array
    {
        return [
            'section_type' => SkillType::Structure,
            'question_type' => $type,
            'question_text' => $questionText,
            'explanation' => $explanation,
            'options' => array_map(fn (array $option): array => [
                'label' => $option[0],
                'text' => $option[1],
                'correct' => $option[2],
            ], $options),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function structureQuestion(int $dayNumber, int $variant): array
    {
        $questions = [
            1 => [
                'type' => QuestionType::IncompleteSentence,
                'text' => "Day {$dayNumber} Structure Drill 1: The list of review notes ___ useful for beginners.",
                'explanation' => 'Subject inti adalah list, singular. Of review notes hanya modifier, jadi verb yang tepat adalah is.',
                'options' => [
                    ['label' => 'A', 'text' => 'are', 'correct' => false],
                    ['label' => 'B', 'text' => 'is', 'correct' => true],
                    ['label' => 'C', 'text' => 'be', 'correct' => false],
                    ['label' => 'D', 'text' => 'being', 'correct' => false],
                ],
            ],
            2 => [
                'type' => QuestionType::IncompleteSentence,
                'text' => "Day {$dayNumber} Structure Drill 2: The explanation ___ by the tutor after practice.",
                'explanation' => 'Subject explanation menerima aksi. Passive voice membutuhkan was + V3: was reviewed.',
                'options' => [
                    ['label' => 'A', 'text' => 'was reviewed', 'correct' => true],
                    ['label' => 'B', 'text' => 'reviewed', 'correct' => false],
                    ['label' => 'C', 'text' => 'was review', 'correct' => false],
                    ['label' => 'D', 'text' => 'reviewing', 'correct' => false],
                ],
            ],
            3 => [
                'type' => QuestionType::IncompleteSentence,
                'text' => "Day {$dayNumber} Structure Drill 3: Because the passage was difficult, ___ the evidence twice.",
                'explanation' => 'Because-clause sudah dependent. Main clause membutuhkan subject dan verb lengkap: the student checked.',
                'options' => [
                    ['label' => 'A', 'text' => 'the student checked', 'correct' => true],
                    ['label' => 'B', 'text' => 'checked the student', 'correct' => false],
                    ['label' => 'C', 'text' => 'checking the student', 'correct' => false],
                    ['label' => 'D', 'text' => 'the student checking', 'correct' => false],
                ],
            ],
            4 => [
                'type' => QuestionType::ErrorRecognition,
                'text' => "Day {$dayNumber} Structure Drill 4: Identify the part that must be changed: The tutor asked why was the answer correct.",
                'explanation' => 'Di dalam noun clause, gunakan statement word order: why the answer was correct.',
                'options' => [
                    ['label' => 'A', 'text' => 'asked', 'correct' => false],
                    ['label' => 'B', 'text' => 'why was', 'correct' => true],
                    ['label' => 'C', 'text' => 'the answer', 'correct' => false],
                    ['label' => 'D', 'text' => 'correct', 'correct' => false],
                ],
            ],
        ];

        if (! isset($questions[$variant])) {
            return $this->advancedStructureQuestion($dayNumber, $variant);
        }

        $question = $questions[$variant];

        return [
            'section_type' => SkillType::Structure,
            'question_type' => $question['type'],
            'question_text' => $question['text'],
            'explanation' => $question['explanation'],
            'options' => $question['options'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function advancedStructureQuestion(int $dayNumber, int $variant): array
    {
        $questions = [
            [
                'type' => QuestionType::IncompleteSentence,
                'text' => 'The research team ___ the results before publishing the report.',
                'explanation' => 'Team dapat diperlakukan singular sebagai satu unit. Simple present third-person singular memakai reviews.',
                'options' => [
                    ['label' => 'A', 'text' => 'review', 'correct' => false],
                    ['label' => 'B', 'text' => 'reviews', 'correct' => true],
                    ['label' => 'C', 'text' => 'reviewing', 'correct' => false],
                    ['label' => 'D', 'text' => 'to review', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::IncompleteSentence,
                'text' => 'Neither the examples nor the explanation ___ the second rule clearly.',
                'explanation' => 'Dengan neither/nor, verb mengikuti subject terdekat. Explanation singular, jadi clarifies.',
                'options' => [
                    ['label' => 'A', 'text' => 'clarify', 'correct' => false],
                    ['label' => 'B', 'text' => 'clarifies', 'correct' => true],
                    ['label' => 'C', 'text' => 'clarifying', 'correct' => false],
                    ['label' => 'D', 'text' => 'have clarified', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::ErrorRecognition,
                'text' => 'Identify the part that must be changed: The tutor asked why did the students choose that option.',
                'explanation' => 'Di dalam noun clause, word order harus statement: why the students chose that option.',
                'options' => [
                    ['label' => 'A', 'text' => 'asked', 'correct' => false],
                    ['label' => 'B', 'text' => 'why did', 'correct' => true],
                    ['label' => 'C', 'text' => 'choose', 'correct' => false],
                    ['label' => 'D', 'text' => 'that option', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::IncompleteSentence,
                'text' => 'The checklist helps learners ___ repeated grammar traps.',
                'explanation' => 'Help + object dapat diikuti base verb. Bentuk yang tepat adalah avoid.',
                'options' => [
                    ['label' => 'A', 'text' => 'avoid', 'correct' => true],
                    ['label' => 'B', 'text' => 'avoids', 'correct' => false],
                    ['label' => 'C', 'text' => 'avoiding', 'correct' => false],
                    ['label' => 'D', 'text' => 'to avoiding', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::IncompleteSentence,
                'text' => 'The notes were organized to make the pattern easier ___.',
                'explanation' => 'Adjective easier dapat diikuti to-infinitive. Bentuk yang tepat adalah to recognize.',
                'options' => [
                    ['label' => 'A', 'text' => 'recognize', 'correct' => false],
                    ['label' => 'B', 'text' => 'recognizing', 'correct' => false],
                    ['label' => 'C', 'text' => 'to recognize', 'correct' => true],
                    ['label' => 'D', 'text' => 'recognized', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::ErrorRecognition,
                'text' => 'Identify the part that must be changed: The guide recommends to review the wrong answers immediately.',
                'explanation' => 'Recommend diikuti gerund, sehingga to review harus menjadi reviewing.',
                'options' => [
                    ['label' => 'A', 'text' => 'The guide', 'correct' => false],
                    ['label' => 'B', 'text' => 'recommends to review', 'correct' => true],
                    ['label' => 'C', 'text' => 'wrong answers', 'correct' => false],
                    ['label' => 'D', 'text' => 'immediately', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::IncompleteSentence,
                'text' => 'The strategy is useful because it reduces guessing, improves timing, and ___ accuracy.',
                'explanation' => 'Tiga verb sejajar setelah it: reduces, improves, and increases.',
                'options' => [
                    ['label' => 'A', 'text' => 'increase', 'correct' => false],
                    ['label' => 'B', 'text' => 'increases', 'correct' => true],
                    ['label' => 'C', 'text' => 'increasing', 'correct' => false],
                    ['label' => 'D', 'text' => 'to increase', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::IncompleteSentence,
                'text' => 'By ___ the distractors first, learners can see the correct pattern faster.',
                'explanation' => 'By adalah preposition, jadi setelahnya gunakan gerund: eliminating.',
                'options' => [
                    ['label' => 'A', 'text' => 'eliminate', 'correct' => false],
                    ['label' => 'B', 'text' => 'to eliminate', 'correct' => false],
                    ['label' => 'C', 'text' => 'eliminating', 'correct' => true],
                    ['label' => 'D', 'text' => 'eliminated', 'correct' => false],
                ],
            ],
        ];
        $question = $questions[($variant - 5) % count($questions)];

        return [
            'section_type' => SkillType::Structure,
            'question_type' => $question['type'],
            'question_text' => "Day {$dayNumber} Structure Drill {$variant}: {$question['text']}",
            'explanation' => $question['explanation'],
            'options' => $question['options'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listeningQuestion(int $dayNumber, int $variant): array
    {
        $questions = [
            1 => [
                'type' => QuestionType::ShortConversation,
                'text' => "Day {$dayNumber} Listening Drill 1: What does the second speaker mean?",
                'transcript' => 'Speaker A: Are you joining the TOEFL review today? Speaker B: I cannot make it to the review session.',
                'explanation' => 'Cannot make it berarti tidak bisa hadir. Jawaban benar menyampaikan makna itu dengan paraphrase.',
                'options' => [
                    ['label' => 'A', 'text' => 'The speaker may not attend.', 'correct' => true],
                    ['label' => 'B', 'text' => 'The speaker already finished the test.', 'correct' => false],
                    ['label' => 'C', 'text' => 'The speaker wants a different book.', 'correct' => false],
                    ['label' => 'D', 'text' => 'The speaker forgot the question.', 'correct' => false],
                ],
            ],
            2 => [
                'type' => QuestionType::ShortConversation,
                'text' => "Day {$dayNumber} Listening Drill 2: What does the second speaker suggest?",
                'transcript' => 'Speaker A: The study room is already full. Speaker B: Why do not we use the media room instead?',
                'explanation' => 'Why do not we memberi saran. Pembicara menyarankan memakai media room sebagai alternatif.',
                'options' => [
                    ['label' => 'A', 'text' => 'They should use another room.', 'correct' => true],
                    ['label' => 'B', 'text' => 'They should cancel the class.', 'correct' => false],
                    ['label' => 'C', 'text' => 'The media room is full.', 'correct' => false],
                    ['label' => 'D', 'text' => 'The speaker dislikes studying.', 'correct' => false],
                ],
            ],
            3 => [
                'type' => QuestionType::LongConversation,
                'text' => "Day {$dayNumber} Listening Drill 3: How does the student feel now?",
                'transcript' => 'Student: I was worried about the structure section, but the explanation actually makes sense now.',
                'explanation' => 'But dan actually menunjukkan perubahan sikap. Student sekarang lebih paham dan lebih percaya diri.',
                'options' => [
                    ['label' => 'A', 'text' => 'More confident about the material', 'correct' => true],
                    ['label' => 'B', 'text' => 'Angry because the explanation is unclear', 'correct' => false],
                    ['label' => 'C', 'text' => 'Uninterested in the lesson', 'correct' => false],
                    ['label' => 'D', 'text' => 'Certain that the test is canceled', 'correct' => false],
                ],
            ],
            4 => [
                'type' => QuestionType::TalksLectures,
                'text' => "Day {$dayNumber} Listening Drill 4: What is the main point of the announcement?",
                'transcript' => 'Tutor: Before starting the mini-test, review the explanation for every missed question. This will help you avoid repeating the same pattern.',
                'explanation' => 'Main point announcement adalah review kesalahan sebelum mini-test agar pola salah tidak terulang.',
                'options' => [
                    ['label' => 'A', 'text' => 'Students should review mistakes before the mini-test.', 'correct' => true],
                    ['label' => 'B', 'text' => 'Students should skip the explanation.', 'correct' => false],
                    ['label' => 'C', 'text' => 'The mini-test has been removed.', 'correct' => false],
                    ['label' => 'D', 'text' => 'The tutor only discusses vocabulary lists.', 'correct' => false],
                ],
            ],
        ];

        if (! isset($questions[$variant])) {
            return $this->advancedListeningQuestion($dayNumber, $variant);
        }

        $question = $questions[$variant];

        return [
            'section_type' => SkillType::Listening,
            'question_type' => $question['type'],
            'question_text' => $question['text'],
            'transcript' => $question['transcript'],
            'explanation' => $question['explanation'],
            'options' => $question['options'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function advancedListeningQuestion(int $dayNumber, int $variant): array
    {
        $questions = [
            [
                'type' => QuestionType::ShortConversation,
                'text' => 'What does the woman imply?',
                'transcript' => 'Man: Did you finish the practice set already? Woman: I still have three questions left, but the hardest part is over.',
                'explanation' => 'The hardest part is over menunjukkan bagian tersulit sudah selesai, tetapi latihan belum sepenuhnya selesai.',
                'options' => [
                    ['label' => 'A', 'text' => 'She has completed the most difficult part.', 'correct' => true],
                    ['label' => 'B', 'text' => 'She has not started the practice.', 'correct' => false],
                    ['label' => 'C', 'text' => 'She wants to skip all remaining questions.', 'correct' => false],
                    ['label' => 'D', 'text' => 'She found every question easy.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::ShortConversation,
                'text' => 'What will the students probably do next?',
                'transcript' => 'Tutor: The recording is fast, so listen once for the main idea and then replay it for details.',
                'explanation' => 'Tutor menyarankan urutan: main idea dulu, lalu detail pada replay.',
                'options' => [
                    ['label' => 'A', 'text' => 'Listen for the main idea before focusing on details.', 'correct' => true],
                    ['label' => 'B', 'text' => 'Read the transcript before hearing the recording.', 'correct' => false],
                    ['label' => 'C', 'text' => 'Ignore the main idea completely.', 'correct' => false],
                    ['label' => 'D', 'text' => 'Stop the practice immediately.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::LongConversation,
                'text' => 'Why is the student relieved?',
                'transcript' => 'Student: I thought the lecture question was about dates, but it was really asking for the speaker\'s purpose.',
                'explanation' => 'Student menyadari fokus pertanyaan adalah purpose, bukan dates. Ini membantu memilih jawaban yang tepat.',
                'options' => [
                    ['label' => 'A', 'text' => 'The question tested purpose rather than dates.', 'correct' => true],
                    ['label' => 'B', 'text' => 'The lecture was canceled.', 'correct' => false],
                    ['label' => 'C', 'text' => 'The dates were all missing.', 'correct' => false],
                    ['label' => 'D', 'text' => 'The speaker refused to answer.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::TalksLectures,
                'text' => 'What is the main point of the mini lecture?',
                'transcript' => 'Instructor: Distractors often repeat words from the recording. The correct answer usually keeps the meaning but changes the wording.',
                'explanation' => 'Main point-nya adalah jawaban benar biasanya paraphrase, bukan sekadar pengulangan kata.',
                'options' => [
                    ['label' => 'A', 'text' => 'Correct answers often paraphrase the recording.', 'correct' => true],
                    ['label' => 'B', 'text' => 'Repeated words are always correct.', 'correct' => false],
                    ['label' => 'C', 'text' => 'Students should ignore meaning.', 'correct' => false],
                    ['label' => 'D', 'text' => 'Every option has the same meaning.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::ShortConversation,
                'text' => 'What does the man mean?',
                'transcript' => 'Woman: Should we review the missed questions now? Man: We might as well do it while the mistakes are still fresh.',
                'explanation' => 'Might as well menunjukkan persetujuan/saran praktis untuk langsung review.',
                'options' => [
                    ['label' => 'A', 'text' => 'They should review the mistakes immediately.', 'correct' => true],
                    ['label' => 'B', 'text' => 'They should wait several weeks.', 'correct' => false],
                    ['label' => 'C', 'text' => 'They have no mistakes to review.', 'correct' => false],
                    ['label' => 'D', 'text' => 'They should change the test topic.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::LongConversation,
                'text' => 'What problem does the student mention?',
                'transcript' => 'Student: I understood the first speaker, but I missed the change in opinion after the word however.',
                'explanation' => 'However menandai perubahan/oposisi. Student kehilangan perubahan opini setelah kata itu.',
                'options' => [
                    ['label' => 'A', 'text' => 'The student missed a change in opinion.', 'correct' => true],
                    ['label' => 'B', 'text' => 'The first speaker was silent.', 'correct' => false],
                    ['label' => 'C', 'text' => 'The recording had no contrast marker.', 'correct' => false],
                    ['label' => 'D', 'text' => 'The student ignored the entire conversation.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::TalksLectures,
                'text' => 'What does the speaker emphasize?',
                'transcript' => 'Advisor: In Part C, do not write down every word. Track the topic, the examples, and the conclusion.',
                'explanation' => 'Speaker menekankan tracking struktur lecture, bukan menulis semua kata.',
                'options' => [
                    ['label' => 'A', 'text' => 'Students should follow the lecture structure.', 'correct' => true],
                    ['label' => 'B', 'text' => 'Students should copy every word.', 'correct' => false],
                    ['label' => 'C', 'text' => 'Examples are never important.', 'correct' => false],
                    ['label' => 'D', 'text' => 'The conclusion can be skipped.', 'correct' => false],
                ],
            ],
            [
                'type' => QuestionType::ShortConversation,
                'text' => 'What is the woman suggesting?',
                'transcript' => 'Man: I keep choosing answers that sound familiar. Woman: Try matching the meaning, not the exact words.',
                'explanation' => 'Woman menyarankan mencocokkan makna/paraphrase, bukan kata yang terdengar sama.',
                'options' => [
                    ['label' => 'A', 'text' => 'He should focus on meaning instead of exact words.', 'correct' => true],
                    ['label' => 'B', 'text' => 'He should choose familiar words automatically.', 'correct' => false],
                    ['label' => 'C', 'text' => 'He should stop listening to the audio.', 'correct' => false],
                    ['label' => 'D', 'text' => 'He should ignore every option.', 'correct' => false],
                ],
            ],
        ];
        $question = $questions[($variant - 5) % count($questions)];

        return [
            'section_type' => SkillType::Listening,
            'question_type' => $question['type'],
            'question_text' => "Day {$dayNumber} Listening Drill {$variant}: {$question['text']}",
            'transcript' => $question['transcript'],
            'explanation' => $question['explanation'],
            'options' => $question['options'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readingQuestion(int $dayNumber, int $variant): array
    {
        $passage = 'A clear study plan helps learners improve because it divides a large goal into small actions. When students review mistakes after each practice session, they can see which skill needs attention next. This process also prevents repeated errors because learners understand why an answer is correct or incorrect.';
        $questions = [
            1 => [
                'type' => QuestionType::MainIdea,
                'text' => "Day {$dayNumber} Reading Drill 1: What is the passage mainly about?",
                'explanation' => 'Main idea harus mencakup study plan, review mistakes, dan improvement secara umum.',
                'options' => [
                    ['label' => 'A', 'text' => 'Using a daily plan and review to improve learning', 'correct' => true],
                    ['label' => 'B', 'text' => 'Choosing a university major', 'correct' => false],
                    ['label' => 'C', 'text' => 'Memorizing every word in a dictionary', 'correct' => false],
                    ['label' => 'D', 'text' => 'Avoiding practice sessions', 'correct' => false],
                ],
            ],
            2 => [
                'type' => QuestionType::Detail,
                'text' => "Day {$dayNumber} Reading Drill 2: According to the passage, what can students identify after reviewing mistakes?",
                'explanation' => 'Passage menyebut students can see which skill needs attention next.',
                'options' => [
                    ['label' => 'A', 'text' => 'Which skill needs attention next', 'correct' => true],
                    ['label' => 'B', 'text' => 'Which campus building is closed', 'correct' => false],
                    ['label' => 'C', 'text' => 'How to avoid all future tests', 'correct' => false],
                    ['label' => 'D', 'text' => 'The name of every TOEFL examiner', 'correct' => false],
                ],
            ],
            3 => [
                'type' => QuestionType::VocabularyContext,
                'text' => "Day {$dayNumber} Reading Drill 3: The word \"prevents\" in the passage is closest in meaning to",
                'explanation' => 'Prevents berarti menghentikan atau membuat sesuatu tidak terjadi.',
                'options' => [
                    ['label' => 'A', 'text' => 'stops', 'correct' => true],
                    ['label' => 'B', 'text' => 'creates', 'correct' => false],
                    ['label' => 'C', 'text' => 'copies', 'correct' => false],
                    ['label' => 'D', 'text' => 'hides', 'correct' => false],
                ],
            ],
            4 => [
                'type' => QuestionType::Inference,
                'text' => "Day {$dayNumber} Reading Drill 4: What can be inferred from the passage?",
                'explanation' => 'Jika learners memahami alasan benar atau salah, mereka lebih mungkin menghindari kesalahan yang sama.',
                'options' => [
                    ['label' => 'A', 'text' => 'Understanding explanations can reduce repeated mistakes.', 'correct' => true],
                    ['label' => 'B', 'text' => 'Students should never take practice sessions.', 'correct' => false],
                    ['label' => 'C', 'text' => 'Large goals cannot be divided.', 'correct' => false],
                    ['label' => 'D', 'text' => 'Review is useful only for vocabulary.', 'correct' => false],
                ],
            ],
        ];

        if (! isset($questions[$variant])) {
            return $this->advancedReadingQuestion($dayNumber, $variant);
        }

        $question = $questions[$variant];

        return [
            'section_type' => SkillType::Reading,
            'question_type' => $question['type'],
            'question_text' => $question['text'],
            'passage_text' => $passage,
            'explanation' => $question['explanation'],
            'options' => $question['options'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function advancedReadingQuestion(int $dayNumber, int $variant): array
    {
        $passages = [
            [
                'passage' => 'Students often improve faster when they review explanations immediately after practice. Immediate review keeps the original reasoning fresh, so learners can compare their choice with the evidence in the passage. Delayed review may still help, but it is less efficient because the learner must reconstruct the question context.',
                'type' => QuestionType::MainIdea,
                'text' => 'What is the passage mainly about?',
                'explanation' => 'Passage membahas manfaat immediate review setelah practice untuk menjaga reasoning dan konteks tetap segar.',
                'options' => [
                    ['label' => 'A', 'text' => 'Why immediate review can make practice more efficient', 'correct' => true],
                    ['label' => 'B', 'text' => 'How to avoid reading passages entirely', 'correct' => false],
                    ['label' => 'C', 'text' => 'Why delayed review is always impossible', 'correct' => false],
                    ['label' => 'D', 'text' => 'How to memorize every answer choice', 'correct' => false],
                ],
            ],
            [
                'passage' => 'A strong reading strategy begins before the answer choices are examined. Test takers should identify the question type, locate the relevant sentence, and predict the answer in simple words. This process reduces the influence of distractors that repeat attractive but incomplete phrases from the passage.',
                'type' => QuestionType::Detail,
                'text' => 'According to the passage, what should test takers do before reading the choices?',
                'explanation' => 'Passage menyebut identify question type, locate relevant sentence, dan predict the answer.',
                'options' => [
                    ['label' => 'A', 'text' => 'Predict the answer from the relevant evidence', 'correct' => true],
                    ['label' => 'B', 'text' => 'Choose the longest answer immediately', 'correct' => false],
                    ['label' => 'C', 'text' => 'Ignore the question type', 'correct' => false],
                    ['label' => 'D', 'text' => 'Read only incomplete phrases', 'correct' => false],
                ],
            ],
            [
                'passage' => 'In academic texts, a transition such as however often signals that the writer is about to contrast a previous idea. Readers who notice the transition can adjust their expectation and avoid choosing an answer that matches only the first half of the paragraph.',
                'type' => QuestionType::Inference,
                'text' => 'What can be inferred about transition words?',
                'explanation' => 'Transition seperti however membantu pembaca melihat perubahan arah ide dan menghindari jawaban setengah konteks.',
                'options' => [
                    ['label' => 'A', 'text' => 'They help readers track changes in meaning.', 'correct' => true],
                    ['label' => 'B', 'text' => 'They make the first half of a paragraph irrelevant.', 'correct' => false],
                    ['label' => 'C', 'text' => 'They always introduce examples.', 'correct' => false],
                    ['label' => 'D', 'text' => 'They remove the need to read evidence.', 'correct' => false],
                ],
            ],
            [
                'passage' => 'Vocabulary-in-context questions rarely ask for the dictionary meaning alone. Instead, they ask which option fits the sentence and paragraph. A familiar word may have a less common meaning when it appears in a technical or academic context.',
                'type' => QuestionType::VocabularyContext,
                'text' => 'The phrase "fits the sentence" is closest in meaning to',
                'explanation' => 'Fits the sentence berarti sesuai dengan konteks kalimat, bukan sekadar arti kamus.',
                'options' => [
                    ['label' => 'A', 'text' => 'matches the context', 'correct' => true],
                    ['label' => 'B', 'text' => 'copies the dictionary', 'correct' => false],
                    ['label' => 'C', 'text' => 'removes the paragraph', 'correct' => false],
                    ['label' => 'D', 'text' => 'changes the question', 'correct' => false],
                ],
            ],
            [
                'passage' => 'A pronoun reference question requires the reader to look backward carefully. The correct noun usually appears before the pronoun and agrees with it in number and meaning. If two nouns are possible, the sentence logic decides which reference is correct.',
                'type' => QuestionType::Reference,
                'text' => 'According to the passage, how should readers solve pronoun reference questions?',
                'explanation' => 'Pembaca harus melihat noun sebelumnya dan mengecek agreement serta logika kalimat.',
                'options' => [
                    ['label' => 'A', 'text' => 'Look backward and check number and meaning', 'correct' => true],
                    ['label' => 'B', 'text' => 'Choose the nearest verb automatically', 'correct' => false],
                    ['label' => 'C', 'text' => 'Ignore sentence logic', 'correct' => false],
                    ['label' => 'D', 'text' => 'Look only at the next paragraph', 'correct' => false],
                ],
            ],
            [
                'passage' => 'Timed reading practice should not be rushed from the first sentence. Efficient readers move quickly through familiar information and slow down when they find definitions, contrast markers, or cause-effect relationships. Their speed changes according to the value of the information.',
                'type' => QuestionType::MainIdea,
                'text' => 'What is the main idea of the passage?',
                'explanation' => 'Passage menjelaskan bahwa reading speed harus fleksibel sesuai nilai informasi.',
                'options' => [
                    ['label' => 'A', 'text' => 'Effective reading speed changes with the importance of information.', 'correct' => true],
                    ['label' => 'B', 'text' => 'Readers should rush every sentence equally.', 'correct' => false],
                    ['label' => 'C', 'text' => 'Definitions never matter in timed reading.', 'correct' => false],
                    ['label' => 'D', 'text' => 'Cause-effect relationships should be skipped.', 'correct' => false],
                ],
            ],
            [
                'passage' => 'A detail answer must be supported by a specific line or sentence. If an option sounds reasonable but cannot be linked to evidence, it is not the best answer. TOEFL reading rewards proof, not personal opinion.',
                'type' => QuestionType::Detail,
                'text' => 'According to the passage, what makes a detail answer strong?',
                'explanation' => 'Detail answer harus punya evidence spesifik dari passage.',
                'options' => [
                    ['label' => 'A', 'text' => 'It can be linked to specific evidence.', 'correct' => true],
                    ['label' => 'B', 'text' => 'It is based only on personal opinion.', 'correct' => false],
                    ['label' => 'C', 'text' => 'It sounds reasonable without proof.', 'correct' => false],
                    ['label' => 'D', 'text' => 'It avoids every line in the passage.', 'correct' => false],
                ],
            ],
            [
                'passage' => 'When a paragraph includes examples, the examples usually support a broader claim. Readers should not mistake the example for the main idea. The example is evidence, while the claim explains why the evidence matters.',
                'type' => QuestionType::Inference,
                'text' => 'What can be inferred about examples in a paragraph?',
                'explanation' => 'Examples mendukung broader claim, tetapi bukan main idea itu sendiri.',
                'options' => [
                    ['label' => 'A', 'text' => 'They often support a larger claim.', 'correct' => true],
                    ['label' => 'B', 'text' => 'They always replace the claim.', 'correct' => false],
                    ['label' => 'C', 'text' => 'They are unrelated to evidence.', 'correct' => false],
                    ['label' => 'D', 'text' => 'They remove the need for context.', 'correct' => false],
                ],
            ],
        ];
        $question = $passages[($variant - 5) % count($passages)];

        return [
            'section_type' => SkillType::Reading,
            'question_type' => $question['type'],
            'question_text' => "Day {$dayNumber} Reading Drill {$variant}: {$question['text']}",
            'passage_text' => $question['passage'],
            'explanation' => $question['explanation'],
            'options' => $question['options'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function vocabularies(): array
    {
        $words = [
            ['ability', 'kemampuan', 'Listening ability improves with review.', 'academic', 'beginner'],
            ['accurate', 'akurat', 'Accurate grammar improves Structure scores.', 'academic', 'beginner'],
            ['achieve', 'mencapai', 'You can achieve your target score.', 'academic', 'beginner'],
            ['analyze', 'menganalisis', 'Analyze the question before reading the options.', 'academic', 'beginner'],
            ['approach', 'pendekatan', 'A calm approach helps learners solve difficult questions.', 'academic', 'beginner'],
            ['available', 'tersedia', 'The lesson is available today.', 'academic', 'beginner'],
            ['benefit', 'manfaat', 'Daily review has a clear benefit for memory.', 'academic', 'beginner'],
            ['cause', 'menyebabkan', 'Lack of practice can cause low accuracy.', 'academic', 'beginner'],
            ['challenge', 'tantangan', 'Fast listening is a common challenge for learners.', 'academic', 'beginner'],
            ['concept', 'konsep', 'The concept becomes easier after several examples.', 'academic', 'beginner'],
            ['context', 'konteks', 'Vocabulary questions depend on context.', 'academic', 'beginner'],
            ['data', 'data', 'The data show steady improvement.', 'academic', 'beginner'],
            ['decrease', 'menurun', 'Mistakes decrease after review.', 'academic', 'beginner'],
            ['develop', 'mengembangkan', 'Students develop confidence through repetition.', 'academic', 'beginner'],
            ['effect', 'dampak', 'Review has a strong effect on progress.', 'academic', 'beginner'],
            ['evidence', 'bukti', 'Choose answers with evidence from the passage.', 'academic', 'beginner'],
            ['factor', 'faktor', 'Timing is one factor that affects test performance.', 'academic', 'beginner'],
            ['feature', 'fitur', 'The feature highlights repeated mistakes.', 'academic', 'beginner'],
            ['function', 'fungsi', 'Each grammar rule has a clear function.', 'academic', 'beginner'],
            ['important', 'penting', 'Grammar is important for Structure questions.', 'academic', 'beginner'],
            ['improve', 'meningkatkan', 'I want to improve my TOEFL score.', 'academic', 'beginner'],
            ['increase', 'meningkat', 'Your score can increase with focused drills.', 'academic', 'beginner'],
            ['issue', 'masalah', 'The main issue is repeated guessing.', 'academic', 'beginner'],
            ['maintain', 'mempertahankan', 'Maintain your streak this week.', 'academic', 'beginner'],
            ['method', 'metode', 'This method trains accuracy before speed.', 'academic', 'beginner'],
            ['occur', 'terjadi', 'Mistakes occur when learners rush.', 'academic', 'beginner'],
            ['obtain', 'memperoleh', 'Students obtain better results with practice.', 'academic', 'beginner'],
            ['period', 'periode', 'The study period lasted for eight weeks.', 'academic', 'beginner'],
            ['process', 'proses', 'Learning is a daily process.', 'academic', 'beginner'],
            ['provide', 'menyediakan', 'The app provides daily practice.', 'academic', 'beginner'],
            ['purpose', 'tujuan', 'The purpose of practice is accuracy.', 'academic', 'beginner'],
            ['range', 'rentang', 'The score range helps learners set targets.', 'academic', 'beginner'],
            ['region', 'wilayah', 'The passage compares climate patterns by region.', 'academic', 'beginner'],
            ['require', 'membutuhkan', 'This program requires regular practice.', 'academic', 'beginner'],
            ['respond', 'merespons', 'Respond quickly during listening practice.', 'academic', 'beginner'],
            ['result', 'hasil', 'The result shows your weak area.', 'academic', 'beginner'],
            ['significant', 'penting', 'A significant change can happen in two months.', 'academic', 'intermediate'],
            ['similar', 'mirip', 'These answers are similar in meaning.', 'academic', 'beginner'],
            ['source', 'sumber', 'The source of the evidence is the second paragraph.', 'academic', 'beginner'],
            ['structure', 'struktur', 'Sentence structure matters in TOEFL questions.', 'academic', 'beginner'],
            ['valid', 'sah', 'A valid answer must match the evidence.', 'academic', 'intermediate'],
            ['assess', 'menilai', 'The test assesses reading and listening ability.', 'research', 'intermediate'],
            ['assume', 'berasumsi', 'Do not assume the answer without evidence.', 'research', 'intermediate'],
            ['confirm', 'memastikan', 'Confirm the answer with a line from the passage.', 'research', 'beginner'],
            ['contrast', 'membedakan', 'The writer contrasts two learning methods.', 'research', 'intermediate'],
            ['define', 'mendefinisikan', 'The paragraph defines the technical term first.', 'research', 'beginner'],
            ['derive', 'memperoleh', 'The conclusion is derived from the results.', 'research', 'advanced'],
            ['demonstrate', 'menunjukkan', 'The example demonstrates the rule clearly.', 'research', 'intermediate'],
            ['emphasize', 'menekankan', 'The speaker emphasizes the final deadline.', 'research', 'intermediate'],
            ['evaluate', 'mengevaluasi', 'Evaluate each option before choosing.', 'research', 'intermediate'],
            ['expand', 'memperluas', 'The second paragraph expands the first idea.', 'research', 'intermediate'],
            ['identify', 'mengidentifikasi', 'Identify the subject before checking the verb.', 'research', 'beginner'],
            ['indicate', 'menunjukkan', 'The chart indicates steady progress.', 'research', 'beginner'],
            ['infer', 'menyimpulkan', 'Infer the answer from the speaker\'s tone.', 'research', 'intermediate'],
            ['interpret', 'menafsirkan', 'Interpret the sentence in context.', 'research', 'intermediate'],
            ['justify', 'membenarkan', 'Justify the answer with evidence.', 'research', 'advanced'],
            ['locate', 'menemukan', 'Locate the relevant sentence quickly.', 'research', 'beginner'],
            ['predict', 'memprediksi', 'Predict the answer before reading the choices.', 'research', 'intermediate'],
            ['principle', 'prinsip', 'The principle explains why the verb changes.', 'research', 'intermediate'],
            ['theory', 'teori', 'The theory is explained in the lecture.', 'research', 'advanced'],
            ['although', 'meskipun', 'Although listening is fast, the meaning is clear.', 'transition', 'beginner'],
            ['consequently', 'akibatnya', 'The learner reviewed daily; consequently, accuracy improved.', 'transition', 'intermediate'],
            ['despite', 'meskipun', 'Despite the difficult passage, the main idea is clear.', 'transition', 'intermediate'],
            ['furthermore', 'selain itu', 'Furthermore, the example supports the claim.', 'transition', 'intermediate'],
            ['however', 'namun', 'The test is difficult; however, it is manageable.', 'transition', 'beginner'],
            ['instead', 'sebagai gantinya', 'Instead, choose the option with the same meaning.', 'transition', 'beginner'],
            ['likewise', 'demikian juga', 'Likewise, the second example supports the rule.', 'transition', 'advanced'],
            ['meanwhile', 'sementara itu', 'Meanwhile, the lecturer introduces another example.', 'transition', 'intermediate'],
            ['moreover', 'selain itu', 'Moreover, the answer must fit the paragraph.', 'transition', 'intermediate'],
            ['nevertheless', 'meskipun demikian', 'Nevertheless, the correct answer is still clear.', 'transition', 'advanced'],
            ['otherwise', 'jika tidak', 'Review the rule; otherwise, the mistake may repeat.', 'transition', 'intermediate'],
            ['therefore', 'oleh karena itu', 'You reviewed mistakes; therefore, you improved.', 'transition', 'beginner'],
            ['whereas', 'sedangkan', 'The first option is too broad, whereas the second is precise.', 'transition', 'advanced'],
            ['claim', 'klaim', 'The claim appears in the first sentence.', 'reading', 'intermediate'],
            ['detail', 'rincian', 'The detail question asks for specific evidence.', 'reading', 'beginner'],
            ['inference', 'kesimpulan tersirat', 'An inference question requires careful reasoning.', 'reading', 'intermediate'],
            ['main idea', 'ide utama', 'The main idea covers the whole passage.', 'reading', 'beginner'],
            ['paragraph', 'paragraf', 'The paragraph explains one central idea.', 'reading', 'beginner'],
            ['passage', 'bacaan', 'Read the passage before checking the options.', 'reading', 'beginner'],
            ['reference', 'rujukan', 'The reference question asks what a pronoun means.', 'reading', 'intermediate'],
            ['summary', 'ringkasan', 'The summary keeps only the important points.', 'reading', 'beginner'],
            ['announcement', 'pengumuman', 'The announcement explains a schedule change.', 'listening', 'beginner'],
            ['attitude', 'sikap', 'The speaker\'s attitude can reveal the answer.', 'listening', 'intermediate'],
            ['conversation', 'percakapan', 'The conversation includes two speakers.', 'listening', 'beginner'],
            ['imply', 'menyiratkan', 'What does the professor imply in the final sentence?', 'listening', 'intermediate'],
            ['lecture', 'kuliah', 'The lecture discusses environmental change.', 'listening', 'beginner'],
            ['recording', 'rekaman', 'The recording is played only once in practice.', 'listening', 'beginner'],
            ['speaker', 'pembicara', 'The second speaker disagrees politely.', 'listening', 'beginner'],
            ['suggest', 'menyarankan', 'The woman suggests reviewing the notes.', 'listening', 'beginner'],
            ['tone', 'nada', 'The tone shows that the speaker is unsure.', 'listening', 'intermediate'],
            ['transcript', 'transkrip', 'The transcript confirms the paraphrase.', 'listening', 'beginner'],
            ['agreement', 'kesesuaian', 'Subject-verb agreement is tested often.', 'structure', 'beginner'],
            ['auxiliary', 'kata bantu', 'An auxiliary changes the verb form.', 'structure', 'intermediate'],
            ['clause', 'klausa', 'A clause has a subject and a verb.', 'structure', 'beginner'],
            ['conjunction', 'kata hubung', 'A conjunction connects two ideas.', 'structure', 'beginner'],
            ['gerund', 'kata kerja benda', 'A gerund can be the subject of a sentence.', 'structure', 'intermediate'],
            ['infinitive', 'to + kata kerja', 'An infinitive often shows purpose.', 'structure', 'intermediate'],
            ['modifier', 'penerang', 'A modifier adds information to a noun.', 'structure', 'intermediate'],
            ['parallel', 'sejajar', 'Parallel items have the same grammar form.', 'structure', 'intermediate'],
            ['passive', 'pasif', 'Passive voice uses be plus past participle.', 'structure', 'intermediate'],
            ['phrase', 'frasa', 'A phrase does not always contain a verb.', 'structure', 'beginner'],
            ['predicate', 'predikat', 'The predicate tells what the subject does.', 'structure', 'advanced'],
            ['preposition', 'preposisi', 'A preposition is followed by a noun or gerund.', 'structure', 'beginner'],
            ['subject', 'subjek', 'The subject controls verb agreement.', 'structure', 'beginner'],
            ['tense', 'bentuk waktu', 'Tense shows when the action happens.', 'structure', 'beginner'],
            ['verb', 'kata kerja', 'Every complete sentence needs a main verb.', 'structure', 'beginner'],
            ['accuracy', 'akurasi', 'Accuracy matters before speed.', 'test-strategy', 'beginner'],
            ['distractor', 'pilihan pengecoh', 'A distractor may repeat words from the passage.', 'test-strategy', 'intermediate'],
            ['eliminate', 'mengeliminasi', 'Eliminate choices that do not match the evidence.', 'test-strategy', 'beginner'],
            ['mistake', 'kesalahan', 'A mistake becomes useful after review.', 'test-strategy', 'beginner'],
            ['option', 'pilihan', 'Read every option before answering.', 'test-strategy', 'beginner'],
            ['paraphrase', 'parafrasa', 'A paraphrase keeps the meaning with different words.', 'test-strategy', 'intermediate'],
            ['pattern', 'pola', 'The pattern appears in many Structure questions.', 'test-strategy', 'beginner'],
            ['progress', 'kemajuan', 'Progress becomes visible after consistent practice.', 'test-strategy', 'beginner'],
            ['review', 'meninjau ulang', 'Review every wrong answer after practice.', 'test-strategy', 'beginner'],
            ['simulation', 'simulasi', 'A simulation trains timing and endurance.', 'test-strategy', 'intermediate'],
            ['target', 'target', 'Set a target before starting the session.', 'test-strategy', 'beginner'],
            ['timing', 'pengaturan waktu', 'Good timing prevents rushed decisions.', 'test-strategy', 'intermediate'],
            ['abstract', 'abstrak', 'The abstract idea becomes clear after the example.', 'academic', 'advanced'],
            ['accessible', 'mudah diakses', 'The explanation is accessible to new learners.', 'academic', 'intermediate'],
            ['accommodate', 'menyesuaikan', 'The schedule can accommodate extra review time.', 'academic', 'advanced'],
            ['accumulate', 'menumpuk', 'Small errors accumulate during a long test.', 'academic', 'intermediate'],
            ['acknowledge', 'mengakui', 'The writer acknowledges an opposing view.', 'academic', 'advanced'],
            ['acquire', 'memperoleh', 'Learners acquire vocabulary through repeated exposure.', 'academic', 'intermediate'],
            ['adequate', 'memadai', 'Adequate preparation reduces stress.', 'academic', 'intermediate'],
            ['adjacent', 'berdekatan', 'The adjacent sentence gives a useful clue.', 'academic', 'advanced'],
            ['adjust', 'menyesuaikan', 'Adjust your pacing after the first passage.', 'academic', 'beginner'],
            ['advocate', 'menganjurkan', 'The professor advocates a practical solution.', 'academic', 'advanced'],
            ['allocate', 'mengalokasikan', 'Allocate enough time for the final questions.', 'academic', 'intermediate'],
            ['alter', 'mengubah', 'One word can alter the meaning of a sentence.', 'academic', 'intermediate'],
            ['alternative', 'alternatif', 'The passage presents an alternative explanation.', 'academic', 'intermediate'],
            ['ambiguous', 'bermakna ganda', 'An ambiguous option is usually unsafe.', 'academic', 'advanced'],
            ['annual', 'tahunan', 'The annual survey measured student progress.', 'academic', 'beginner'],
            ['anticipate', 'mengantisipasi', 'Anticipate the next idea while listening.', 'academic', 'intermediate'],
            ['apparent', 'jelas', 'The answer becomes apparent after reading the detail.', 'academic', 'intermediate'],
            ['approximate', 'mendekati', 'The approximate meaning is enough for the question.', 'academic', 'advanced'],
            ['arbitrary', 'sewenang-wenang', 'The choice should not be arbitrary.', 'academic', 'advanced'],
            ['aspect', 'aspek', 'The paragraph focuses on one aspect of climate.', 'academic', 'beginner'],
            ['assign', 'menugaskan', 'The instructor will assign a short reading.', 'academic', 'beginner'],
            ['attain', 'mencapai', 'Students can attain a higher score with review.', 'academic', 'intermediate'],
            ['attribute', 'mengaitkan', 'The author attributes the change to new technology.', 'academic', 'advanced'],
            ['capacity', 'kapasitas', 'Memory capacity improves through active recall.', 'academic', 'intermediate'],
            ['coherent', 'runtut', 'A coherent summary connects all key points.', 'academic', 'advanced'],
            ['compensate', 'mengimbangi', 'Strong grammar can compensate for slower reading.', 'academic', 'advanced'],
            ['compile', 'menyusun', 'Compile repeated mistakes into one review list.', 'academic', 'intermediate'],
            ['complex', 'rumit', 'Complex sentences often contain more than one clause.', 'academic', 'intermediate'],
            ['comprehensive', 'menyeluruh', 'A comprehensive review covers all skill areas.', 'academic', 'advanced'],
            ['comprise', 'terdiri dari', 'The section comprises several question types.', 'academic', 'advanced'],
            ['considerable', 'cukup besar', 'Considerable progress appears after consistent study.', 'academic', 'advanced'],
            ['consistent', 'konsisten', 'Consistent practice builds stable accuracy.', 'academic', 'intermediate'],
            ['constitute', 'membentuk', 'These examples constitute strong evidence.', 'academic', 'advanced'],
            ['constraint', 'batasan', 'Time is the main constraint in the exam.', 'academic', 'advanced'],
            ['consume', 'menghabiskan', 'Hard passages consume more reading time.', 'academic', 'intermediate'],
            ['contribute', 'berkontribusi', 'Vocabulary contributes to reading speed.', 'academic', 'intermediate'],
            ['conventional', 'umum', 'The writer rejects a conventional assumption.', 'academic', 'advanced'],
            ['crucial', 'sangat penting', 'It is crucial to check the subject first.', 'academic', 'intermediate'],
            ['decline', 'penurunan', 'The graph shows a decline after 2010.', 'academic', 'intermediate'],
            ['distinct', 'berbeda', 'The two terms have distinct meanings.', 'academic', 'intermediate'],
            ['diverse', 'beragam', 'Diverse examples make the rule clearer.', 'academic', 'intermediate'],
            ['dominant', 'dominan', 'The dominant idea appears in the topic sentence.', 'academic', 'advanced'],
            ['emerge', 'muncul', 'A pattern may emerge after several attempts.', 'academic', 'intermediate'],
            ['enhance', 'meningkatkan', 'Review can enhance long-term memory.', 'academic', 'intermediate'],
            ['ensure', 'memastikan', 'Ensure every answer matches the evidence.', 'academic', 'beginner'],
            ['establish', 'membangun', 'The first paragraph establishes the topic.', 'academic', 'intermediate'],
            ['estimate', 'memperkirakan', 'Estimate the time before starting the passage.', 'academic', 'beginner'],
            ['exclude', 'mengecualikan', 'Exclude answers that contradict the lecture.', 'academic', 'intermediate'],
            ['feasible', 'layak', 'A feasible plan fits the available time.', 'academic', 'advanced'],
            ['fluctuate', 'berfluktuasi', 'Scores may fluctuate during early practice.', 'academic', 'advanced'],
            ['fundamental', 'mendasar', 'Subject and verb rules are fundamental.', 'academic', 'intermediate'],
            ['generate', 'menghasilkan', 'Active recall can generate stronger memory.', 'academic', 'intermediate'],
            ['incentive', 'dorongan', 'A visible streak gives learners an incentive.', 'academic', 'advanced'],
            ['initial', 'awal', 'The initial answer may change after review.', 'academic', 'beginner'],
            ['insight', 'wawasan', 'Mistake analysis gives useful insight.', 'academic', 'intermediate'],
            ['investigate', 'menyelidiki', 'The researcher investigates learning habits.', 'academic', 'advanced'],
            ['involve', 'melibatkan', 'The task may involve comparing two claims.', 'academic', 'beginner'],
            ['layer', 'lapisan', 'The passage adds another layer of meaning.', 'academic', 'intermediate'],
            ['link', 'hubungan', 'Find the link between the example and the claim.', 'academic', 'beginner'],
            ['major', 'utama', 'The major point appears before the example.', 'academic', 'beginner'],
            ['minor', 'kecil', 'A minor detail should not become the main answer.', 'academic', 'beginner'],
            ['outcome', 'hasil akhir', 'The outcome depends on daily practice.', 'academic', 'intermediate'],
            ['overall', 'secara keseluruhan', 'Overall meaning matters more than one unknown word.', 'academic', 'beginner'],
            ['perspective', 'sudut pandang', 'The lecture presents a different perspective.', 'academic', 'intermediate'],
            ['policy', 'kebijakan', 'The campus policy affects all students.', 'academic', 'intermediate'],
            ['potential', 'potensi', 'The answer has potential but lacks evidence.', 'academic', 'intermediate'],
            ['precise', 'tepat', 'Choose the most precise meaning.', 'academic', 'intermediate'],
            ['previous', 'sebelumnya', 'The previous sentence gives context.', 'academic', 'beginner'],
            ['primary', 'utama', 'The primary reason is stated in paragraph two.', 'academic', 'beginner'],
            ['priority', 'prioritas', 'Make accuracy the first priority.', 'academic', 'beginner'],
            ['resource', 'sumber daya', 'Use every resource for review.', 'academic', 'beginner'],
            ['retain', 'menyimpan', 'Spaced repetition helps retain vocabulary.', 'academic', 'intermediate'],
            ['sequence', 'urutan', 'The sequence of events reveals the answer.', 'academic', 'intermediate'],
            ['shift', 'pergeseran', 'A shift in tone changes the meaning.', 'academic', 'intermediate'],
            ['stable', 'stabil', 'Stable progress comes from steady habits.', 'academic', 'beginner'],
            ['sufficient', 'cukup', 'Sufficient evidence supports the answer.', 'academic', 'intermediate'],
            ['survey', 'survei', 'The survey includes students from three regions.', 'academic', 'beginner'],
            ['technical', 'teknis', 'Technical words may be defined in context.', 'academic', 'intermediate'],
            ['trend', 'tren', 'The trend rises during the final year.', 'academic', 'intermediate'],
            ['undergo', 'mengalami', 'The system may undergo a major change.', 'academic', 'advanced'],
            ['vary', 'bervariasi', 'Question difficulty can vary by passage.', 'academic', 'intermediate'],
            ['widespread', 'tersebar luas', 'The problem is widespread in large classes.', 'academic', 'advanced'],
            ['analysis', 'analisis', 'The analysis explains why the answer works.', 'research', 'intermediate'],
            ['bias', 'bias', 'Bias can affect how data are interpreted.', 'research', 'advanced'],
            ['citation', 'kutipan sumber', 'A citation identifies the original source.', 'research', 'advanced'],
            ['cite', 'mengutip', 'The writer cites a recent study.', 'research', 'intermediate'],
            ['classify', 'mengelompokkan', 'Classify the examples by function.', 'research', 'intermediate'],
            ['compare', 'membandingkan', 'Compare the two explanations before answering.', 'research', 'beginner'],
            ['correlation', 'korelasi', 'The study found a correlation between practice and score.', 'research', 'advanced'],
            ['criteria', 'kriteria', 'Use clear criteria to choose the answer.', 'research', 'advanced'],
            ['dataset', 'kumpulan data', 'The dataset includes scores from many learners.', 'research', 'advanced'],
            ['finding', 'temuan', 'The finding supports the main claim.', 'research', 'intermediate'],
            ['framework', 'kerangka kerja', 'The framework organizes the research question.', 'research', 'advanced'],
            ['hypothesis', 'hipotesis', 'The hypothesis predicts a score increase.', 'research', 'advanced'],
            ['illustrate', 'menggambarkan', 'The example illustrates the grammar pattern.', 'research', 'intermediate'],
            ['limitation', 'keterbatasan', 'The limitation appears in the final paragraph.', 'research', 'advanced'],
            ['measure', 'mengukur', 'The test measures comprehension under time pressure.', 'research', 'beginner'],
            ['methodology', 'metodologi', 'The methodology explains how data were collected.', 'research', 'advanced'],
            ['observation', 'pengamatan', 'The observation supports the conclusion.', 'research', 'intermediate'],
            ['outlier', 'nilai menyimpang', 'An outlier can distort the average.', 'research', 'advanced'],
            ['peer review', 'tinjauan sejawat', 'Peer review improves research quality.', 'research', 'advanced'],
            ['phenomenon', 'fenomena', 'The phenomenon appears in several studies.', 'research', 'advanced'],
            ['procedure', 'prosedur', 'The procedure follows three steps.', 'research', 'intermediate'],
            ['publication', 'publikasi', 'The publication summarizes the experiment.', 'research', 'intermediate'],
            ['qualitative', 'kualitatif', 'Qualitative data describe learner behavior.', 'research', 'advanced'],
            ['quantitative', 'kuantitatif', 'Quantitative data include scores and counts.', 'research', 'advanced'],
            ['reliability', 'keandalan', 'Reliability means the result is consistent.', 'research', 'advanced'],
            ['replicate', 'mengulangi penelitian', 'Researchers replicate a study to confirm results.', 'research', 'advanced'],
            ['sample', 'sampel', 'The sample includes fifty students.', 'research', 'intermediate'],
            ['scope', 'cakupan', 'The scope of the study is limited.', 'research', 'advanced'],
            ['validity', 'validitas', 'Validity shows whether the test measures the right skill.', 'research', 'advanced'],
            ['variable', 'variabel', 'A variable can change during an experiment.', 'research', 'intermediate'],
            ['argument', 'argumen', 'The argument depends on strong evidence.', 'reading', 'intermediate'],
            ['author purpose', 'tujuan penulis', 'Author purpose questions ask why the writer includes an idea.', 'reading', 'intermediate'],
            ['cause-and-effect', 'sebab akibat', 'Cause-and-effect structure links events clearly.', 'reading', 'intermediate'],
            ['central idea', 'gagasan utama', 'The central idea controls the whole passage.', 'reading', 'beginner'],
            ['chronology', 'urutan waktu', 'Chronology helps organize historical passages.', 'reading', 'advanced'],
            ['conclusion', 'kesimpulan', 'The conclusion follows from the evidence.', 'reading', 'intermediate'],
            ['counterargument', 'argumen tandingan', 'A counterargument challenges the main claim.', 'reading', 'advanced'],
            ['definition clue', 'petunjuk definisi', 'A definition clue explains an unfamiliar term.', 'reading', 'beginner'],
            ['negative factual', 'fakta negatif', 'Negative factual questions ask what is not stated.', 'reading', 'advanced'],
            ['paragraph organization', 'susunan paragraf', 'Paragraph organization reveals how ideas connect.', 'reading', 'advanced'],
            ['premise', 'premis', 'The premise supports the conclusion.', 'reading', 'advanced'],
            ['pronoun reference', 'rujukan kata ganti', 'Pronoun reference questions ask what a pronoun replaces.', 'reading', 'intermediate'],
            ['referent', 'rujukan', 'The referent appears in the previous sentence.', 'reading', 'intermediate'],
            ['restatement', 'pernyataan ulang', 'A restatement expresses the same meaning differently.', 'reading', 'intermediate'],
            ['rhetorical purpose', 'tujuan retoris', 'Rhetorical purpose questions ask why a detail is included.', 'reading', 'advanced'],
            ['scan', 'memindai', 'Scan the paragraph for names and numbers.', 'reading', 'beginner'],
            ['sentence insertion', 'penyisipan kalimat', 'Sentence insertion requires checking logical flow.', 'reading', 'advanced'],
            ['skim', 'membaca sekilas', 'Skim the passage to get the main idea.', 'reading', 'beginner'],
            ['stance', 'sikap penulis', 'The writer takes a cautious stance.', 'reading', 'advanced'],
            ['supporting detail', 'rincian pendukung', 'A supporting detail proves the claim.', 'reading', 'beginner'],
            ['supporting example', 'contoh pendukung', 'A supporting example clarifies the topic sentence.', 'reading', 'beginner'],
            ['tone shift', 'perubahan nada', 'A tone shift may signal contrast.', 'reading', 'advanced'],
            ['transition signal', 'penanda transisi', 'A transition signal links two ideas.', 'reading', 'intermediate'],
            ['viewpoint', 'sudut pandang', 'The viewpoint appears in the final sentence.', 'reading', 'intermediate'],
            ['vocabulary-in-context', 'kosakata dalam konteks', 'Vocabulary-in-context questions depend on surrounding words.', 'reading', 'intermediate'],
            ['accent', 'aksen', 'The accent does not change the main meaning.', 'listening', 'intermediate'],
            ['advisor', 'penasihat akademik', 'The advisor recommends a different course.', 'listening', 'beginner'],
            ['assignment', 'tugas', 'The assignment is due next Monday.', 'listening', 'beginner'],
            ['attendance', 'kehadiran', 'Attendance affects the final grade.', 'listening', 'beginner'],
            ['campus', 'kampus', 'The campus announcement mentions a new library.', 'listening', 'beginner'],
            ['clarification', 'klarifikasi', 'The student asks for clarification after the lecture.', 'listening', 'intermediate'],
            ['deadline', 'tenggat waktu', 'The deadline was moved to Friday.', 'listening', 'beginner'],
            ['discussion', 'diskusi', 'The discussion focuses on marine biology.', 'listening', 'beginner'],
            ['enrollment', 'pendaftaran kuliah', 'Enrollment opens at the end of the month.', 'listening', 'intermediate'],
            ['extension', 'perpanjangan waktu', 'The professor gives the student an extension.', 'listening', 'intermediate'],
            ['feedback', 'umpan balik', 'The student wants feedback on the paper.', 'listening', 'beginner'],
            ['field trip', 'kunjungan lapangan', 'The field trip was canceled because of rain.', 'listening', 'intermediate'],
            ['homework', 'pekerjaan rumah', 'The homework reinforces the lecture topic.', 'listening', 'beginner'],
            ['internship', 'magang', 'The internship requires a recommendation letter.', 'listening', 'intermediate'],
            ['interruption', 'interupsi', 'The interruption makes the speaker repeat the point.', 'listening', 'advanced'],
            ['lab', 'laboratorium', 'The lab session begins after lunch.', 'listening', 'beginner'],
            ['note-taking', 'mencatat', 'Good note-taking captures the structure of a lecture.', 'listening', 'intermediate'],
            ['office hours', 'jam konsultasi dosen', 'The student visits during office hours.', 'listening', 'beginner'],
            ['presentation', 'presentasi', 'The presentation explains renewable energy.', 'listening', 'beginner'],
            ['professor', 'profesor', 'The professor changes the reading list.', 'listening', 'beginner'],
            ['proposal', 'proposal', 'The proposal needs a clearer research question.', 'listening', 'intermediate'],
            ['recommendation', 'rekomendasi', 'The professor writes a recommendation for the student.', 'listening', 'intermediate'],
            ['registration', 'pendaftaran', 'Registration closes before the semester starts.', 'listening', 'beginner'],
            ['requirement', 'persyaratan', 'The course requirement includes one project.', 'listening', 'intermediate'],
            ['schedule', 'jadwal', 'The schedule changes after the first week.', 'listening', 'beginner'],
            ['seminar', 'seminar', 'The seminar introduces current research.', 'listening', 'intermediate'],
            ['syllabus', 'silabus', 'The syllabus lists every assignment.', 'listening', 'beginner'],
            ['tuition', 'biaya kuliah', 'Tuition payment is due next month.', 'listening', 'intermediate'],
            ['volunteer', 'relawan', 'The club needs volunteers for the event.', 'listening', 'beginner'],
            ['workshop', 'lokakarya', 'The workshop teaches presentation skills.', 'listening', 'beginner'],
            ['adjective', 'kata sifat', 'An adjective describes a noun.', 'structure', 'beginner'],
            ['adverb', 'kata keterangan', 'An adverb can modify a verb or adjective.', 'structure', 'beginner'],
            ['appositive', 'aposisi', 'An appositive renames a noun.', 'structure', 'advanced'],
            ['article', 'kata sandang', 'An article appears before a noun.', 'structure', 'beginner'],
            ['comparative', 'perbandingan', 'A comparative form compares two things.', 'structure', 'intermediate'],
            ['complement', 'pelengkap', 'A complement completes the meaning of a verb.', 'structure', 'advanced'],
            ['compound', 'majemuk', 'A compound sentence joins two complete ideas.', 'structure', 'intermediate'],
            ['conditional', 'kalimat pengandaian', 'A conditional sentence expresses a possible result.', 'structure', 'intermediate'],
            ['dependent clause', 'klausa terikat', 'A dependent clause cannot stand alone.', 'structure', 'intermediate'],
            ['determiner', 'penentu nomina', 'A determiner comes before a noun phrase.', 'structure', 'advanced'],
            ['elliptical clause', 'klausa elipsis', 'An elliptical clause omits repeated words.', 'structure', 'advanced'],
            ['independent clause', 'klausa bebas', 'An independent clause can stand alone.', 'structure', 'intermediate'],
            ['inversion', 'pembalikan susunan', 'Inversion places the verb before the subject.', 'structure', 'advanced'],
            ['noun phrase', 'frasa nomina', 'A noun phrase can act as a subject or object.', 'structure', 'intermediate'],
            ['object', 'objek', 'The object receives the action of the verb.', 'structure', 'beginner'],
            ['participle', 'partisipel', 'A participle can reduce a clause.', 'structure', 'advanced'],
            ['plural', 'jamak', 'Plural nouns often end in s.', 'structure', 'beginner'],
            ['possessive', 'kepemilikan', 'A possessive form shows ownership.', 'structure', 'intermediate'],
            ['quantifier', 'penunjuk jumlah', 'A quantifier shows amount or number.', 'structure', 'intermediate'],
            ['reduced clause', 'klausa ringkas', 'A reduced clause removes repeated grammar parts.', 'structure', 'advanced'],
            ['relative clause', 'klausa relatif', 'A relative clause describes a noun.', 'structure', 'intermediate'],
            ['relative pronoun', 'kata ganti relatif', 'A relative pronoun introduces a relative clause.', 'structure', 'intermediate'],
            ['singular', 'tunggal', 'A singular subject needs a singular verb.', 'structure', 'beginner'],
            ['subordinate clause', 'klausa subordinat', 'A subordinate clause depends on another clause.', 'structure', 'advanced'],
            ['subject complement', 'pelengkap subjek', 'A subject complement describes the subject.', 'structure', 'advanced'],
            ['superlative', 'tingkat paling', 'A superlative form compares more than two things.', 'structure', 'intermediate'],
            ['transition word', 'kata transisi', 'A transition word shows the relationship between ideas.', 'structure', 'beginner'],
            ['verb phrase', 'frasa kerja', 'A verb phrase may include auxiliaries.', 'structure', 'intermediate'],
            ['word form', 'bentuk kata', 'Word form questions test noun, verb, adjective, and adverb forms.', 'structure', 'intermediate'],
            ['word order', 'urutan kata', 'Word order matters in English clauses.', 'structure', 'intermediate'],
            ['accordingly', 'sesuai dengan itu', 'The evidence was strong; accordingly, the claim was accepted.', 'transition', 'advanced'],
            ['additionally', 'selain itu', 'Additionally, the lecture gives a second example.', 'transition', 'intermediate'],
            ['afterward', 'sesudah itu', 'Afterward, the speaker summarizes the method.', 'transition', 'beginner'],
            ['as a result', 'sebagai hasilnya', 'The student reviewed daily; as a result, accuracy improved.', 'transition', 'beginner'],
            ['by contrast', 'sebaliknya', 'By contrast, the second theory explains the data.', 'transition', 'advanced'],
            ['for instance', 'misalnya', 'For instance, the writer mentions a classroom study.', 'transition', 'beginner'],
            ['in addition', 'selain itu', 'In addition, the paragraph includes a statistic.', 'transition', 'beginner'],
            ['in contrast', 'sebaliknya', 'In contrast, the second option is too narrow.', 'transition', 'intermediate'],
            ['in fact', 'sebenarnya', 'In fact, the lecture corrects the reading passage.', 'transition', 'intermediate'],
            ['in other words', 'dengan kata lain', 'In other words, the speaker restates the main point.', 'transition', 'beginner'],
            ['in particular', 'khususnya', 'In particular, the writer focuses on one cause.', 'transition', 'intermediate'],
            ['on the other hand', 'di sisi lain', 'On the other hand, the professor disagrees.', 'transition', 'intermediate'],
            ['prior to', 'sebelum', 'Prior to the experiment, students took a placement test.', 'transition', 'advanced'],
            ['similarly', 'demikian pula', 'Similarly, the next example supports the same idea.', 'transition', 'intermediate'],
            ['specifically', 'secara khusus', 'Specifically, the question asks for the second reason.', 'transition', 'intermediate'],
            ['subsequently', 'kemudian', 'Subsequently, the researchers repeated the test.', 'transition', 'advanced'],
            ['thus', 'dengan demikian', 'Thus, the evidence supports the answer.', 'transition', 'intermediate'],
            ['ultimately', 'pada akhirnya', 'Ultimately, the best answer matches the whole passage.', 'transition', 'advanced'],
            ['while', 'sementara', 'While the first idea is general, the second is specific.', 'transition', 'beginner'],
            ['yet', 'namun', 'The option looks correct, yet it changes the meaning.', 'transition', 'intermediate'],
            ['benchmark', 'tolok ukur', 'A benchmark helps measure improvement.', 'test-strategy', 'intermediate'],
            ['checklist', 'daftar cek', 'Use a checklist before submitting the session.', 'test-strategy', 'beginner'],
            ['confidence', 'kepercayaan diri', 'Confidence grows after accurate practice.', 'test-strategy', 'beginner'],
            ['correction', 'koreksi', 'Correction turns mistakes into review material.', 'test-strategy', 'beginner'],
            ['diagnosis', 'diagnosis', 'A diagnosis shows which skill needs attention.', 'test-strategy', 'advanced'],
            ['endurance', 'daya tahan', 'Endurance matters during a long simulation.', 'test-strategy', 'intermediate'],
            ['guessing', 'menebak', 'Guessing should be based on eliminated options.', 'test-strategy', 'beginner'],
            ['mastery', 'penguasaan', 'Mastery appears when accuracy stays high.', 'test-strategy', 'intermediate'],
            ['pacing', 'pengaturan kecepatan', 'Pacing keeps every section under control.', 'test-strategy', 'intermediate'],
            ['recall', 'mengingat kembali', 'Active recall strengthens vocabulary memory.', 'test-strategy', 'intermediate'],
            ['retention', 'daya ingat', 'Retention improves through spaced repetition.', 'test-strategy', 'intermediate'],
            ['streak', 'rangkaian latihan', 'A streak motivates learners to practice daily.', 'test-strategy', 'beginner'],
        ];

        return array_map(fn (array $word): array => [
            'word' => $word[0],
            'meaning' => $word[1],
            'usage_note' => $word[5] ?? $this->usageNoteFor($word[0], $word[1], $word[3]),
            'example_sentence' => $word[2],
            'example_translation' => $word[6] ?? $this->exampleTranslationFor($word[0], $word[1], $word[3]),
            'category' => $word[3],
            'difficulty' => $word[4],
            'skill_type' => SkillType::Vocabulary,
        ], $words);
    }

    private function usageNoteFor(string $word, string $meaning, string $category): string
    {
        $context = $this->vocabularyContextFor($category);

        return "Digunakan untuk memahami kata \"{$word}\" yang berarti \"{$meaning}\" dalam konteks {$context}.";
    }

    private function exampleTranslationFor(string $word, string $meaning, string $category): string
    {
        $context = $this->vocabularyContextFor($category);

        return "Terjemahan contoh: kalimat tersebut memakai \"{$word}\" sebagai \"{$meaning}\" dalam konteks {$context}.";
    }

    private function vocabularyContextFor(string $category): string
    {
        return match ($category) {
            'academic' => 'akademik dan ide umum TOEFL',
            'research' => 'riset, bukti, data, dan analisis',
            'transition' => 'penghubung ide antar kalimat',
            'reading' => 'bacaan dan strategi Reading',
            'listening' => 'percakapan, kuliah, dan Listening',
            'structure' => 'grammar, klausa, dan bentuk kata',
            'test-strategy' => 'strategi menjawab dan review TOEFL',
            default => 'latihan TOEFL',
        };
    }
}
