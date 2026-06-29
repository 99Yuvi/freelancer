<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Called by the Node.js chat server (service token auth) to persist a message.
     * The sender_id is trusted because it was validated against the Sanctum token
     * during Socket.io handshake on the Node.js side.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'sender_id'       => ['required', 'integer', 'exists:users,id'],
            'body'            => ['required_without:file_path', 'nullable', 'string'],
            'type'            => ['nullable', 'in:text,file,image'],
        ]);

        $message = DB::transaction(function () use ($data) {
            $msg = Message::create([
                'conversation_id' => $data['conversation_id'],
                'sender_id'       => $data['sender_id'],
                'type'            => $data['type'] ?? 'text',
                'body'            => $data['body'] ?? null,
            ]);

            Conversation::where('id', $data['conversation_id'])
                ->update(['last_message_at' => now()]);

            return $msg;
        });

        return response()->json([
            'data' => $message->load('sender:id,name,avatar_path'),
        ], 201);
    }
}
