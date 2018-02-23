<?php

namespace App\Services;

use App\Contracts\AccountTransferLimitService;
use App\Models\Account;
use Carbon\Carbon;

class AccountTransferLimitServiceViaCache implements AccountTransferLimitService
{
    /**
     * Instance of the service used to interact with cache.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * Class constructor.
     *
     * Inject dependencies.
     *
     * @param \Illuminate\Contracts\Cache\Factory $cache
     *
     * @return void
     */
    public function __construct(\Illuminate\Contracts\Cache\Factory $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get account daily transfer limit cache key.
     *
     * @param \App\Models\Account $acount
     *
     * @return string
     */
    protected function getAccountDailyTransferLimitCacheKey(Account $acount): string
    {
        return sprintf('account-%s-day-%s-trasfer-amount', $acount->getNumber(), Carbon::now()->format('Y-m-d'));
    }

    /**
     * Register a new account transfer.
     *
     * @param \App\Models\Account $acount
     * @param float $amount
     *
     * @return self
     */
    public function registerTransfer($acount, $amount)
    {
        $key = $this->getAccountDailyTransferLimitCacheKey($acount);
        $value = $amount + $this->cache->get($key, 0);
        $expiresAt = Carbon::now()->endOfDay();

        $this->cache->put($key, $value, $expiresAt);

        return $this;
    }

    /**
     * Determine whether or not the account daily transfer limit is reached or will be reached.
     *
     * @param \App\Models\Account $acount
     * @param float $amount
     *
     * @return bool
     */
    public function transferDailyLimitExceeded(Account $acount, float $amount): bool
    {
        $key = $this->getAccountDailyTransferLimitCacheKey($acount);
        $value = $amount + $this->cache->get($key, 0);

        return $value > static::DAILY_TRANSFER_LIMIT;
    }
}
