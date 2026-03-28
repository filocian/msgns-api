<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Database;

use Illuminate\Support\Facades\DB;
use Src\Shared\Core\Ports\TransactionPort;

final class LaravelTransaction implements TransactionPort
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        return DB::transaction(static function () use ($callback): mixed {
            return $callback();
        });
    }
}
