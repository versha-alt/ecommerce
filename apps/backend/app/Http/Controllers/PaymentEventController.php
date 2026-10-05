<?php

namespace App\Http\Controllers;

use App\Services\CustomerPayments;
use Illuminate\Http\Request;

class PaymentEventController extends Controller
{
    public function __invoke(Request $request, CustomerPayments $payments): array
    {
        $secret = config('commerce.payment_events_secret');
        abort_unless(is_string($secret) && strlen($secret) >= 32, 503, 'Payment event integration has not been configured.');
        $timestamp = $request->header('X-Payment-Timestamp', '');
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 401, 'Payment event timestamp is invalid.');
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(hash_equals($expected, $request->header('X-Payment-Signature', '')), 401, 'Payment event signature is invalid.');

        return $payments->event($request->json()->all());
    }
}
