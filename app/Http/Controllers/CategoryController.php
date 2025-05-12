<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class CategoryController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        // Initialize the Firebase Database instance
        $this->firebaseDb = app('firebase.database');
    }

    /**
     * Get all categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = $this->firebaseDb->getReference('categories')->getValue();
        return response()->json($categories);
    }

    /**
     * Get a single category by its ID.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategory($id)
    {
        $category = $this->firebaseDb->getReference("categories/{$id}")->getValue();
        return response()->json($category);
    }

    /**
     * Add a new category.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCategory(Request $request)
    {
        // You can add validation here based on your requirements
        $newCategory = $this->firebaseDb->getReference('categories')->push($request->all());
        return response()->json([
            'message' => 'Category added',
            'key' => $newCategory->getKey()
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCategory(Request $request, $id)
    {
        $this->firebaseDb->getReference("categories/{$id}")->update($request->all());
        return response()->json(['message' => 'Category updated']);
    }

    /**
     * Delete a category.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCategory($id)
    {
        $this->firebaseDb->getReference("categories/{$id}")->remove();
        return response()->json(['message' => 'Category deleted']);
    }
}
