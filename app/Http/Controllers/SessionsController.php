<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class SessionsController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseDb = app('firebase.database');
    }

    // 📥 Add new session (pending validation)
    public function scheduleSession(Request $request)
    {
        $data = $request->validate([
            'exchange_id' => 'required|string',
            'count' => 'required|integer|min:1',
            'time_scheduled' => 'required|date',
        ]);

        $sessionId = Str::uuid()->toString();

        $data['scheduled_at'] = now()->toIso8601String();
        $data['status'] = 'pending';
        $data['validated'] = false;

        $this->firebaseDb->getReference("sessions/{$sessionId}")->set($data);

        return response()->json(['message' => 'Session scheduled', 'id' => $sessionId]);
    }

    // 📋 Get all sessions for an exchange
    public function getSessions($exchangeId)
    {
        $sessionsRef = $this->firebaseDb->getReference('sessions')
            ->orderByChild('exchange_id')
            ->equalTo($exchangeId)
            ->getValue();

        $sessions = $sessionsRef ? array_values($sessionsRef) : [];

        return response()->json($sessions);
    }

    // ✅ Validate a pending session
    public function validateSession(Request $request, $sessionId)
    {
        $ref = $this->firebaseDb->getReference("sessions/{$sessionId}");

        $session = $ref->getValue();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $updates = [
            'validated' => true,
            'status' => 'confirmed',
        ];

        $ref->update($updates);

        return response()->json(['message' => 'Session validated']);
    }

    // ✅ Mark session as completed
    public function markSessionCompleted(Request $request, $sessionId)
    {
        $ref = $this->firebaseDb->getReference("sessions/{$sessionId}");

        $session = $ref->getValue();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $updates = [
            'status' => 'completed',
            'completed_at' => now()->toIso8601String(),
        ];

        $ref->update($updates);

        // Also increment session_done in skill_exchange
        $exchangeId = $session['exchange_id'];
        $exchangeRef = $this->firebaseDb->getReference("skill_exchange/{$exchangeId}");
        $exchangeData = $exchangeRef->getValue();

        if ($exchangeData) {
            $current = $exchangeData['session_done'] ?? 0;
            $exchangeRef->update(['session_done' => $current + 1]);
        }

        return response()->json(['message' => 'Session marked as completed']);
    }
}
