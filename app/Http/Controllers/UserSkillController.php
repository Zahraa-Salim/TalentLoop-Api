<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class UserSkillController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        // Initialize the Firebase Database instance using Laravel's Firebase binding.
        $this->firebaseDb = app('firebase.database');
    }

    /**
     * Get all skills for a given user.
     *
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSkills($userId)
    {
        $userSkills = $this->firebaseDb->getReference("user_skills/{$userId}")->getValue();
        return response()->json($userSkills);
    }

    /**
     * Add (or upsert) individual user skills.
     *
     * Expects a JSON payload like:
     *
     * {
     *   "skills": [
     *     {
     *       "skill_id": "123",
     *       "proficiency": "Advanced",
     *       "year_acquired": "2015"
     *     },
     *     {
     *       "skill_id": "456",
     *       "proficiency": "Intermediate",
     *       "year_acquired": "2018"
     *     }
     *   ]
     * }
     *
     * Each skill is stored individually at a deterministic path using the skill_id as the key.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function upsertUserSkills(Request $request, $userId)
    {
        $validated = $request->validate([
            'skills' => 'required|array',
            'skills.*.skill_id' => 'required',
            'skills.*.proficiency' => 'required|string',
            'skills.*.year_acquired' => 'required|string',
        ]);

        // Use the existing firebaseDb property
        $userSkillsRef = $this->firebaseDb->getReference("user_skills/{$userId}");

        // Optional: remove previous skills if you want to overwrite all existing skills.
        // $userSkillsRef->remove();

        $results = [];
        foreach ($validated['skills'] as $skill) {
            // Store the data under the key of the skill_id.
            $userSkillsRef->getChild($skill['skill_id'])->set([
                'proficiency'   => $skill['proficiency'],
                'year_acquired' => $skill['year_acquired'],
            ]);
            $results[] = $skill;
        }

        return response()->json([
            'message' => 'Skills saved to Firebase',
            'skills' => $results,
        ], 200);
    }

    /**
     * Delete a specific skill for a user.
     *
     * @param string $userId
     * @param string $skillKey  // Unique key for the skill record or the skill_id if using deterministic keys
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUserSkill($userId, $skillKey)
    {
        $this->firebaseDb->getReference("user_skills/{$userId}/{$skillKey}")->remove();
        return response()->json(['message' => 'User skill deleted'], 200);
    }

    public function getUserSkillsWithNames($userId)
    {
        // 1) Get all skills for the user (with proficiency and year_acquired)
        $userSkills = $this->firebaseDb
                        ->getReference("user_skills/{$userId}")
                        ->getValue() ?? [];

        $result = [];

        foreach ($userSkills as $skillId => $details) {
            // 2) Get the skill's name (and optionally other details)
            $skillData = $this->firebaseDb
                            ->getReference("skills/{$skillId}")
                            ->getValue();

            $result[] = [
                'skill_id'       => $skillId,
                'name'           => $skillData['name'] ?? '',
                'proficiency'    => $details['proficiency'] ?? '',
                'year_acquired'  => $details['year_acquired'] ?? '',
                // Optional: include description/category if you want
                'description' => $skillData['description'] ?? '',
                'category_id' => $skillData['category_id'] ?? '',
            ];
        }

        return response()->json($result);
    }

}
