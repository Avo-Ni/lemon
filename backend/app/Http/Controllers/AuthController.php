<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Enregistré avec succès'], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Erroné'], 401);
        }

        return response()->json(['message' => 'Authentifié avec succès', 'user' => $user], 200);
    }

    public function getCheckoutUrl(Request $request)
    {
        
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $user->subscribed = 1;
        $user->save();

        $apiKey = env('LEMON_SQUEEZY_API_KEY');
        $productId = env('LEMON_SQUEEZY_PRODUCT_ID');

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.lemonsqueezy.com/v1/checkouts', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'checkout' => [
                        'product_id' => $productId,
                        'custom' => ['email' => $user->email],
                    ],
                ],
            ]);

            $checkoutData = json_decode($response->getBody(), true);

            return response()->json(['checkout_url' => $checkoutData['data']['attributes']['url']], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la création de l\'URL de paiement', 'error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $signature = $request->header('X-Signature');
        $payload = $request->getContent();

        if (!$this->isValidSignature($payload, $signature)) {
            return response()->json(['message' => 'Signature invalide'], 403);
        }

        $data = json_decode($payload, true);

        if ($data['type'] === 'subscription_created' || $data['type'] === 'subscription_updated') {
            $email = $data['data']['attributes']['custom']['email'];

            $user = User::where('email', $email)->first();
            if ($user) {
                $user->subscribed = true; 
                $user->save();
            }
        }

        return response()->json(['message' => 'Webhook traité avec succès'], 200);
    }

    private function isValidSignature($payload, $signature)
    {
        $secret = env('LEMON_SQUEEZY_WEBHOOK_SECRET');
        $hash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($hash, $signature);
    }
}
