<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class UserInterestController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseDb = app('firebase.database');
    }

    /**
     * Get all interests for a given user.
     */
    public function getUserInterests($userId)
    {
        $userInterests = $this->firebaseDb->getReference("user_interests/{$userId}")->getValue();
        return response()->json($userInterests);
    }

    /**
     * Upsert user interests using skill IDs as keys (not generating Firebase keys).
     * The structure will be: user_interests/{user_id}/{skill_id} => true
     */
    public function upsertUserInterests(Request $request, $userId)
    {
        $validated = $request->validate([
            'interest_ids' => 'required|array',
            'interest_ids.*' => 'required|string',
        ]);

        $results = [];

        foreach ($validated['interest_ids'] as $interestId) {
            $this->firebaseDb
                ->getReference("user_interests/{$userId}/{$interestId}")
                ->set(true);

            $results[] = $interestId;
        }

        return response()->json([
            'message' => 'User interests updated successfully',
            'data' => $results,
        ], 200);
    }

    /**
     * Delete a specific user interest by skill ID.
     */
    public function deleteUserInterest($userId, $skillId)
    {
        $this->firebaseDb
            ->getReference("user_interests/{$userId}/{$skillId}")
            ->remove();

        return response()->json(['message' => 'User interest deleted'], 200);
    }
    public function getUserInterestsWithNames($userId)
    {
        // 1) Fetch the map of skill_id => true
        $raw = $this->firebaseDb
                    ->getReference("user_interests/{$userId}")
                    ->getValue() ?? [];

        $result = [];
        foreach (array_keys($raw) as $skillId) {
            // 2) Lookup the skill's name
            $skill = $this->firebaseDb
                          ->getReference("skills/{$skillId}/name")
                          ->getValue();
            $result[] = [
                'skill_id' => $skillId,
                'name'     => $skill ?? '',
            ];
        }

        return response()->json($result);
    }
}
