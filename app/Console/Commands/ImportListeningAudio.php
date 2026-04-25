<?php

namespace App\Console\Commands;

use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Question;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

#[Signature('linguapath:import-listening-audio {manifest? : JSON or CSV manifest path}')]
#[Description('Import legal listening audio files from a manifest and attach them to listening questions.')]
class ImportListeningAudio extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $manifestPath = $this->manifestPath();
        $disk = config('linguapath.audio_storage_disk', 'public');

        if (! File::exists($manifestPath)) {
            $this->error("Manifest not found: {$manifestPath}");

            return self::FAILURE;
        }

        try {
            $rows = $this->readManifest($manifestPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $audioDirectory = dirname($manifestPath).DIRECTORY_SEPARATOR.'audio';
        $adminId = User::query()->where('role', 'admin')->value('id');
        $result = [
            'imported' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($rows, $audioDirectory, $disk, $adminId, &$result): void {
            foreach ($rows as $index => $row) {
                $line = $index + 1;
                $errors = $this->rowErrors($row, $audioDirectory);

                if ($errors !== []) {
                    $result['failed']++;
                    $result['errors'][] = "Row {$line}: ".implode(', ', $errors);

                    continue;
                }

                $question = Question::query()
                    ->with(['audioAsset', 'options'])
                    ->find((int) $row['question_id']);

                $audioPath = $audioDirectory.DIRECTORY_SEPARATOR.$row['audio_filename'];
                $storedPath = 'listening-audio/imported/question-'.$question->id.'/'.basename($row['audio_filename']);
                Storage::disk($disk)->put($storedPath, File::get($audioPath));

                $asset = $question->audioAsset && $question->audioAsset->source === 'demo-import'
                    ? $question->audioAsset
                    : new AudioAsset;

                $approvedAt = filled($row['approved_at'] ?? null)
                    ? Carbon::parse($row['approved_at'])
                    : now();

                $asset->fill([
                    'title' => $row['title'],
                    'audio_url' => Storage::disk($disk)->url($storedPath),
                    'file_path' => $storedPath,
                    'mime_type' => File::mimeType($audioPath) ?: 'audio/mpeg',
                    'file_size' => File::size($audioPath),
                    'uploaded_by' => $adminId,
                    'is_real_audio' => true,
                    'playback_limit_exam' => 1,
                    'status' => 'ready',
                    'transcript' => $row['transcript'],
                    'transcript_reviewed_at' => now(),
                    'approved_at' => $approvedAt,
                    'approved_by' => $adminId,
                    'review_notes' => 'Imported from listening audio manifest.',
                    'speaker_notes' => $row['speaker_notes'] ?? null,
                    'duration_seconds' => (int) $row['duration_seconds'],
                    'accent' => $row['speaker_accent'] ?? 'american',
                    'speed' => (float) ($row['speed'] ?? 1.0),
                    'source' => 'demo-import',
                ]);
                $asset->save();

                $question->update([
                    'audio_asset_id' => $asset->id,
                    'audio_url' => $asset->playbackUrl(),
                    'transcript' => $row['transcript'],
                ]);

                $result['imported']++;
            }
        });

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        $this->info("Imported {$result['imported']} listening audio assets. Failed {$result['failed']} rows.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function manifestPath(): string
    {
        $path = $this->argument('manifest')
            ?: config('linguapath.demo_audio_import_path', 'storage/app/imports/listening-audio/manifest.json');

        return str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : base_path($path);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readManifest(string $manifestPath): array
    {
        return match (strtolower(pathinfo($manifestPath, PATHINFO_EXTENSION))) {
            'json' => $this->readJsonManifest($manifestPath),
            'csv' => $this->readCsvManifest($manifestPath),
            default => throw new RuntimeException('Manifest must be a JSON or CSV file.'),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJsonManifest(string $manifestPath): array
    {
        $decoded = json_decode(File::get($manifestPath), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Manifest JSON is invalid.');
        }

        return array_values($decoded);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readCsvManifest(string $manifestPath): array
    {
        $handle = fopen($manifestPath, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to read CSV manifest.');
        }

        $headers = fgetcsv($handle);
        $rows = [];

        if (! is_array($headers)) {
            fclose($handle);

            return [];
        }

        while (($values = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $values);
        }

        fclose($handle);

        return array_values(array_filter($rows));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function rowErrors(array $row, string $audioDirectory): array
    {
        $errors = [];
        $required = [
            'question_id',
            'audio_filename',
            'title',
            'transcript',
            'duration_seconds',
            'is_real_audio',
            'transcript_reviewed',
            'quality_status',
        ];

        foreach ($required as $field) {
            if (blank($row[$field] ?? null)) {
                $errors[] = "{$field} is required";
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        $question = Question::query()
            ->withCount([
                'options',
                'options as correct_options_count' => fn ($query) => $query->where('is_correct', true),
            ])
            ->find((int) $row['question_id']);

        if (! $question) {
            $errors[] = 'question_id does not exist';
        } elseif ($question->section_type !== SkillType::Listening) {
            $errors[] = 'question is not a listening question';
        } else {
            if ($question->options_count !== 4) {
                $errors[] = 'question must have exactly 4 options';
            }

            if ($question->correct_options_count !== 1) {
                $errors[] = 'question must have exactly one correct option';
            }
        }

        if (! filter_var($row['is_real_audio'], FILTER_VALIDATE_BOOL)) {
            $errors[] = 'is_real_audio must be true';
        }

        if (! filter_var($row['transcript_reviewed'], FILTER_VALIDATE_BOOL)) {
            $errors[] = 'transcript_reviewed must be true';
        }

        if (! in_array($row['quality_status'], ['approved', 'ready'], true)) {
            $errors[] = 'quality_status must be approved or ready';
        }

        $audioPath = $audioDirectory.DIRECTORY_SEPARATOR.$row['audio_filename'];

        if (! File::exists($audioPath)) {
            $errors[] = "audio file not found: {$row['audio_filename']}";
        }

        return $errors;
    }
}
