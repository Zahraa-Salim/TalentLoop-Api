<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class SearchController extends Controller
{

    protected $database;

    public function __construct()
    {
        $this->database = app('firebase.database');
    }

    public function search(Request $request)
    {
        $query = strtolower($request->input('query', ''));

        // Fetch all posts
        $postsSnapshot = $this->database->getReference('posts')->getSnapshot();
        $posts = $postsSnapshot->getValue() ?? [];

        // Filter posts by content or title
        $filteredPosts = array_filter($posts, function ($post) use ($query) {
            return isset($post['content']) && stripos($post['content'], $query) !== false ||
                   isset($post['title']) && stripos($post['title'], $query) !== false;
        });

        // Fetch all users
        $usersSnapshot = $this->database->getReference('users')->getSnapshot();
        $users = $usersSnapshot->getValue() ?? [];

        // Filter users by name or email
        $filteredUsers = array_filter($users, function ($user) use ($query) {
            return isset($user['name']) && stripos($user['name'], $query) !== false ||
                   isset($user['email']) && stripos($user['email'], $query) !== false;
        });

        return response()->json([
            'posts' => $filteredPosts,
            'users' => $filteredUsers,
        ]);
    }
}
