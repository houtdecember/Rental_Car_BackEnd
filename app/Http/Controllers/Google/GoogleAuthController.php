<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Laravel\Socialite\Facades\Socialite; 

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callbackGoogle()
    {
        try {

            $google_user = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('google_id', $google_user->getId())->first();

            if (!$user) {
                $user = User::where('email', $google_user->getEmail())->first();
            }

            if (!$user) {
                $user = User::create([
                    'name' => $google_user->getName(),
                    'email' => $google_user->getEmail(),
                    'google_id' => $google_user->getId(),
                    'password' => bcrypt('google-auth-password'),
                    'role' => 'user' 
                ]);
            } else {
                if (empty($user->google_id)) {
                    $user->update([
                        'google_id' => $google_user->getId()
                    ]);
                }
            }

            
            $token = $user->createToken('auth_token')->plainTextToken;

            $queryParams = http_build_query([
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role ?? 'user', 
                'token' => $token                 // Send token back to React
            ]);

            return redirect("http://localhost:5173/google-callback?" . $queryParams);

        } catch (Exception $e) {
            return redirect("http://localhost:5173/google-callback?error=auth_failed");
        }
    }
}