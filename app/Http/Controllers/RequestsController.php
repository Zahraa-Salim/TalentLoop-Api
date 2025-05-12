<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class RequestsController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseDb = app('firebase.database');
    }

    // Get all requests
    public function getRequests()
    {
        $requests = $this->firebaseDb->getReference('requests')->getValue();
        return response()->json($requests);
    }

    // Get a single request by ID
    public function getRequest($id)
    {
        $requestData = $this->firebaseDb->getReference("requests/{$id}")->getValue();
        return response()->json($requestData);
    }

    // Add a new request
    public function addRequest(Request $request)
    {
        $data = $request->validate([
            'requester_id' => 'required|string',
            'requested_user_id' => 'required|string',
            'skill_id' => 'required|string',
            'interest_id' => 'required|string',
            'status' => 'nullable|string', // Defaults to 'pending' if not provided
        ]);

        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();
        $data['status'] = $data['status'] ?? 'pending';

        $newRequest = $this->firebaseDb->getReference('requests')->push($data);

        return response()->json(['message' => 'Request added', 'key' => $newRequest->getKey()]);
    }

    // Update a request
    public function updateRequest(Request $request, $id)
    {
        $data = $request->only(['status']);
        $data['updated_at'] = now()->toIso8601String();

        $this->firebaseDb->getReference("requests/{$id}")->update($data);

        return response()->json(['message' => 'Request updated']);
    }

    // Delete a request
    public function deleteRequest($id)
    {
        $this->firebaseDb->getReference("requests/{$id}")->remove();
        return response()->json(['message' => 'Request deleted']);
    }

    // Get all requests sent *by* a specific user (requester)
    public function getRequestsByRequester($userId)
    {
        $requests = $this->firebaseDb->getReference('requests')->getValue();

        $filtered = collect($requests)
            ->filter(fn($request) => $request['requester_id'] === $userId)
            ->all();

        return response()->json($filtered);
    }

    // Get all requests sent *to* a specific user (requested_user_id)
    public function getRequestsToUser($userId)
    {
        $requests = $this->firebaseDb->getReference('requests')->getValue();

        $filtered = collect($requests)
            ->filter(fn($request) => $request['requested_user_id'] === $userId)
            ->all();

        return response()->json($filtered);
    }

}
