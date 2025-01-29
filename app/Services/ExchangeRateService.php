<?php


namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected $client;
    protected $apiUrl = 'https://api.fastforex.io/fetch-one?from=';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getRate($from, $to)
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', 'https://api.fastforex.io/fetch-one?from=XAF&to=USD&api_key=' . env('FAST_FOREX_API_KEY'), [
        'headers' => [
            'accept' => 'application/json',
        ],
        ]);

        $data = json_decode($response->getBody(), true);
        if (isset($data['result'][$to])) {
            return $data['result'][$to];
        }

        return null; // Handle error appropriately in your application
    }
}
