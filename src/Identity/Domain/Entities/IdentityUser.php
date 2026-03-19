<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Entities;

use DateTimeImmutable;
use Src\Shared\Core\Errors\ValidationFailed;

final class IdentityUser
{
    private function __construct(
        public readonly int $id,
        public string $email,
        public string $name,
        public ?string $hashedPassword,
        public bool $active,
        public ?DateTimeImmutable $emailVerifiedAt,
        public ?string $googleId,
        public ?string $phone,
        public ?string $country,
        public ?string $defaultLocale,
        public ?string $userAgent,
        public bool $passwordResetRequired,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $email,
        string $name,
        string $hashedPassword,
        ?string $phone = null,
        ?string $country = null,
        ?string $defaultLocale = null,
        ?string $userAgent = null,
    ): self
    {
        $now = new DateTimeImmutable();
        return new self(
            id: 0,
            email: $email,
            name: $name,
            hashedPassword: $hashedPassword,
            active: true,
            emailVerifiedAt: null,
            googleId: null,
            phone: $phone,
            country: $country,
            defaultLocale: $defaultLocale,
            userAgent: $userAgent,
            passwordResetRequired: false,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromGoogle(string $email, string $name, string $googleId): self
    {
        $now = new DateTimeImmutable();
        return new self(
            id: 0,
            email: $email,
            name: $name,
            hashedPassword: null,
            active: true,
            emailVerifiedAt: $now,
            googleId: $googleId,
            phone: null,
            country: null,
            defaultLocale: null,
            userAgent: null,
            passwordResetRequired: false,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        int $id,
        string $email,
        string $name,
        ?string $hashedPassword,
        bool $active,
        ?DateTimeImmutable $emailVerifiedAt,
        ?string $googleId,
        ?string $phone,
        ?string $country,
        bool $passwordResetRequired,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?string $defaultLocale = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            id: $id,
            email: $email,
            name: $name,
            hashedPassword: $hashedPassword,
            active: $active,
            emailVerifiedAt: $emailVerifiedAt,
            googleId: $googleId,
            phone: $phone,
            country: $country,
            defaultLocale: $defaultLocale,
            userAgent: $userAgent,
            passwordResetRequired: $passwordResetRequired,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function verifyEmail(): void
    {
        if ($this->emailVerifiedAt !== null) {
            throw ValidationFailed::because('email_already_verified');
        }
        $this->emailVerifiedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        if (!$this->active) {
            throw ValidationFailed::because('user_already_inactive');
        }
        $this->active = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        if ($this->active) {
            throw ValidationFailed::because('user_already_active');
        }
        $this->active = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateProfile(
        ?string $name = null,
        ?string $phone = null,
        ?string $country = null,
        ?string $defaultLocale = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($phone !== null) {
            $this->phone = $phone;
        }
        if ($country !== null) {
            $this->country = $country;
        }
        if ($defaultLocale !== null) {
            $this->defaultLocale = $defaultLocale;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function adminUpdateProfile(
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $country = null,
        ?string $defaultLocale = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($email !== null) {
            $this->changeEmail($email);
        }
        if ($phone !== null) {
            $this->phone = $phone;
        }
        if ($country !== null) {
            $this->country = $country;
        }
        if ($defaultLocale !== null) {
            $this->defaultLocale = $defaultLocale;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @param callable(string, string): bool $verifyCurrentPassword
     */
    public function changePassword(
        string $currentPlaintext,
        string $newHashedPassword,
        callable $verifyCurrentPassword,
    ): void {
        if ($this->hashedPassword === null) {
            throw ValidationFailed::because('no_password_set');
        }
        if (!$verifyCurrentPassword($currentPlaintext, $this->hashedPassword)) {
            throw ValidationFailed::because('invalid_current_password');
        }
        $this->hashedPassword = $newHashedPassword;
        $this->passwordResetRequired = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function adminSetPassword(string $hashedPassword): void
    {
        $this->hashedPassword = $hashedPassword;
        $this->passwordResetRequired = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function forceVerifyEmail(): void
    {
        if ($this->emailVerifiedAt === null) {
            $this->emailVerifiedAt = new DateTimeImmutable();
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function resetPassword(string $hashedPassword): void
    {
        $this->hashedPassword = $hashedPassword;
        $this->passwordResetRequired = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function hasPassword(): bool
    {
        return $this->hashedPassword !== null;
    }

    public function isGoogleUser(): bool
    {
        return $this->googleId !== null;
    }
}
