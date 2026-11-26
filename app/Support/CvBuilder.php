<?php

namespace App\Support;

use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Education;
use App\Models\Experience;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Backs the step-by-step CV builder: saves the content a candidate edits there
 * (profile, experiences, educations) and builds unsaved models for the live preview.
 *
 * CV content is shared by all of a candidate's CVs; only title, template, colour
 * and objective belong to a single CV.
 */
class CvBuilder
{
    /**
     * Form values for the builder, from old input after a failed submit or from the database.
     *
     * @return array{name: string, headline: ?string, phone: ?string, address: ?string, date_of_birth: ?string, skills: array<int, string>, experiences: array<int, array<string, mixed>>, educations: array<int, array<string, mixed>>}
     */
    public static function formValues(User $user): array
    {
        $profile = $user->profile();

        return [
            'name' => old('name', $user->name),
            'headline' => old('headline', $profile->headline),
            'phone' => old('phone', $profile->phone),
            'address' => old('address', $profile->address),
            'date_of_birth' => old('date_of_birth', $profile->date_of_birth?->format('Y-m-d')),
            'skills' => array_values(old('skills', $profile->skills ?? [])),
            'experiences' => array_values(old('experiences', $user->experiences()->orderByDesc('start_date')->get()->map(fn (Experience $e): array => [
                'id' => $e->id,
                'company' => $e->company,
                'position' => $e->position,
                'start_date' => $e->start_date?->format('Y-m'),
                'end_date' => $e->end_date?->format('Y-m'),
                'is_current' => $e->is_current,
                'description' => $e->description,
            ])->all())),
            'educations' => array_values(old('educations', $user->educations()->orderByDesc('start_date')->get()->map(fn (Education $e): array => [
                'id' => $e->id,
                'school' => $e->school,
                'major' => $e->major,
                'degree' => $e->degree,
                'start_date' => $e->start_date?->format('Y-m'),
                'end_date' => $e->end_date?->format('Y-m'),
                'description' => $e->description,
            ])->all())),
        ];
    }

    /**
     * Persists a validated builder submission. Rows the candidate removed are deleted.
     *
     * @param  array<string, mixed>  $data
     */
    public static function save(User $user, Cv $cv, array $data): void
    {
        DB::transaction(function () use ($user, $cv, $data): void {
            $cv->fill(collect($data)->only(['title', 'template', 'accent_color', 'objective'])->all());
            $cv->save();

            if (! array_key_exists('name', $data)) {
                return; // Legacy form without content fields.
            }

            $user->forceFill(['name' => $data['name']])->save();
            $user->profile()->update([
                'headline' => $data['headline'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'skills' => array_values($data['skills'] ?? []),
            ]);

            self::syncRows($user->experiences(), $data['experiences'] ?? [], fn (array $row): array => [
                'company' => $row['company'],
                'position' => $row['position'],
                'start_date' => self::month($row['start_date']),
                'is_current' => (bool) ($row['is_current'] ?? false),
                'end_date' => ! empty($row['is_current']) ? null : self::month($row['end_date'] ?? null),
                'description' => $row['description'] ?? null,
            ]);

            self::syncRows($user->educations(), $data['educations'] ?? [], fn (array $row): array => [
                'school' => $row['school'],
                'major' => $row['major'] ?? null,
                'degree' => $row['degree'] ?? null,
                'start_date' => self::month($row['start_date']),
                'end_date' => self::month($row['end_date'] ?? null),
                'description' => $row['description'] ?? null,
            ]);
        });
    }

    /**
     * Unsaved models from raw (unvalidated) builder input, shaped like the CV templates expect.
     *
     * @param  array<string, mixed>  $input
     * @return array{cv: Cv, user: User, profile: CandidateProfile, experiences: Collection<int, Experience>, educations: Collection<int, Education>}
     */
    public static function previewData(User $authUser, array $input): array
    {
        $text = fn (mixed $value, int $max = 3000): ?string => is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, $max) : null;
        $templates = config('recruitment.cv.templates');

        $cv = new Cv([
            'title' => $text($input['title'] ?? null, 100) ?? 'CV',
            'template' => isset($templates[$input['template'] ?? '']) ? $input['template'] : 'ats',
            'accent_color' => in_array($input['accent_color'] ?? null, config('recruitment.cv.accent_colors'), true) ? $input['accent_color'] : '#2563eb',
            'objective' => $text($input['objective'] ?? null, 1000),
        ]);

        $user = new User(['name' => $text($input['name'] ?? null, 255) ?? $authUser->name, 'email' => $authUser->email]);

        $profile = new CandidateProfile([
            'headline' => $text($input['headline'] ?? null, 150),
            'phone' => $text($input['phone'] ?? null, 20),
            'address' => $text($input['address'] ?? null, 255),
            'date_of_birth' => self::date($input['date_of_birth'] ?? null),
            'skills' => collect(is_array($input['skills'] ?? null) ? $input['skills'] : [])->map(fn ($s) => $text($s, 50))->filter()->take(30)->values()->all(),
        ] + collect($authUser->profile()->only(['gender', 'summary', 'desired_salary_min', 'desired_salary_max', 'desired_locations']))->all());

        $rows = fn (string $key): Collection => collect(is_array($input[$key] ?? null) ? $input[$key] : [])->filter(fn ($row) => is_array($row))->take(20);

        // A row still being typed has no start month yet: show it as "this month" so the preview keeps working.
        $experiences = $rows('experiences')->map(fn (array $row): Experience => new Experience([
            'company' => $text($row['company'] ?? null, 255) ?? 'Tên công ty',
            'position' => $text($row['position'] ?? null, 255) ?? 'Vị trí',
            'start_date' => self::month($row['start_date'] ?? null) ?? now()->startOfMonth()->toDateString(),
            'is_current' => ! empty($row['is_current']),
            'end_date' => empty($row['is_current']) ? self::month($row['end_date'] ?? null) : null,
            'description' => $text($row['description'] ?? null),
        ]))->values();

        $educations = $rows('educations')->map(fn (array $row): Education => new Education([
            'school' => $text($row['school'] ?? null, 255) ?? 'Tên trường',
            'major' => $text($row['major'] ?? null, 255),
            'degree' => in_array($row['degree'] ?? null, config('recruitment.degrees'), true) ? $row['degree'] : null,
            'start_date' => self::month($row['start_date'] ?? null) ?? now()->startOfMonth()->toDateString(),
            'end_date' => self::month($row['end_date'] ?? null),
            'description' => $text($row['description'] ?? null, 2000),
        ]))->values();

        return compact('cv', 'user', 'profile', 'experiences', 'educations');
    }

    /**
     * Updates rows whose id belongs to the user, creates new ones and deletes the rest.
     *
     * @param  HasMany<Model, User>  $relation
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function syncRows($relation, array $rows, callable $attributes): void
    {
        $existing = $relation->get()->keyBy('id');
        $kept = [];

        foreach ($rows as $row) {
            $model = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            if ($model) {
                $model->update($attributes($row));
            } else {
                $model = $relation->create($attributes($row));
            }

            $kept[] = $model->id;
        }

        $relation->whereNotIn('id', $kept)->delete();
    }

    /** "2024-05" → "2024-05-01"; anything else → null. */
    private static function month(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) ? $value.'-01' : null;
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
