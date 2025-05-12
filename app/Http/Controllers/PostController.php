<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kreait\Firebase\Database;

class PostController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = app('firebase.database');
    }

    /**
     * 1. Add a post (now requires skill_id)
     */
    public function addPost(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|string',
            'content'  => 'required|string',
            'skill_id' => 'required|string',
            'image'    => 'nullable|string',
            'status'   => 'nullable|string'
        ]);

        $postId    = Str::uuid()->toString();
        $createdAt = now()->toDateTimeString();

        $postData = [
            'user_id'   => $request->user_id,
            'content'   => $request->content,
            'skill_id'  => $request->skill_id,
            'image'     => $request->image ?? '',
            'created_at'=> $createdAt,
            'status'    => $request->status ?? 'active',
        ];

        $this->firebase->getReference("posts/{$postId}")
                       ->set($postData);

        return response()->json([
            'message' => 'Post created successfully',
            'post_id' => $postId,
            'data'    => $postData
        ], 201);
    }

    /**
     * Helper to enrich a single post
     */
    private function formatPost(array $post, string $postId): array
    {
        // Only active posts
        if (($post['status'] ?? '') !== 'active') {
            return [];
        }

        // Fetch user data
        $userData = $this->firebase
                         ->getReference("users/{$post['user_id']}")
                         ->getValue() ?? [];

        // Fetch skill data
        $skillData = $this->firebase
                          ->getReference("skills/{$post['skill_id']}")
                          ->getValue() ?? [];

        return [
            'post_id'      => $postId,
            'image'        => $post['image']        ?? '',
            'content'      => $post['content']      ?? '',
            'user_id'      => $post['user_id'],
            'skill_name'   => $skillData['name']    ?? '',
            'status'       => $post['status'],
            'created_at'   => $post['created_at'],      // ← added here
        ];
    }

    /**
     * 2. Get posts for a specific user (only active)
     */
    public function getUserPosts($userId)
    {
        $allPosts = $this->firebase->getReference("posts")->getValue() ?? [];
        $formatted = [];

        foreach ($allPosts as $postId => $post) {
            if (($post['user_id'] ?? '') === $userId) {
                $enriched = $this->formatPost($post, $postId);
                if (!empty($enriched)) {
                    $formatted[] = $enriched;
                }
            }
        }

        return response()->json($formatted);
    }

    /**
     * 3. Get posts by all other users (only active)
     */
    public function getOtherUsersPosts($userId)
    {
        $allPosts = $this->firebase->getReference("posts")->getValue() ?? [];
        $formatted = [];

        foreach ($allPosts as $postId => $post) {
            if (($post['user_id'] ?? '') !== $userId) {
                $enriched = $this->formatPost($post, $postId);
                if (!empty($enriched)) {
                    $formatted[] = $enriched;
                }
            }
        }

        return response()->json($formatted);
    }

    /**
     * 4. Update a post (content, image, status, skill_id)
     */
    public function updatePost(Request $request, $postId)
    {
        $postRef = $this->firebase->getReference("posts/{$postId}");
        $existing = $postRef->getValue();

        if (!$existing) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $updateData = $request->only(['content', 'image', 'status', 'skill_id']);
        $postRef->update($updateData);

        return response()->json(['message' => 'Post updated successfully']);
    }

    /**
     * 5. Delete a post
     */
    public function deletePost($postId)
    {
        $postRef = $this->firebase->getReference("posts/{$postId}");

        if (!$postRef->getValue()) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $postRef->remove();
        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * Get all active posts matching a given skill ID,
     * or all active posts if skillId is null.
     *
     * @param string $skillId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsBySkill($skillId)
    {
        $allPosts = $this->firebase->getReference('posts')->getValue() ?? [];
        $formatted = [];

        foreach ($allPosts as $postId => $post) {
            if (($post['skill_id'] ?? '') === $skillId) {
                $enriched = $this->formatPost($post, $postId);
                if (!empty($enriched)) {
                    $formatted[] = $enriched;
                }
            }
        }

        return response()->json($formatted);
    }

    
    public function getAllUserPosts($userId)
    {
        $allPosts  = $this->firebase->getReference("posts")->getValue() ?? [];
        $formatted = [];

        foreach ($allPosts as $postId => $post) {
            if (($post['user_id'] ?? '') === $userId) {
                // Fetch user
                $userData = $this->firebase
                                 ->getReference("users/{$post['user_id']}")
                                 ->getValue() ?? [];
                // Fetch skill
                $skillData = $this->firebase
                                  ->getReference("skills/{$post['skill_id']}")
                                  ->getValue() ?? [];

                $formatted[] = [
                    'post_id'    => $postId,
                    'image'      => $post['image']      ?? '',
                    'content'    => $post['content']    ?? '',
                    'user_id'    => $post['user_id'],
                    'skill_name' => $skillData['name']  ?? '',
                    'status'     => $post['status']     ?? '',
                    'created_at' => $post['created_at'] ?? '',
                ];
            }
        }

        return response()->json($formatted);
    }
}
