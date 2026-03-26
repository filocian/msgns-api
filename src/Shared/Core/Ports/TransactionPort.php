<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

interface TransactionPort
{
    /**
     * Execute the given callable inside a database transaction.
     * On exception, the transaction is rolled back and the exception re-thrown.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
