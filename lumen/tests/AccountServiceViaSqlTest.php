<?php

use App\Models\Account;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AccountServiceViaSqlTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    /**
     * Run before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // Mock external dependencies
        $limitService = Mockery::mock('\App\Contracts\AccountTransferLimitService');
        $limitService->shouldReceive('transferDailyLimitExceeded')->withAnyArgs()->andReturn(false);
        $limitService->shouldReceive('registerTransfer')->withAnyArgs()->andReturnSelf();
        $approvalService = Mockery::mock('\App\Contracts\AccountTransfertApprovalService');
        $approvalService->shouldReceive('approveTransfer')->withAnyArgs()->andReturn(true);

        // Create service to be tested
        $databaseService = app(\Illuminate\Database\ConnectionResolverInterface::class);
        $this->service = new \App\Services\AccountServiceViaSql($limitService, $approvalService, $databaseService);

        // Create test account
        $this->account = Account::make(['owner' => 'test']);
        $this->service->create($this->account);
    }

    /**
     * Test get all bank accounts.
     *
     * @return void
     */
    public function testGetAll()
    {
        $accounts = $this->service->getAll();
        $this->assertEquals(1, $accounts->count());
        $this->assertEquals($this->account->toArray(), $accounts->first()->toArray());
    }

    /**
     * Test create a new bank account.
     *
     * @return void
     */
    public function testCreateAccount()
    {
        $account = Account::make(['owner' => 'test']);
        $created = $this->service->create($account);
        $this->assertTrue($created);
    }

    /**
     * Test get bank account by its number.
     *
     * @return void
     */
    public function testGetAccountByNumber()
    {
        $account = $this->service->getByNumber($this->account->getNumber());
        $this->assertEquals($this->account->toArray(), $account->toArray());
    }

    /**
     * Test close bank account.
     *
     * @return void
     */
    public function testCloseAccount()
    {
        // Non empty account
        $this->service->deposit($this->account, 100);
        $closed = $this->service->close($this->account);
        $this->assertFalse($closed);
        $this->assertTrue($this->account->isOpen());

        // Empty account
        $this->service->withdrawal($this->account, 100);
        $closed = $this->service->close($this->account);
        $this->assertTrue($closed);
        $this->assertTrue($this->account->isClosed());
    }

    /**
     * Test deposit amount into bank account.
     *
     * @return void
     */
    public function testDepositAmount()
    {
        // Deposit valid amout
        $deposit = $this->service->deposit($this->account, 100);
        $this->assertTrue($deposit);

        // Deposit in closed account
        $this->service->withdrawal($this->account, 100);
        $this->service->close($this->account);
        $deposit = $this->service->deposit($this->account, 100);
        $this->assertFalse($deposit);

        // Deposit invalid amout
        $this->expectExceptionMessage('Invalid amount');
        $this->service->deposit($this->account, -100);
    }

    /**
     * Test withdraw amount from bank account.
     *
     * @return void
     */
    public function testWithdrawAmount()
    {
        // Withdraw without balance
        $withdrawal = $this->service->withdrawal($this->account, 100);
        $this->assertFalse($withdrawal);

        // Withdraw valid amout
        $this->service->deposit($this->account, 100);
        $withdrawal = $this->service->withdrawal($this->account, 100);
        $this->assertTrue($withdrawal);

        // Withdraw from closed account
        $this->service->close($this->account);
        $withdrawal = $this->service->withdrawal($this->account, 100);
        $this->assertFalse($withdrawal);

        // Withdraw invalid amout
        $this->expectExceptionMessage('Invalid amount');
        $this->service->withdrawal($this->account, -100);
    }

    /**
     * Test calculate transfer fee.
     *
     * @return void
     */
    public function testCalculateTransferFee()
    {
        $fromAccount = Account::make(['owner' => 'test']);
        $toSameOwnerAccount = Account::make(['owner' => 'test']);
        $toAccount = Account::make(['owner' => 'anotherTest']);

        $fee = $this->service->calculateTransferFee($fromAccount, $toSameOwnerAccount);
        $this->assertEquals(0, $fee);

        $fee = $this->service->calculateTransferFee($fromAccount, $toAccount);
        $this->assertEquals($this->service::TRANSFER_FEE, $fee);
    }

    /**
     * Test transfer amount between bank accounts.
     *
     * @return void
     */
    public function testTransferIntoAccount()
    {
        // Prepare accounts
        $fromAccount = $this->account;
        $this->service->deposit($fromAccount, 1000);
        $this->service->create($toSameOwnerAccount = Account::make(['owner' => 'test']));
        $this->service->create($toAccount = Account::make(['owner' => 'anotherTest']));

        // Test transfer to same owner account
        $result = $this->service->transfer($fromAccount, $toSameOwnerAccount, 123);
        $this->assertTrue($result);
        $fromAccount = $this->service->getByNumber($fromAccount->getNumber());
        $toSameOwnerAccount = $this->service->getByNumber($toSameOwnerAccount->getNumber());
        $this->assertEquals(1000 - 123, $fromAccount->getBalance());
        $this->assertEquals(123, $toSameOwnerAccount->getBalance());

        // Test transfer to another owner account
        $result = $this->service->transfer($fromAccount, $toAccount, 456);
        $this->assertTrue($result);
        $fromAccount = $this->service->getByNumber($fromAccount->getNumber());
        $toAccount = $this->service->getByNumber($toAccount->getNumber());
        $this->assertEquals(1000 - 123 - 456 - 100/*fee*/, $fromAccount->getBalance());
        $this->assertEquals(456, $toAccount->getBalance());
    }

    /**
     * Test transfer wrong amount.
     *
     * @return void
     */
    public function testTransferWrongAmount()
    {
        $this->expectExceptionMessage('Invalid amount');
        $this->service->transfer($this->account, $this->account, -100);
    }

    /**
     * Test transfer to same account.
     *
     * @return void
     */
    public function testTransferToSameAccount()
    {
        $fromAccount = $toAccount = $this->account;
        $this->expectExceptionMessage('Source and destination accounts cannot be the same');
        $this->service->transfer($fromAccount, $toAccount, 100);
    }

    /**
     * Test transfer without enough balance.
     *
     * @return void
     */
    public function testTransferWithoutBalance()
    {
        $fromAccount = $this->account;
        $toAccount = Account::make(['owner' => 'anotherTest']);

        $this->expectExceptionMessage('Unable to withdraw amount from source account');
        $this->service->transfer($fromAccount, $toAccount, 100);
    }

    /**
     * Test transfer from invalid account.
     *
     * @return void
     */
    public function testTransferFromInvalidAccount()
    {
        $fromAccount = Account::make(['owner' => 'anotherTest']);
        $toAccount = $this->account;

        $this->expectExceptionMessage('Unable to withdraw amount from source account');
        $this->service->transfer($fromAccount, $toAccount, 100);
    }

    /**
     * Test transfer to invalid account.
     *
     * @return void
     */
    public function testTransferToInvalidAccount()
    {
        $fromAccount = $this->account;
        $this->service->deposit($fromAccount, 1000);
        $toAccount = Account::make(['owner' => 'anotherTest']);

        $this->expectExceptionMessage('Unable to deposit amount into destination account');
        $this->service->transfer($fromAccount, $toAccount, 100);
    }

    /**
     * Test transfer from closed account.
     *
     * @return void
     */
    public function testTransferFromClosedAccount()
    {
        $fromAccount = $this->account;
        $this->service->close($fromAccount);
        $toAccount = Account::make(['owner' => 'anotherTest']);

        $this->expectExceptionMessage('Unable to withdraw amount from source account');
        $this->service->transfer($fromAccount, $toAccount, 100);
    }

    /**
     * Test transfer to closed account.
     *
     * @return void
     */
    public function testTransferToClosedAccount()
    {
        $fromAccount = $this->account;
        $this->service->deposit($fromAccount, 1000);
        $toAccount = Account::make(['owner' => 'anotherTest']);
        $this->service->create($toAccount);
        $this->service->close($toAccount);

        $this->expectExceptionMessage('Unable to deposit amount into destination account');
        $this->service->transfer($fromAccount, $toAccount, 100);
    }
}
