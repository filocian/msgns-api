<?php

namespace App\Providers;

use App\Repositories\NFCRepository;
use App\Usecases\NFC\ActivateNFCUseCase;
use Illuminate\Support\ServiceProvider;

class NFCServicesProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NFCRepository::class);
        $this->app->singleton(ActivateNFCUseCase::class);
    }
}
