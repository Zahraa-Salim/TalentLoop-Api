<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Database;
use Kreait\Laravel\Firebase\Facades\Firebase;

class AuthController extends Controller
{
    protected $firebaseAuth;
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseAuth = app('firebase.auth');
        $this->firebaseDb   = app('firebase.database');
    }

    public function login(Request $request)
    {
        try {
            // 1. Obtain and verify the Firebase ID token
            $idToken = $request->bearerToken();
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
    
            // 2. Retrieve the Firebase user details
            $firebaseUser = $this->firebaseAuth->getUser($uid);
    
            // 3. Prepare the user data to be stored in Firebase Realtime Database
            $userData = [
                'name'       => $firebaseUser->displayName ?? '',  // Set the user's name
                'email'      => $firebaseUser->email ?? '',
                'created_at' => now()->toDateTimeString()
            ];
    
            // 4. Write the user data to "users/{uid}".
            $this->firebaseDb->getReference("users/{$uid}")->set($userData);
    
            return response()->json([
                'message' => 'User authenticated and saved.',
                'user'    => $userData
            ], 200);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
    
            // Option 1. Check if the message mentions authentication credentials.
            if (stripos($errorMessage, 'auth credentials') !== false) {
                $errorMessage = 'Authentication error: The supplied auth credentials are incorrect.';
            }
    
            // Option 2. (Alternative) Catch specific Firebase exceptions if available:
            // catch (\Firebase\Auth\Token\Exception\InvalidToken $e) {
            //    $errorMessage = 'Authentication error: The supplied auth credentials are incorrect.';
            // }
    
            return response()->json([
                'error' => $errorMessage
            ], 401);
        }
    }    
}
