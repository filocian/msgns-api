<?php

declare(strict_types=1);

namespace Src\Identity\Application\Resources;

final readonly class AdminUserExportResource
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $country,
        public ?string $defaultLocale,
        public string $active,
        public string $emailVerified,
        public string $roles,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * CSV column headers (order matches toCsvRow).
     *
     * @return list<string>
     */
    public static function csvHeaders(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone',
            'country',
            'default_locale',
            'active',
            'email_verified',
            'roles',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return list<string>
     */
    public function toCsvRow(): array
    {
        return [
            (string) $this->id,
            self::sanitize($this->name),
            self::sanitize($this->email),
            self::sanitize($this->phone ?? ''),
            self::sanitize($this->country ?? ''),
            self::sanitize($this->defaultLocale ?? ''),
            $this->active,
            $this->emailVerified,
            self::sanitize($this->roles),
            $this->createdAt,
            $this->updatedAt,
        ];
    }

    /**
     * Prevent CSV injection by prefixing dangerous leading chars with a single quote.
     */
    private static function sanitize(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
