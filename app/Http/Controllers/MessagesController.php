<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kreait\Firebase\Database;

class MessagesController extends Controller
{
    protected $firebaseDb;

    public function __construct()
    {
        $this->firebaseDb = app('firebase.database');
    }

    // Get all messages
    public function getAllMessages()
    {
        $messages = $this->firebaseDb->getReference('messages')->getValue();

        $messagesWithIds = collect($messages)->map(function ($msg, $id) {
            return array_merge($msg, ['id' => $id]);
        })->values();

        return response()->json($messagesWithIds);
    }

    // Get messages by exchange ID
    public function getMessagesByExchange($exchangeId)
    {
        $messages = $this->firebaseDb->getReference('messages')->getValue();

        $filtered = collect($messages)->filter(function ($msg, $id) use ($exchangeId) {
            return isset($msg['exchange_id']) && $msg['exchange_id'] === $exchangeId;
        })->map(function ($msg, $id) {
            return array_merge($msg, ['id' => $id]);
        })->values();

        return response()->json($filtered);
    }


    // Get messages between two users
    public function getMessagesBetweenUsers($userId1, $userId2)
    {
        $messages = $this->firebaseDb->getReference('messages')->getValue();

        $filtered = collect($messages)->filter(function ($msg, $id) use ($userId1, $userId2) {
            return (
                ($msg['sender_id'] === $userId1 && $msg['receiver_id'] === $userId2) ||
                ($msg['sender_id'] === $userId2 && $msg['receiver_id'] === $userId1)
            );
        })->map(function ($msg, $id) {
            return array_merge($msg, ['id' => $id]);
        })->values();

        return response()->json($filtered);
    }

    // Send a new message
    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'exchange_id' => 'required|string',
            'sender_id' => 'required|string',
            'receiver_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $messageId = Str::uuid()->toString();

        $data['created_at'] = now()->toIso8601String();
        $data['status'] = 'sent';

        $this->firebaseDb->getReference("messages/{$messageId}")->set($data);

        return response()->json(['message' => 'Message sent', 'id' => $messageId]);
    }

    // Update message status (e.g., read, received)
    public function updateMessageStatus($id, Request $request)
    {
        $data = $request->validate([
            'status' => 'required|string|in:sent,received,read,pending',
        ]);

        $this->firebaseDb->getReference("messages/{$id}")->update($data);

        return response()->json(['message' => 'Message status updated']);
    }

    // Delete a message
    public function deleteMessage($id)
    {
        $this->firebaseDb->getReference("messages/{$id}")->remove();
        return response()->json(['message' => 'Message deleted']);
    }

    public function getLastMessageByExchange($exchangeId)
    {
        $messages = $this->firebaseDb->getReference('messages')->getValue();

        $filtered = collect($messages)->filter(function ($msg, $id) use ($exchangeId) {
            return isset($msg['exchange_id']) && $msg['exchange_id'] === $exchangeId;
        });

        if ($filtered->isEmpty()) {
            return response()->json(null);
        }

        $lastMessageId = $filtered->sortByDesc(fn($msg) => $msg['created_at'])->keys()->first();
        $lastMessage = $filtered[$lastMessageId];
        $lastMessage['id'] = $lastMessageId;

        return response()->json($lastMessage);
    }

    // Count unread received messages for a specific user
    public function countUnreadMessages($userId, $exchangeId)
    {
        $messages = $this->firebaseDb->getReference('messages')->getValue();

        $count = collect($messages)->filter(function ($msg) use ($userId, $exchangeId) {
            return isset($msg['receiver_id'], $msg['status'], $msg['exchange_id']) &&
                $msg['receiver_id'] === $userId &&
                $msg['exchange_id'] === $exchangeId &&
                $msg['status'] === 'received';
        })->count();

        return response()->json(['unread_received_count' => $count]);
    }

    public function updateMessageContent($id, Request $request)
    {
        // Accept any fields the user wants to update
        $data = $request->all();
    
        // Optional: Add validation rules dynamically or skip if flexible
        if (empty($data)) {
            return response()->json(['error' => 'No data provided'], 400);
        }
    
        $this->firebaseDb->getReference("messages/{$id}")->update($data);
    
        return response()->json(['message' => 'Message updated successfully']);
    }
    
    
}
