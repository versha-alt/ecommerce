<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentMethods
{
    public function validate(array $input, ?Record $old): array
    {
        $data = Validator::make($input, [
            'category' => 'required|in:Online,Manual',
            'provider' => 'required|in:PayPal,Stripe,Razorpay,M-Pesa,Pesapal,Flutterwave,DPO,Other online,COD,Bank transfer,Other manual',
            'environment' => 'required|in:Sandbox,Production',
            'public_key' => 'nullable|string|max:2000',
            'credentials' => 'nullable|string|max:10000',
            'instructions' => 'nullable|string|max:5000',
            'configuration' => 'nullable|string|max:10000',
        ])->validate();
        $manual = in_array($data['provider'], ['COD', 'Bank transfer', 'Other manual'], true);
        if ($manual !== ($data['category'] === 'Manual')) {
            $this->fail('The selected provider does not belong to this payment category.');
        }
        if (Record::where('resource', 'payment-methods')->where('id', '!=', $old?->id ?? '')->get()->contains(fn ($record) => ($record->data['provider'] ?? '') === $data['provider'] && ! str_starts_with($data['provider'], 'Other') && ($record->data['status'] ?? '') !== 'Retired')) {
            $this->fail('This provider is already configured. Update its existing payment method.');
        }
        $credentials = $input['credentials'] ?? '';
        if (! $credentials && $old && ($old->data['provider'] ?? '') === $data['provider']) {
            $data['credentials'] = $old->data['credentials'] ?? '';
        } elseif ($credentials) {
            $decoded = json_decode($credentials, true);
            if (! is_array($decoded) || array_is_list($decoded) || ! $decoded) {
                $this->fail('Enter credentials as a JSON object of gateway keys and secrets.');
            }
            foreach ($decoded as $value) {
                if (! is_string($value)) {
                    $this->fail('Gateway credential values must be strings.');
                }
            }
            if ($old && ($old->data['provider'] ?? '') === $data['provider'] && ! empty($old->data['credentials'])) {
                $decoded = array_merge(json_decode(Crypt::decryptString($old->data['credentials']), true), $decoded);
            }
            $data['credentials'] = Crypt::encryptString(json_encode($decoded));
        } else {
            $data['credentials'] = '';
        }
        if ($manual) {
            $data['credentials'] = '';
            $data['public_key'] = '';
            $data['environment'] = 'Production';
        }
        if (! $manual && ($input['status'] ?? 'Active') === 'Active' && ! $data['credentials']) {
            $this->fail('Configure gateway credentials before enabling online payments.');
        }
        if (! $manual && ($input['status'] ?? 'Active') === 'Active') {
            $required = match ($data['provider']) {
                'PayPal' => ['client_secret'], 'Stripe' => ['secret_key'], 'Razorpay' => ['key_secret'], 'M-Pesa' => ['consumer_key', 'consumer_secret', 'passkey'], 'Pesapal' => ['consumer_key', 'consumer_secret'], 'Flutterwave' => ['secret_key'], 'DPO' => ['company_token'], default => []
            };
            $stored = json_decode(Crypt::decryptString($data['credentials']), true);
            foreach ($required as $key) {
                if (empty(trim($stored[$key] ?? ''))) {
                    $this->fail('Configure '.$key.' before enabling this gateway.');
                }
            }
            if (in_array($data['provider'], ['PayPal', 'Stripe', 'Razorpay', 'M-Pesa'], true) && empty(trim($data['public_key'] ?? ''))) {
                $this->fail('Configure the public gateway identifier before enabling this method.');
            }
        }
        if (! empty($data['configuration'])) {
            $configuration = json_decode($data['configuration'], true);
            if (! is_array($configuration) || array_is_list($configuration)) {
                $this->fail('Gateway configuration must be a JSON object.');
            }
            array_walk_recursive($configuration, function ($value, $key) {
                if (preg_match('/secret|password|token|passkey|private|consumer_key/i', (string) $key)) {
                    $this->fail('Put secrets in the encrypted credentials field.');
                }
            });
        }

        return $data;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['payment_method' => $message]);
    }
}
