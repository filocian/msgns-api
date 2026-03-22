<?php

declare(strict_types=1);

namespace Src\Identity\Application\Resources;

/**
 * Per-user result for bulk operations.
 * Status vocabulary: updated | unchanged | failed.
 */
final readonly class BulkUserResult
{
    /**
     * @param string $status One of: updated, unchanged, failed
     * @param string|null $code Machine-readable error code (for failed status)
     * @param string|null $message Human-readable message
     */
    public function __construct(
        public int $userId,
        public string $status,
        public ?string $code = null,
        public ?string $message = null,
    ) {
        // Validate status values
        if (!in_array($status, ['updated', 'unchanged', 'failed'], true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}. Must be one of: updated, unchanged, failed");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'userId' => $this->userId,
            'status' => $this->status,
        ];

        if ($this->code !== null) {
            $result['code'] = $this->code;
        }

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        return $result;
    }
}
