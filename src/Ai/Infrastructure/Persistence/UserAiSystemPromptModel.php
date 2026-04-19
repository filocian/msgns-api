<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

final class UserAiSystemPromptModel extends Model
{
    protected $table = 'user_ai_system_prompts';

    /** @var list<string> */
    protected $fillable = ['user_id', 'product_type', 'prompt_text'];
}
