<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;
use Illuminate\Support\Facades\Log;

class UserExchangesController extends Controller
{
    protected $database;

    public function __construct()
    {
        $this->database = app('firebase.database');
    }

    // 🔍 Get all skill exchanges for a given user
    public function getUserExchanges($userId)
    {
        $ref = $this->database->getReference('skill_exchange');
        $snapshot = $ref->getValue();

        $userExchanges = [];

        if ($snapshot) {
            foreach ($snapshot as $exchangeId => $exchange) {
                if ($exchange['user1_id'] === $userId || $exchange['user2_id'] === $userId) {
                    $otherUserId = $exchange['user1_id'] === $userId ? $exchange['user2_id'] : $exchange['user1_id'];
                    $userSkill = $exchange['user1_id'] === $userId ? $exchange['skill1_id'] : $exchange['skill2_id'];
                    $otherSkill = $exchange['user1_id'] === $userId ? $exchange['skill2_id'] : $exchange['skill1_id'];

                    $userExchanges[] = [
                        'exchange_id' => $exchangeId,
                        'status' => $exchange['status'] ?? null,
                        'created_at' => $exchange['created_at'] ?? null,
                        'updated_at' => $exchange['updated_at'] ?? null,
                        'other_user_id' => $otherUserId,
                        'your_skill_id' => $userSkill,
                        'other_skill_id' => $otherSkill,
                        'sessions_needed' => $exchange['sessions_needed'] ?? null,
                        'sessions_done' => $exchange['sessions_done'] ?? null,
                    ];
                }
            }
        }

        return response()->json($userExchanges);
    }

    // ✏️ Update a specific exchange
    public function updateExchange(Request $request, $exchangeId)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string',
            'sessions_needed' => 'sometimes|integer',
            'updated_at' => 'sometimes'
        ]);

        $ref = $this->database->getReference('skill_exchange/' . $exchangeId);
        
        // Update only provided fields
        foreach ($validated as $key => $value) {
            $ref->getChild($key)->set($value);
        }

        return response()->json(['message' => 'Exchange updated successfully']);
    }

    // ❌ Delete a specific exchange
    public function deleteExchange($exchangeId)
    {
        $ref = $this->database->getReference('skill_exchange/' . $exchangeId);
        $ref->remove();

        return response()->json(['message' => 'Exchange deleted']);
    }

    public function addExchange(Request $request)
    {
        $data = $request->only([
            'user1_id',
            'user2_id',
            'skill1_id',
            'skill2_id',
            'status',
        ]);

        // Basic validation (you can use Laravel's validation features instead)
        if (
            empty($data['user1_id']) ||
            empty($data['user2_id']) ||
            empty($data['skill1_id']) ||
            empty($data['skill2_id'])
        ) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        // Set timestamps
        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();
        $data['status'] = $data['status'] ?? 'ongoing';

        // Push to Firebase
        $ref = $this->database->getReference('skill_exchange')->push($data);

        return response()->json([
            'message' => 'Exchange added successfully',
            'exchange_id' => $ref->getKey(),
        ]);
    }

    public function getExchangeById($exchangeId)
    {
        $ref = $this->database->getReference('skill_exchange/' . $exchangeId);
        $exchange = $ref->getValue();

        if (!$exchange) {
            return response()->json(['error' => 'Exchange not found'], 404);
        }

        // Add the ID into the response
        $exchange['exchange_id'] = $exchangeId;

        return response()->json($exchange);
}

}
