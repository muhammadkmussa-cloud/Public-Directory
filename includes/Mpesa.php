<?php
/**
 * Ummah Directory — M-Pesa Daraja STK Push helper
 *
 * With real credentials (MPESA_CONSUMER_KEY/SECRET + production env) this
 * performs a live STK push. Without credentials (or in dev/sandbox) it
 * returns a simulated success so the donation flow is fully testable.
 */

class Mpesa
{
    /** Initiate an STK push to the customer's phone */
    public static function stkPush($phone, $amount, $accountRef, $transactionDesc)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) {
            $phone = '254' . $phone;                 // 7XXXXXXXX → 2547XXXXXXXX
        } elseif (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '254' . substr($phone, 1);
        }

        // Simulation mode (no credentials configured)
        if (MPESA_CONSUMER_KEY === '' || MPESA_CONSUMER_SECRET === '' || MPESA_ENV !== 'production') {
            return [
                'ok' => true,
                'simulated' => true,
                'phone' => $phone,
                'amount' => $amount,
                'message' => 'M-Pesa payment initiated (sandbox mode — no real charge was made)',
            ];
        }

        try {
            $token = self::authToken();
            $env = MPESA_ENV === 'production'
                ? 'https://api.safaricom.co.ke'
                : 'https://sandbox.safaricom.co.ke';

            $ts = date('YmdHis');
            $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $ts);

            $payload = [
                'BusinessShortCode' => MPESA_SHORTCODE,
                'Password'          => $password,
                'Timestamp'         => $ts,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => (int)round($amount),
                'PartyA'            => $phone,
                'PartyB'            => MPESA_SHORTCODE,
                'PhoneNumber'       => $phone,
                'CallBackURL'       => MPESA_CALLBACK_URL,
                'AccountReference'  => substr($accountRef, 0, 12),
                'TransactionDesc'   => substr($transactionDesc, 0, 13),
            ];

            $ch = curl_init($env . '/mpesa/stkpush/v1/processrequest');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => 15,
            ]);
            $resp = json_decode(curl_exec($ch) ?: '', true);
            curl_close($ch);

            if (!empty($resp['ResponseCode']) && $resp['ResponseCode'] === '0') {
                return ['ok' => true, 'simulated' => false, 'checkout_request_id' => $resp['CheckoutRequestID'] ?? null];
            }
            return ['ok' => false, 'error' => $resp['ResponseDescription'] ?? 'M-Pesa request failed'];
        } catch (Throwable $e) {
            error_log('M-Pesa STK push error: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'M-Pesa is unavailable right now'];
        }
    }

    private static function authToken()
    {
        $env = MPESA_ENV === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $ch = curl_init($env . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = json_decode(curl_exec($ch) ?: '', true);
        curl_close($ch);

        if (empty($resp['access_token'])) {
            throw new RuntimeException('Could not obtain M-Pesa access token');
        }
        return $resp['access_token'];
    }
}
