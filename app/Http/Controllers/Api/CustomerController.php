<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\Clock\SystemClock;
use DateTimeImmutable;
use DateTimeZone;

class CustomerController extends Controller
{
    private $secretKey = 'Qw3rty09!@#';

    /**
     * POST /api/customer-items
     */
    public function getCustomerItems(Request $request)
    {
        try {
            // 1. Validasi bearer token
            $authHeader = $request->header('Authorization');

            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json([
                    'error' => 'Token not provided or invalid format'
                ], 401);
            }

            $tokenString = substr($authHeader, 7);

            // 2. Konfigurasi JWT
            $config = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::plainText($this->secretKey)
            );

            // 3. Parse token
            try {
                $token = $config->parser()->parse($tokenString);
                Log::info('Token parsed successfully');
            } catch (\Exception $e) {
                Log::error('Token parse error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Invalid token format'
                ], 401);
            }

            // 4. Validasi token
            $constraints = [
                new SignedWith($config->signer(), $config->signingKey()),
                new ValidAt(new SystemClock(new DateTimeZone('Asia/Jakarta'))),
            ];

            if (!$config->validator()->validate($token, ...$constraints)) {
                Log::error('Token validation failed');
                return response()->json(['error' => 'Token is invalid or expired'], 401);
            }

            // 5. Validasi request body
            $request->validate([
                'name_customers' => 'required|string',
                'date_request' => 'required|date_format:Y-m-d H:i:s'
            ]);

            $nameCustomers = $request->input('name_customers');
            $dateRequest = $request->input('date_request');

            // 6. Logic discount sesuai ketentuan
            $discounts = [
                ['min' => 0, 'max' => 50000, 'discount' => 0.02],
                ['min' => 50000, 'max' => 150000, 'discount' => 0.035],
                ['min' => 150000, 'max' => PHP_INT_MAX, 'discount' => 0.05]
            ];

            // 7. Data contoh sesuai requirement
            // Dalam implementasi real, data ini akan dari database
            $exampleData = [
                [
                    'name_customers' => $nameCustomers,
                    'items' => 'Lampu bohlam LED 20 WATT',
                    'estimate_price' => 20000,
                ],
                [
                    'name_customers' => $nameCustomers,
                    'items' => 'Mouse wireless logitech',
                    'estimate_price' => 180000,
                ]
            ];

            // 8. Hitung discount dan fix price
            $results = [];
            foreach ($exampleData as $item) {
                $price = $item['estimate_price'];
                $discount = 0;

                foreach ($discounts as $discRule) {
                    if ($price >= $discRule['min'] && $price < $discRule['max']) {
                        $discount = $discRule['discount'];
                        break;
                    }
                }

                $fixPrice = $price - ($price * $discount);

                $results[] = [
                    'name_customers' => $item['name_customers'],
                    'items' => $item['items'],
                    'dicount' => number_format($discount, 3, ',', ''),
                    'fix_price' => number_format($fixPrice, 0, ',', '')
                ];
            }

            // 9. Return response sesuai format requirement
            return response()->json([
                'result' => $results
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in getCustomerItems: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
