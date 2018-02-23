<?php

namespace App\Contracts;

use App\Models\Account;

interface AccountTransfertApprovalService
{
    /**
     * Approve bank account transfer.
     *
     * @param \App\Models\Account $fromAcount
     * @param \App\Models\Account $toAccount
     * @param float $amount
     *
     * @return bool
     */
    public function approveTransfer(Account $fromAcount, Account $toAccount, float $amount): bool;
}
