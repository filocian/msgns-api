<?php
declare(strict_types=1);
namespace Src\Identity\Application\Resources;
final readonly class ImpersonationResource
{
    public function __construct(
        public UserResource $user,
        public string $action, // 'started' | 'stopped'
    ) {}
}
