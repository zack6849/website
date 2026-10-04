<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\QRZAPIException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use j4nr6n\ADIF\Exception\ParseException;
use j4nr6n\ADIF\Parser;

class QRZLogbookService
{

    private string $baseUrl = '';
    private mixed $token = '';

    public function __construct()
    {
        $this->baseUrl = 'https://logbook.qrz.com';
        $this->token = config('services.qrz.key');
    }

    /**
     * @throws QRZAPIException
     * @throws ConnectionException
     * @throws RequestException
     * @throws ParseException
     */
    public function getLogbookEntries(): array
    {
        $response = Http::asForm()->timeout(15)->connectTimeout(5)->post($this->baseUrl . '/api', [
            'KEY' => $this->token,
            'ACTION' => 'FETCH'
        ]);
        if(!$response->successful()){
            $response->throw();
        }
        $data = $this->parseResponseBody($response->body());
        //QRZ's API always responds HTTP 200, even on auth/logical failure, and
        //signals errors via STATUS/RESULT/REASON fields in the body instead
        if (!isset($data['ADIF'])) {
            throw new QRZAPIException(
                'QRZ logbook fetch failed: ' . ($data['REASON'] ?? $response->body())
            );
        }
        $adifData = str_replace('{AMP}', '&', $data['ADIF']);
        return new Parser()->parse($adifData);
    }

    public function parseLogbookEntries($adifString): array
    {
        $data = $this->parseResponseBody($adifString);
        $adifData = str_replace('{AMP}', '&', $data['ADIF']);
        return new Parser()->parse($adifData);
    }

    private function parseResponseBody(string $responseBody): array
    {
        $responseText = str_replace(["&lt;", "&gt;", "\n"], ["<", ">", ""], $responseBody);
        $responseText = preg_replace("/&(?![^\s=]+=[^\s=])/m", "{AMP}", $responseText);
        $responseText = htmlspecialchars_decode($responseText);
        $data = [];
        parse_str($responseText, $data);
        return $data;
    }
}
