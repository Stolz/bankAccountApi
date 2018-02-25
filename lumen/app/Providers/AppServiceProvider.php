<?php

namespace App\Providers;

use App\Contracts\AccountService;
use App\Contracts\AccountTransferLimitService;
use App\Contracts\AccountTransfertApprovalService;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register service to check bank account transfer limits
        $this->app->bind(AccountTransferLimitService::class, function () {
            $cacheManager = app('cache');

            return new \App\Services\AccountTransferLimitServiceViaCache($cacheManager);
        });

        // Register service to request bank account transfer approvals
        $this->app->bind(AccountTransfertApprovalService::class, function () {
            return new \App\Services\AccountTransfertApprovalServiceViaHttp();
        });

        // Register service to interact with bank accounts
        $this->app->bind(AccountService::class, function () {
            $limitService = app(AccountTransferLimitService::class);
            $approvalService = app(AccountTransfertApprovalService::class);
            $databaseService = app(ConnectionResolverInterface::class);

            return new \App\Services\AccountServiceViaSql($limitService, $approvalService, $databaseService);
        });
    }
}
