<?php
declare(strict_types=1);
namespace Src\Identity\Application\Resources;
final readonly class AdminUserResource
{
    /** @param string[] $roles */
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public bool $active,
        public bool $emailVerified,
        public ?string $phone,
        public ?string $country,
        public bool $hasGoogleLogin,
        public bool $passwordResetRequired,
        public array $roles,
        public ?string $defaultLocale,
        public string $createdAt,
        public string $updatedAt,
        public ?string $pendingEmail = null,
    ) {}
}
