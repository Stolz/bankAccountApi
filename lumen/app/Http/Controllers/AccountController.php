<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Instance of the service used to interact with bank accounts.
     *
     * @var \App\Contracts\AccountService
     */
    protected $accountService;

    /**
     * Class constructor.
     *
     * Inject dependencies.
     *
     * @param \App\Contracts\AccountService $accountService
     *
     * @return void
     */
    public function __construct(\App\Contracts\AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Get all bank accounts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll()
    {
        $accounts = $this->accountService->getAll();

        return response($accounts, 200);
    }

    /**
     * Create a new bank account.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Validate request
        $this->validate($request, ['owner' => 'required|min:3|max:255']);

        // Initialize account
        $account = Account::make($request->only(['owner']))->setClosed(false);

        // Persist account
        $created = $this->accountService->create($account);
        if (! $created) {
            return response(['error' => 'Unable to create account'], 500);
        }

        // Success
        return response($account, 201);
    }

    /**
     * Get bank account by its number.
     *
     * @param string $number
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByNumber(string $number)
    {
        // Retrieve account
        $account = $this->accountService->getByNumber($number);

        // Account not found
        if (! $account) {
            return response(['error' => 'Account not found'], 404);
        }

        // Account found
        return response($account, 200);
    }

    /**
     * Close bank account.
     *
     * @param string $number
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function close(string $number)
    {
        // Retrieve account
        $account = $this->accountService->getByNumber($number);

        // Account not found
        if (! $account) {
            return response(['error' => 'Account not found'], 404);
        }

        // Ensure account balance is liquidated
        if ($account->getBalance() != 0) {
            return response(['error' => 'Account balance not liquidated'], 422);
        }

        // Close account
        $closed = $this->accountService->close($account);
        if (! $closed) {
            return response(['error' => 'Unable to close account'], 500);
        }

        return response($account, 200);
    }

    /**
     * Deposit amount into bank account.
     *
     * @param string $number
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit(string $number, Request $request)
    {
        return $this->operate($number, $request, 'deposit');
    }

    /**
     * Withdrawal amount from bank account.
     *
     * @param string $number
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdrawal(string $number, Request $request)
    {
        return $this->operate($number, $request, 'withdrawal', true);
    }

    /**
     * Perform an operation in bank account.
     *
     * @param string $number
     * @param \Illuminate\Http\Request $request
     * @param string $operation
     * @param bool $checkOverdraft
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function operate(string $number, Request $request, string $operation, bool $checkOverdraft = false)
    {
        // Validate request
        $this->validate($request, ['amount' => 'required|numeric|min:0.01']);

        // Retrieve account
        $account = $this->accountService->getByNumber($number);

        // Account not found
        if (! $account) {
            return response(['error' => 'Account not found'], 404);
        }

        // Check account status
        if ($account->isClosed()) {
            return response(['error' => 'Account is closed'], 422);
        }

        // Check for overdraft
        $amount = (float) $request->input('amount');
        if ($checkOverdraft and $account->getBalance() < $amount) {
            return response(['error' => 'Insufficient balance'], 422);
        }

        // Perform operation
        $success = $this->accountService->{$operation}($account, $amount);
        if (! $success) {
            return response(['error' => "Unable to make $operation"], 500);
        }

        // Refresh account information
        $account = $this->accountService->getByNumber($number);

        return response($account, 200);
    }

    /**
     * Transfer amount into another bank account.
     *
     * @param string $number
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transfer(string $number, Request $request)
    {
        // Validate request
        $this->validate($request, [
            'toNumber' => 'required',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Retrieve accounts
        $fromAccount = $this->accountService->getByNumber($number);
        $toAccount = $this->accountService->getByNumber($request->input('toNumber'));
        $amount = (float) $request->input('amount');

        // Account not found
        if (! $fromAccount or ! $toAccount) {
            $account = ($toAccount) ? 'Source' : 'Destination';
            return response(['error' => "$account account not found"], 404);
        }

        // Check accounts status
        if ($fromAccount->isClosed() or $toAccount->isClosed()) {
            $account = ($fromAccount->isClosed()) ? 'Source' : 'Destination';
            return response(['error' => "$account account is closed"], 422);
        }

        // Make transfer
        try {
            $success = $this->accountService->transfer($fromAccount, $toAccount, $amount);
            if (! $success) {
                return response(['error' => 'Unable to transfer amount'], 500);
            }
        } catch (\Exception $exception) {
            return response(['error' => $exception->getMessage()], 500);
        }

        // Refresh account information
        $fromAccount = $this->accountService->getByNumber($number);

        return response($fromAccount, 200);
    }
}
