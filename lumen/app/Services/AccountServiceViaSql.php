<?php

namespace App\Services;

use App\Contracts\AccountService;
use App\Contracts\AccountTransferLimitService;
use App\Contracts\AccountTransfertApprovalService;
use App\Models\Account;
use Illuminate\Support\Collection;
use Webpatser\Uuid\Uuid;

class AccountServiceViaSql implements AccountService
{
    /**
     * Instance of the service used to check bank account transfer limits.
     *
     * @var \App\Contracts\AccountTransferLimitService
     */
    protected $limitService;

    /**
     * Instance of the service used to request bank account transfer approvals.
     *
     * @var \App\Contracts\AccountTransfertApprovalService
     */
    protected $approvalService;

    /**
     * Class constructor.
     *
     * Inject dependencies.
     *
     * @param \App\Contracts\AccountTransferLimitService $limitService
     * @param \App\Contracts\AccountTransfertApprovalService $approvalService
     *
     * @return void
     */
    public function __construct(AccountTransferLimitService $limitService, AccountTransfertApprovalService $approvalService)
    {
        $this->limitService = $limitService;
        $this->approvalService = $approvalService;
    }

    /**
     * Begin a new query on the model table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function query(): \Illuminate\Database\Query\Builder
    {
        return clone app('db')->table('accounts');
    }

    /**
     * Convert database record into domain model.
     *
     * @param  \StdClass $attributes
     * @return \App\Models\Account
     */
    protected function recordToModel(\StdClass $record)
    {
        return Account::make((array) $record);
    }

    /**
     * Convert domain model into database record.
     *
     * @param \App\Models\Account $account
     * @return array
     */
    protected function modelToRecord(Account $account): array
    {
        return $account->toArray();
    }

    /**
     * Get all bank accounts.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAll(): Collection
    {
        return $this->query()->get()->transform(function ($record) {
            return $this->recordToModel($record);
        });
    }

    /**
     * Create a new bank account.
     *
     * @param \App\Models\Account $account
     *
     * @return bool
     */
    public function create(Account $account): bool
    {
        $record = $this->modelToRecord($account);
        $record['number'] = Uuid::generate(4);
        $record['closed'] = false;

        if (! $this->query()->insert($record)) {
            return false;
        }

        $account->setNumber($record['number'])->setClosed(false);

        return true;
    }

    /**
     * Get bank account by its number.
     *
     * @param string $number
     *
     * @return \App\Models\Account|null
     */
    public function getByNumber(string $number)
    {
        $found = $this->query()->whereNumber($number)->first();

        return ($found) ? $this->recordToModel($found) : null;
    }

    /**
     * Close bank account.
     *
     * @param \App\Models\Account $account
     *
     * @return bool
     */
    public function close(Account $account): bool
    {
        $conditions = [
            'number' => $account->getNumber(),
            'closed' => false, // Ensure account is open
            'balance' => 0.0, // Ensure account is liquidated
        ];

        $closed = (bool) $this->query()->where($conditions)->limit(1)->update(['closed' => true]);
        $account->setClosed($closed);

        return $closed;
    }

    /**
     * Deposit amount into bank account.
     *
     * @param \App\Models\Account $account
     * @param float $amount
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deposit(Account $account, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        $conditions = [
            'number' => $account->getNumber(),
            'closed' => false, // Ensure account is open
        ];

        return (bool) $this->query()->where($conditions)->limit(1)->increment('balance', $amount);
    }

    /**
     * Withdrawal amount from bank account.
     *
     * @param \App\Models\Account $account
     * @param float $amount
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function withdrawal(Account $account, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        $conditions = [
            'number' => $account->getNumber(),
            'closed' => false, // Ensure account is open
            ['balance', '>=', $amount], // Ensure account has enough balance
        ];

        return (bool) $this->query()->where($conditions)->limit(1)->decrement('balance', $amount);
    }

    /**
     * Transfer amount to another bank account.
     *
     * @param \App\Models\Account $fromAccount
     * @param \App\Models\Account $toAccount
     * @param float $amount
     *
     * @return bool
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function transfer(Account $fromAccount, Account $toAccount, float $amount): bool
    {
        // Validate transfer parameters
        $this->validateTransfer($fromAccount, $toAccount, $amount);

        // Calculate transfer fee
        $fee = $this->calculateTransferFee($fromAccount, $toAccount);

        $db = app('db');
        try {
            // Start transaction
            $db->beginTransaction();

            // Make withdrawal
            if (! $this->withdrawal($fromAccount, $amount + $fee)) {
                throw new \RuntimeException('Unable to withdraw amount from source account');
            }

            // Make deposit
            if (! $this->deposit($toAccount, $amount)) {
                throw new \RuntimeException('Unable to deposit amount into destination account');
            }

            // Confirm transaction
            $db->commit();

            // Register transfer for accounting in daily limit
            $this->limitService->registerTransfer($fromAccount, $amount);

            return true;
        } catch (\Exception $exception) {
            // Roll back transaction
            $db->rollBack();
            throw $exception;
        }
    }

    /**
     * Calculate transfer fee.
     *
     * @param \App\Models\Account $fromAccount
     * @param \App\Models\Account $toAccount
     *
     * @return float
     */
    public function calculateTransferFee(Account $fromAccount, Account $toAccount): float
    {
        if ($fromAccount->getOwner() === $toAccount->getOwner()) {
            return 0.0;
        }

        return static::TRANSFER_FEE;
    }

    /**
     * Validate transfer.
     *
     * @param \App\Models\Account $fromAccount
     * @param \App\Models\Account $toAccount
     * @param float $amount
     *
     * @return self
     * @throws \RuntimeException|\InvalidArgumentException
     */
    protected function validateTransfer(Account $fromAccount, Account $toAccount, float $amount)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        if ($fromAccount->getNumber() === $toAccount->getNumber()) {
            throw new \InvalidArgumentException('Source and destination accounts cannot be the same');
        }

        // No need to check if accounts are open. 'deposit/withdrawal' functions already handle that
        // No need to check if source account has enough balance (including the fee). 'withdrawal' function already handles that

        // Check account's daily limit
        if ($this->limitService->transferDailyLimitExceeded($fromAccount, $amount)) {
            throw new \RuntimeException('Account daily trasnfer limit reached');
        }

        // Request external approval
        if (! $this->approvalService->approveTransfer($fromAccount, $toAccount, $amount)) {
            throw new \RuntimeException('Transfer not approved');
        }

        return $this;
    }
}
