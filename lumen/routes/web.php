<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// Home page. Show available endpoints
$router->get('/', function () {

    // URI for the database management panel
    $adminerUri = http_build_query([
        'pgsql' =>  env('DB_HOST'),
        'username' => env('DB_USERNAME'),
        'db' => env('DB_DATABASE'),
        'ns' => 'public',
    ]);

    return [
        "GET adminer.php?$adminerUri" => [
            'description' => 'Database management dashboard',
            'password' => env('DB_PASSWORD'),
        ],
        'GET account' => [
            'description' => 'Get all bank accounts',
        ],
        'POST account' => [
            'description' => 'Open new bank account',
            'sample_payload' => ['owner' => 'John Doe'],
        ],
        'GET account/{number}' => [
            'description' => 'Get bank account information',
        ],
        'DELETE account/{number}' => [
            'description' => 'Close bank account',
        ],
        'POST account/{number}/deposit' => [
            'description' => 'Deposit amount into bank account',
            'sample_payload' => ['amount' => 123.45],
        ],
        'POST account/{number}/withdrawal' => [
            'description' => 'Withdrawal amount from bank account',
            'sample_payload' => ['amount' => 123.45],
        ],
        'POST account/{number}/transfer' => [
            'description' => 'Transfer amount into another bank account',
            'sample_payload' => ['toNumber' => '12345678-9012-3456-7890-123456780123', 'amount' => 123.45],
        ],
    ];
});

// Get all bank accounts
$router->get('account', 'AccountController@getAll');

// Open new bank account
$router->post('account', 'AccountController@create');

// Get bank account information
$router->get('account/{number}', 'AccountController@getByNumber');

// Close bank account
$router->delete('account/{number}', 'AccountController@close');

// Deposit amount into bank account
$router->post('account/{number}/deposit', 'AccountController@deposit');

// Withdrawal amount from bank account
$router->post('account/{number}/withdrawal', 'AccountController@withdrawal');

// Transfer amount into another bank account
$router->post('account/{number}/transfer', 'AccountController@transfer');
