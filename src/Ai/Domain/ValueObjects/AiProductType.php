<?php

declare(strict_types=1);

namespace Src\Ai\Domain\ValueObjects;

enum AiProductType: string
{
    case GOOGLE_REVIEW = 'google_review';
    case INSTAGRAM_CONTENT = 'instagram_content';
}
