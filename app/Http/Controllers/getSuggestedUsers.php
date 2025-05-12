<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class getSuggestedUsers extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = app('firebase.database');
    }

    public function getSuggestedUsers($userId)
    {
        $userSkills = $this->firebase->getReference("user_skills/{$userId}")->getValue() ?? [];
        $userInterests = $this->firebase->getReference("user_interests/{$userId}")->getValue() ?? [];

        $allUsers = $this->firebase->getReference("users")->getValue() ?? [];
        $skillsList = $this->firebase->getReference("skills")->getValue() ?? [];

        $suggestedUsers = [];
        $partialMatchUsers = [];

        foreach ($allUsers as $otherUserId => $userData) {
            if ($otherUserId === $userId) continue;

            $otherSkills = $this->firebase->getReference("user_skills/{$otherUserId}")->getValue() ?? [];
            $otherInterests = $this->firebase->getReference("user_interests/{$otherUserId}")->getValue() ?? [];

            $mutual1 = array_intersect(array_keys($userInterests), array_keys($otherSkills));
            $mutual2 = array_intersect(array_keys($userSkills), array_keys($otherInterests));

            if (!empty($mutual1) && !empty($mutual2)) {
                $suggestedUsers[] = $this->formatUserWithSkill($otherUserId, $userData, $mutual1, $skillsList);
            } elseif (!empty($mutual1)) {
                $partialMatchUsers[] = $this->formatUserWithSkill($otherUserId, $userData, $mutual1, $skillsList);
            }
        }

        if (!empty($suggestedUsers)) {
            return response()->json($suggestedUsers);
        } elseif (!empty($partialMatchUsers)) {
            return response()->json($partialMatchUsers);
        } else {
            $allOtherUsers = [];
            foreach ($allUsers as $otherUserId => $userData) {
                if ($otherUserId === $userId) continue;
                $allOtherUsers[] = $this->formatUserWithSkill($otherUserId, $userData, [], $skillsList);
            }
            return response()->json($allOtherUsers);
        }
    }

    /**
     * Format a user's basic info with a matched skill (if any)
     */
    private function formatUserWithSkill($userId, $userData, $matchedSkillIds, $skillsList)
    {
        $firstSkillId = !empty($matchedSkillIds) ? reset($matchedSkillIds) : null;
        $matchedSkillName = $firstSkillId && isset($skillsList[$firstSkillId]) 
            ? $skillsList[$firstSkillId]['name'] 
            : null;

        return [
            'id' => $userId,
            'name' => $userData['name'] ?? '',
            'avatar' => $userData['avatar'] ?? '',
            'bio' => $userData['bio'] ?? '',
            'location' => $userData['location'] ?? '',
            'email' => $userData['email'] ?? '',
            'matched_skill_name' => $matchedSkillName,
        ];
    }
}
