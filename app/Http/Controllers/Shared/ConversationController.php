<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /** List conversations for the authenticated user, ordered by last message */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('client_id', $user->id)
            ->orWhere('freelancer_id', $user->id)
            ->with([
                'contract:id,project_id,status',
                'contract.project:id,title',
                'client:id,name,avatar_path',
                'freelancer:id,name,avatar_path',
                'latestMessage',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->whereNull('read_at')->where('sender_id', '!=', $user->id);
            }])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json(['data' => $conversations]);
    }

    /** Single conversation detail */
    public function show(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->isParty($request->user()), 403);

        return response()->json([
            'data' => $conversation->load([
                'contract:id,project_id,status,total_amount',
                'contract.project:id,title',
                'client:id,name,avatar_path',
                'freelancer:id,name,avatar_path',
            ]),
        ]);
    }

    /** Cursor-paginated messages, newest first */
    public function messages(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->isParty($request->user()), 403);

        $perPage = (int) $request->get('per_page', 50);
        $cursor  = $request->get('cursor');

        $query = $conversation->messages()
            ->with('sender:id,name,avatar_path')
            ->orderByDesc('created_at');

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $messages = $query->limit($perPage + 1)->get();
        $hasMore  = $messages->count() > $perPage;
        $items    = $hasMore ? $messages->slice(0, $perPage) : $messages;
        $nextCursor = $hasMore ? $items->last()?->id : null;

        return response()->json([
            'data' => $items->values(),
            'meta' => ['next_cursor' => $nextCursor, 'has_more' => $hasMore],
        ]);
    }

    /** Mark all unread messages in this conversation as read */
    public function markRead(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->isParty($request->user()), 403);

        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }
}
