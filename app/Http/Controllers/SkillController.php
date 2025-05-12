<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class SkillController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        // Initialize the Firebase Database instance
        $this->firebaseDb = app('firebase.database');
    }

    /**
     * Get all skills with a specific category ID.
     *
     * @param string $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSkillsByCategory($categoryId)
    {
        // Query the 'skills' node, filtering by the 'category_id'
        $skills = $this->firebaseDb->getReference('skills')
            ->orderByChild('category_id')
            ->equalTo($categoryId)
            ->getValue();

        return response()->json($skills);
    }

    /**
     * Optionally, you can add additional methods for getting
     * a single skill, adding a new skill, updating, and deleting skills.
     */

    // Get a single skill by its ID
    public function getSkill($id)
    {
        $skill = $this->firebaseDb->getReference("skills/{$id}")->getValue();
        return response()->json($skill);
    }
    public function getSkills()
    {
        $skill = $this->firebaseDb->getReference("skills")->getValue();
        return response()->json($skill);
    }

    // Add a new skill
    public function addSkill(Request $request)
    {
        $newSkill = $this->firebaseDb->getReference('skills')->push($request->all());
        return response()->json([
            'message' => 'Skill added',
            'key' => $newSkill->getKey(),
        ]);
    }

    // Update an existing skill
    public function updateSkill(Request $request, $id)
    {
        $this->firebaseDb->getReference("skills/{$id}")->update($request->all());
        return response()->json(['message' => 'Skill updated']);
    }

    // Delete a skill
    public function deleteSkill($id)
    {
        $this->firebaseDb->getReference("skills/{$id}")->remove();
        return response()->json(['message' => 'Skill deleted']);
    }


    // Get all skills excluding the ones the user already has
    public function getAvailableSkillsForUser($userId)
{
    // Get all skills
    $allSkills = $this->firebaseDb->getReference('skills')->getValue();

    // Get the user's skills
    $userSkills = $this->firebaseDb->getReference("user_skills/{$userId}")->getValue();

    // If user has no skills, just return all skills
    if (empty($userSkills)) {
        return response()->json($allSkills);
    }

    // Extract skill IDs that the user already has
    $userSkillIds = [];
    foreach ($userSkills as $userSkill) {
        if (isset($userSkill['skill_id'])) {
            $userSkillIds[] = $userSkill['skill_id'];
        }
    }

    // Remove the user's skills from the full skills list
    $availableSkills = array_filter($allSkills, function($skill, $skillId) use ($userSkillIds) {
        return !in_array($skillId, $userSkillIds);
    }, ARRAY_FILTER_USE_BOTH);

    return response()->json($availableSkills);
}

}
