<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    public function examSimulations(): HasMany
    {
        return $this->hasMany(ExamSimulation::class);
    }

    public function mistakeJournals(): HasMany
    {
        return $this->hasMany(MistakeJournal::class);
    }

    public function studyLogs(): HasMany
    {
        return $this->hasMany(StudyLog::class);
    }

    public function userVocabularies(): HasMany
    {
        return $this->hasMany(UserVocabulary::class);
    }

    public function userVocabularyReviews(): HasMany
    {
        return $this->hasMany(UserVocabularyReview::class);
    }

    public function speakingAttempts(): HasMany
    {
        return $this->hasMany(SpeakingAttempt::class);
    }

    public function writingSubmissions(): HasMany
    {
        return $this->hasMany(WritingSubmission::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
