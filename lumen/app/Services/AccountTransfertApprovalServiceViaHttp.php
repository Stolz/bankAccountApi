<?php

namespace App\Services;

use App\Contracts\AccountTransfertApprovalService;
use App\Models\Account;

class AccountTransfertApprovalServiceViaHttp implements AccountTransfertApprovalService
{
    /**
     * URL for requesting bank account transfer approval.
     *
     * @var string
     */
    protected $url = 'http://handy.travel/test/success.json';

    /**
     * Approve bank account transfer.
     *
     * @param \App\Models\Account $fromAcount
     * @param \App\Models\Account $toAccount
     * @param float $amount
     *
     * @return bool
     */
    public function approveTransfer(Account $fromAcount, Account $toAccount, float $amount): bool
    {
        $response = json_decode(@file_get_contents($this->url));

        return ! empty($response->status) and $response->status === 'success';
    }
}
