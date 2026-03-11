<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Entities;

use DateTimeImmutable;
use Src\Shared\Core\Errors\ValidationFailed;

final class IdentityUser
{
    private function __construct(
        public readonly int $id,
        public readonly string $email,
        public string $name,
        public ?string $hashedPassword,
        public bool $active,
        public ?DateTimeImmutable $emailVerifiedAt,
        public ?string $googleId,
        public ?string $phone,
        public ?string $country,
        public bool $passwordResetRequired,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function create(string $email, string $name, string $hashedPassword): self
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
            phone: null,
            country: null,
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

    public function updateProfile(?string $name, ?string $email): void
    {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($email !== null) {
            // Note: email is readonly, so this is intentionally limited
        }
        $this->updatedAt = new DateTimeImmutable();
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
