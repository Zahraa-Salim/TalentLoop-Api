<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;

class ReportsController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseDb = app('firebase.database');
    }

    // Get all reports
    public function getReports()
    {
        $reports = $this->firebaseDb->getReference('reports')->getValue();
        return response()->json($reports);
    }

    // Get a single report by ID
    public function getReport($id)
    {
        $report = $this->firebaseDb->getReference("reports/{$id}")->getValue();
        return response()->json($report);
    }

    // Add a new report
    public function addReport(Request $request)
    {
        $data = $request->validate([
            'reported_by_id' => 'required|string',
            'reported_user_id' => 'required|string',
            'reason' => 'required|string',
            'status' => 'nullable|string', // Default to 'pending' if not provided
        ]);

        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();
        $data['status'] = $data['status'] ?? 'pending';

        $newReport = $this->firebaseDb->getReference('reports')->push($data);

        return response()->json(['message' => 'Report added', 'key' => $newReport->getKey()]);
    }

    // Update a report
    public function updateReport(Request $request, $id)
    {
        $data = $request->only(['reason', 'status']);
        $data['updated_at'] = now()->toIso8601String();

        $this->firebaseDb->getReference("reports/{$id}")->update($data);

        return response()->json(['message' => 'Report updated']);
    }

    // Delete a report
    public function deleteReport($id)
    {
        $this->firebaseDb->getReference("reports/{$id}")->remove();
        return response()->json(['message' => 'Report deleted']);
    }
}
