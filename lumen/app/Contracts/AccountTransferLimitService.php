<?php

namespace App\Contracts;

use App\Models\Account;

interface AccountTransferLimitService
{
    /**
     * Daily transfer limit per account.
     *
     * @const float
     */
    const DAILY_TRANSFER_LIMIT = 10000;

    /**
     * Register a new account transfer.
     *
     * @param \App\Models\Account $acount
     * @param float $amount
     *
     * @return self
     */
    public function registerTransfer($acount, $amount);

    /**
     * Determine whether or not the account daily transfer limit is reached or will be reached.
     *
     * @param \App\Models\Account $acount
     * @param float $amount
     *
     * @return bool
     */
    public function transferDailyLimitExceeded(Account $acount, float $amount): bool;
}
