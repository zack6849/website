<?php

declare(strict_types=1);

namespace App\Http\Controllers\TwilioLookup;

use App\Http\Controllers\Controller;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twilio\Exceptions\TwilioException;
use Twilio\TwiML\MessagingResponse;

class TwilioController extends Controller
{


    public function lookup($phone_number, TwilioService $twilio, Request $request)
    {
        [$result, $includeIdentityData] = $this->performLookupOrAbort($phone_number, $twilio, $request);
        return $twilio->extractData($result, $includeIdentityData);
    }

    public function index()
    {
        return view('pages.phone-lookup');
    }

    public function rawLookup($phone_number, TwilioService $twilio, Request $request)
    {
        [$result] = $this->performLookupOrAbort($phone_number, $twilio, $request);
        return $result;
    }

    //shared by lookup() and rawLookup() so the trust computation and error handling can't drift between the two
    private function performLookupOrAbort($phone_number, TwilioService $twilio, Request $request): array
    {
        $includeIdentityData = $twilio->isTrustedRequester($request->user());
        try {
            $result = $twilio->performLookup($phone_number, $request->user(), $request->ip(), null, $includeIdentityData);
        } catch (TwilioException $e) {
            Log::error('Twilio lookup failed', ['exception' => $e]);
            abort(502, "Something went wrong looking up that number. Please try again later.");
        }
        return [$result, $includeIdentityData];
    }

    public function twilioResponse(Request $request, TwilioService $provider){
        $response = new MessagingResponse();
        $sms_body = $request->Body;
        $matches_in_body = [];
        preg_match_all('/\b\+?\d?\d{3}\s*-?\s*\d{3}\s*-?\s*\d{4}\b/', $sms_body, $matches_in_body);
        if (empty($matches_in_body) || empty($matches_in_body[0])) {
            $response->message("No phone number detected in message.");
            return $response;
        }
        $matches = array_pop($matches_in_body);
        $number = array_pop($matches);
        //if fed a local number, presume US prefix
        if (strlen($number) == 10) {
            $number = "1$number";
        }
        //rate limit by the texter's own number, not the shared API token/IP, so each
        //family member gets their own daily quota instead of sharing (or bypassing) one
        $fromNumber = $request->input('From', $request->ip());
        $rateLimitKey = 'sms:' . $provider->normalizePhoneNumber($fromNumber);
        //this webhook is only reachable by texting the private Twilio number given to family,
        //not the public website, so it stays on the full identity tier unlike the web routes
        try {
            $result = $provider->performLookup($number, $request->user(), $request->ip(), $rateLimitKey, true);
        } catch (HttpException $e) {
            $response->message($e->getMessage());
            return $response;
        } catch (\Throwable $e) {
            Log::error('SMS lookup failed', ['exception' => $e]);
            $response->message("Sorry, that lookup couldn't be completed right now.");
            return $response;
        }
        $response->message($provider->toSms($provider->extractData($result, true)));
        return $response;
    }

}
