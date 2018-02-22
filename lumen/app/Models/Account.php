<?php

namespace App\Models;

class Account extends Model
{
    /**
     * Account number.
     *
     * @var string
     */
    protected $number;

    /**
     * Account owner.
     *
     * @var string
     */
    protected $owner;

    /**
     * Account current balance.
     *
     * @var float
     */
    protected $balance = 0;

    /**
     * Whether or not the account is closed.
     *
     * @var bool
     */
    protected $closed = true;

    /**
     * Get account number.
     *
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set account number.
     *
     * @param  string $number
     * @return self
     */
    public function setNumber(string $number)
    {
        // Set the number
        $this->number = trim($number);

        // Allow method chaining
        return $this;
    }

    /**
     * Get account owner.
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set account owner.
     *
     * @param  string $owner
     * @return self
     */
    public function setOwner(string $owner)
    {
        // Set the owner
        $this->owner = trim($owner);

        // Allow method chaining
        return $this;
    }

    /**
     * Get current balance.
     *
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * Set current balance.
     *
     * @param  float $balance
     * @return self
     */
    public function setBalance(float $balance)
    {
        // Set the balance
        $this->balance = $balance;

        // Allow method chaining
        return $this;
    }

    /**
     * Get closed status.
     *
     * @return bool
     */
    public function getClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Set closed status.
     *
     * @param  bool $closed
     * @return self
     */
    public function setClosed(bool $closed)
    {
        // Set closed status
        $this->closed = $closed;

        // Allow method chaining
        return $this;
    }

    /**
     * Determine whether or not the account is closed.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->getClosed();
    }

    /**
     * Determine whether or not the account is open.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return ! $this->isClosed();
    }

    /**
     * Convert the account into its array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'number' => $this->getNumber(),
            'owner' => $this->getOwner(),
            'balance' => $this->getBalance(),
            'closed' => $this->getClosed(),
        ];
    }
}
