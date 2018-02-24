<?php

namespace App\Contracts;

use App\Models\Account;
use Illuminate\Support\Collection;

interface AccountService
{
    /**
     * Transfer fee.
     *
     * @const float
     */
    const TRANSFER_FEE = 100;

    /**
     * Get all bank accounts.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAll(): Collection;

    /**
     * Create a new bank account.
     *
     * @param \App\Models\Account $account
     *
     * @return bool
     */
    public function create(Account $account): bool;

    /**
     * Get bank account by its number.
     *
     * @param string $number
     *
     * @return \App\Models\Account|null
     */
    public function getByNumber(string $number);

    /**
     * Close bank account.
     *
     * @param \App\Models\Account $account
     *
     * @return bool
     */
    public function close(Account $account): bool;

    /**
     * Deposit amount into bank account.
     *
     * @param \App\Models\Account $account
     * @param float $amount
     *
     * @return bool
     */
    public function deposit(Account $account, float $amount): bool;

    /**
     * Withdrawal amount from bank account.
     *
     * @param \App\Models\Account $account
     * @param float $amount
     *
     * @return bool
     */
    public function withdrawal(Account $account, float $amount): bool;

    /**
     * Transfer amount to another bank account.
     *
     * @param \App\Models\Account $fromAccount
     * @param \App\Models\Account $toAccount
     * @param float $amount
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function transfer(Account $fromAccount, Account $toAccount, float $amount): bool;

    /**
     * Calculate transfer fee.
     *
     * @param \App\Models\Account $fromAccount
     * @param \App\Models\Account $toAccount
     *
     * @return float
     */
    public function calculateTransferFee(Account $fromAccount, Account $toAccount): float;
}
