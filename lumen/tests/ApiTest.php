<?php

use App\Contracts\AccountService;
use App\Models\Account;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ApiTest extends TestCase
{
    // This will automatically rollback any changes
    use DatabaseMigrations, DatabaseTransactions;

    /**
     * Run before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->account = Account::make(['owner' => 'test']);
        $this->service = app(AccountService::class);
        $this->service->create($this->account);
    }

    /**
     * Test endpoint for listing all bank accounts.
     *
     * @return void
     */
    public function testGetAllAccounts()
    {
        $this->get('account');
        $this->assertResponseOk();
        $this->seeJsonContains($this->account->toArray());
    }

    /**
     * Test endpoint for creating a new bank account.
     *
     * @return void
     */
    public function testCreateAccount()
    {
        // Test invalid payload
        $this->post('account', [])->assertResponseStatus(422);
        $this->seeJsonStructure(['owner']);

        // Test valid payload
        $account = Account::make(['owner' => str_random(8)]);
        $this->post('account', ['owner' => $this->account->getOwner()]);
        $this->assertResponseStatus(201);
        $this->seeJsonContains(['owner' => $this->account->getOwner(), 'closed' => false]);
    }

    /**
     * Test endpoint for retieving account information.
     *
     * @return void
     */
    public function testGetAccount()
    {
        // Test non existing account
        $this->get('account/test')->assertResponseStatus(404);
        $this->seeJsonStructure(['error']);

        // Test existing account
        $this->get('account/' . $this->account->getNumber())->assertResponseOk();
        $this->seeJsonEquals($this->account->toArray());
    }

    /**
     * Test endpoint for closing an account.
     *
     * @return void
     */
    public function testCloseAccount()
    {
        // Test non existing account
        $this->delete('account/test')->assertResponseStatus(404);
        $this->seeJsonStructure(['error']);

        // Test existing account with balance
        $number = $this->account->getNumber();
        $amount = 123.45;
        $this->service->deposit($this->account, $amount);
        $this->delete("account/$number")->assertResponseStatus(422);
        $this->seeJsonStructure(['error']);

        // Test existing liquidated account
        $this->service->withdrawal($this->account, $amount);
        $this->delete("account/$number")->assertResponseOk();
        $this->seeJsonEquals($this->account->setClosed(true)->toArray());
    }

    /**
     * Test endpoint for depositing amount into bank account.
     *
     * @return void
     */
    public function testDepositAmount()
    {
        $number = $this->account->getNumber();
        $amount = 123.45;

        // Test non existing account
        $this->post('account/test/deposit', ['amount' => $amount])->assertResponseStatus(404);
        $this->seeJsonStructure(['error']);

        // Test invalid payload
        $this->post("account/$number/deposit", [])->assertResponseStatus(422);
        $this->seeJsonStructure(['amount']);
        $this->post("account/$number/deposit", ['amount' => -100])->assertResponseStatus(422);
        $this->seeJsonStructure(['amount']);

        // Test valid payload
        $this->post("account/$number/deposit", ['amount' => $amount])->assertResponseOk();
        $this->seeJsonEquals($this->account->setBalance($amount)->toArray());

        // Test closed account
        $this->service->withdrawal($this->account, $amount);
        $this->service->close($this->account);
        $this->post("account/$number/deposit", ['amount' => $amount])->assertResponseStatus(422);
        $this->seeJsonStructure(['error']);
    }

    /**
     * Test endpoint for withdrawing amount from bank account.
     *
     * @return void
     */
    public function testWithdrawalAmount()
    {
        $number = $this->account->getNumber();
        $this->service->deposit($this->account, $amount = 123.45);

        // Test non existing account
        $this->post('account/test/withdrawal', ['amount' => $amount])->assertResponseStatus(404);
        $this->seeJsonStructure(['error']);

        // Test invalid payload
        $this->post("account/$number/withdrawal", [])->assertResponseStatus(422);
        $this->seeJsonStructure(['amount']);
        $this->post("account/$number/withdrawal", ['amount' => -100])->assertResponseStatus(422);
        $this->seeJsonStructure(['amount']);

        // Test valid payload
        $this->post("account/$number/withdrawal", ['amount' => $amount])->assertResponseOk();
        $this->seeJsonEquals($this->account->setBalance(0)->toArray());

        // Test closed account
        $this->service->close($this->account);
        $this->post("account/$number/withdrawal", ['amount' => $amount])->assertResponseStatus(422);
        $this->seeJsonStructure(['error']);
    }

    /**
     * Test endpoint for transfering amount between bank accounts.
     *
     * @return void
     */
    public function testTransferIntoAccount()
    {
        // Deposit into source account
        $fromAccount = $this->account;
        $number = $fromAccount->getNumber();
        $this->service->deposit($fromAccount, 1000);

        // Create destination accounts
        $toSameOwnerAccount = Account::make(['owner' => 'test']);
        $toAccount = Account::make(['owner' => 'anotherTest']);
        $this->service->create($toSameOwnerAccount);
        $this->service->create($toAccount);

        // Test non existing source account
        $this->post('account/test/transfer', ['toNumber' => $toAccount->getNumber(), 'amount' => 100])->assertResponseStatus(404);
        $this->seeJsonStructure(['error']);

        // Test non existing destination account
        $this->post("account/$number/transfer", ['toNumber' => '-', 'amount' => 100])->assertResponseStatus(404);
        $this->seeJsonStructure(['error']);

        // Test invalid payload
        $this->post("account/$number/transfer", [])->assertResponseStatus(422);
        $this->seeJsonStructure(['toNumber', 'amount']);
        $this->post("account/$number/transfer", ['amount' => -100])->assertResponseStatus(422);
        $this->seeJsonStructure(['amount']);

        // Test transfer to same owner
        $this->post("account/$number/transfer", ['toNumber' => $toSameOwnerAccount->getNumber(), 'amount' => 123])->assertResponseOk();
        $this->seeJsonEquals($this->account->setBalance(1000 - 123)->toArray());

        // Test transfer to another owner
        $this->post("account/$number/transfer", ['toNumber' => $toAccount->getNumber(), 'amount' => 456])->assertResponseOk();
        $this->seeJsonEquals($this->account->setBalance(1000 - 123 - 456 - 100/*fee*/)->toArray());

        // Test transfer to closed account
        $this->service->close($toAccount);
        $this->post("account/$number/transfer", ['toNumber' => $toAccount->getNumber(), 'amount' => 789]);
        $this->seeJsonStructure(['error']);
    }
}
