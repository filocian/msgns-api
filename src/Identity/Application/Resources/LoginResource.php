<?php
declare(strict_types=1);
namespace Src\Identity\Application\Resources;
final readonly class LoginResource
{
    public function __construct(
        public UserResource $user,
    ) {}
}
