<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 'role' and 'is_active' are intentionally NOT fillable to prevent mass-assignment privilege escalation.
#[Fillable(['name', 'email', 'password', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasOne<CandidateProfile, $this>
     */
    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    /**
     * @return HasOne<Company, $this>
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function hasVerifiedCompany(): bool
    {
        return $this->company?->isVerified() ?? false;
    }

    /**
     * OAuth identities (Google, ...) linked to this user.
     *
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Education, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->orderByDesc('start_date');
    }

    /**
     * @return HasMany<Experience, $this>
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderByDesc('is_current')->orderByDesc('start_date');
    }

    /**
     * @return HasMany<CommunityPost, $this>
     */
    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    /**
     * @return HasMany<Cv, $this>
     */
    public function cvs(): HasMany
    {
        return $this->hasMany(Cv::class);
    }

    /**
     * Job posts created by this employer.
     *
     * @return HasMany<JobPost, $this>
     */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    /**
     * Applications submitted by this candidate.
     *
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return BelongsToMany<JobPost, $this>
     */
    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(JobPost::class, 'saved_jobs')->withTimestamps();
    }

    /**
     * @return HasMany<MockInterview, $this>
     */
    public function mockInterviews(): HasMany
    {
        return $this->hasMany(MockInterview::class);
    }

    /**
     * @return HasOne<VideoCv, $this>
     */
    public function videoCv(): HasOne
    {
        return $this->hasOne(VideoCv::class);
    }

    /**
     * @return HasMany<JobAlert, $this>
     */
    public function jobAlerts(): HasMany
    {
        return $this->hasMany(JobAlert::class);
    }

    /**
     * Returns the candidate profile, creating an empty one on first access.
     */
    public function profile(): CandidateProfile
    {
        if ($this->candidateProfile === null) {
            $this->setRelation('candidateProfile', $this->candidateProfile()->create());
        }

        return $this->candidateProfile;
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $role = $role instanceof UserRole ? $role : UserRole::tryFrom($role);
            if ($role !== null && $this->role === $role) {
                return true;
            }
        }

        return false;
    }

    public function isCandidate(): bool
    {
        return $this->role === UserRole::Candidate;
    }

    public function isEmployer(): bool
    {
        return $this->role === UserRole::Employer;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function homeUrl(): string
    {
        return route($this->role->homeRoute());
    }
}
