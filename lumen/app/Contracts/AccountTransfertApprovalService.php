<?php

namespace App\Contracts;

use App\Models\Account;

interface AccountTransfertApprovalService
{
    /**
     * Approve bank account transfer.
     *
     * @param \App\Models\Account $fromAccount
     * @param \App\Models\Account $toAccount
     * @param float $amount
     *
     * @return bool
     */
    public function approveTransfer(Account $fromAccount, Account $toAccount, float $amount): bool;
}
