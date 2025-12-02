<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use DateTimeImmutable;

class AuthController extends Controller
{
    private $secretKey = 'Qw3rty09!@#';

    /**
     * POST /api/generate-token
     */
    public function generateToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'date_request' => 'required|date_format:Y-m-d H:i:s'
        ]);

        // Gunakan secret key langsung dari ketentuan
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->secretKey) // Gunakan $this->secretKey
        );

        $now = new DateTimeImmutable();
        $exp = $now->modify('+1 hour');

        // Buat Token dengan payload sesuai ketentuan
        $token = $config->builder()
            ->issuedAt($now)
            ->expiresAt($exp)
            ->withClaim('name', $request->name)
            ->withClaim('date_request', $request->date_request)
            ->getToken($config->signer(), $config->signingKey());

        return response()->json([
            'success' => true,
            'name' => $request->name,
            'date_request' => $request->date_request,
            'token' => $token->toString(),
            'exp' => $exp->format('Y-m-d H:i:s')
        ], 200);
    }
}
