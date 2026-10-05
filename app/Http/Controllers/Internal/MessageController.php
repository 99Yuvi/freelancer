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
            'type'            => ['nullable', 'in:text,file,image,video'],
            'file_path'       => ['nullable', 'string', 'max:500'],
            'file_name'       => ['nullable', 'string', 'max:255'],
            'file_size'       => ['nullable', 'integer'],
        ]);

        $message = DB::transaction(function () use ($data) {
            $msg = Message::create([
                'conversation_id' => $data['conversation_id'],
                'sender_id'       => $data['sender_id'],
                'type'            => $data['type'] ?? 'text',
                'body'            => $data['body'] ?? null,
                'file_path'       => $data['file_path'] ?? null,
                'file_name'       => $data['file_name'] ?? null,
                'file_size'       => $data['file_size'] ?? null,
            ]);

            Conversation::where('id', $data['conversation_id'])
                ->update(['last_message_at' => now()]);

            return $msg;
        });

        // refresh() pulls DB-default created_at (model has $timestamps = false)
        return response()->json([
            'data' => $message->refresh()->load('sender:id,name,avatar_path'),
        ], 201);
    }
}
