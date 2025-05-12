<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Providers\FirebaseService;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Models\User;
use Kreait\Firebase\Database;
use Kreait\Laravel\Firebase\Facades\Firebase;

class UserController extends Controller
{
    protected $firebaseAuth;
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseAuth = app('firebase.auth');
        $this->firebaseDb   = app('firebase.database');
    }

    // Get all users
    public function getUsers()
    {
        $users = $this->firebaseDb->getReference('users')->getValue();
        return response()->json($users);
    }

    // Get a single user by ID
    public function getUser($id)
    {
        $user = $this->firebaseDb->getReference("users/{$id}")->getValue();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user['id'] = $id;

        return response()->json($user);
    }
    // Add a user
    public function addUser(Request $request)
    {
        $newUser = $this->firebaseDb->getReference('users')->push($request->all());
        return response()->json(['message' => 'User added', 'key' => $newUser->getKey()]);
    }

    // Update a user
    public function updateUser(Request $request, $id)
    {
        $this->firebaseDb->getReference("users/{$id}")->update($request->all());
        return response()->json(['message' => 'User updated']);
    }

    // Delete a user
    public function deleteUser($id)
    {
        $this->firebaseDb->getReference("users/{$id}")->remove();
        return response()->json(['message' => 'User deleted']);
    }
}
