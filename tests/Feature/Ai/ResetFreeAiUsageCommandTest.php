<?php

declare(strict_types=1);

describe('ResetFreeAiUsageCommand (ai:reset-free-usage)', function (): void {

    it('command runs and exits successfully', function (): void {
        $this->artisan('ai:reset-free-usage')->assertExitCode(0);
    });

    it('deletes all free-tier AiUsageRecord rows when model is available', function (): void {
        // Pending BE-5: AiUsageRecord model not yet available.
    })->skip('Pending BE-5: AiUsageRecord model not yet available.');

    it('leaves classic and prepaid records untouched', function (): void {
        // Pending BE-5: AiUsageRecord model not yet available.
    })->skip('Pending BE-5: AiUsageRecord model not yet available.');

    it('completes without error when no free-tier records exist', function (): void {
        // Pending BE-5: AiUsageRecord model not yet available.
    })->skip('Pending BE-5: AiUsageRecord model not yet available.');
});
