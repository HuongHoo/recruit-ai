<?php

namespace App\Enums;

enum UserRole: string
{
    case Candidate = 'candidate';
    case Employer = 'employer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'Ứng viên',
            self::Employer => 'Nhà tuyển dụng',
            self::Admin => 'Quản trị viên',
        };
    }

    /** Route name of the home page for this role. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Candidate => 'home', // Candidates browse the public site; their pages live in the avatar menu.
            self::Employer => 'employer.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }

    /** Roles a visitor may pick when self-registering (never Admin). */
    public static function registrable(): array
    {
        return [self::Candidate, self::Employer];
    }
}
