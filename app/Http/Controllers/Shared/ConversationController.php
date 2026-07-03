<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'webm', 'mov'],
        'file'  => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'],
    ];

    private const SIZE_LIMITS_MB = ['image' => 6, 'video' => 20, 'file' => 25];

    /** Paginated conversation list for the authenticated user, ordered by last message */
    public function index(Request $request)
    {
        $user    = $request->user();
        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $search  = trim((string) $request->get('search', ''));

        $query = Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)->orWhere('freelancer_id', $user->id);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                       ->orWhereHas('freelancer', fn ($f) => $f->where('name', 'like', "%{$search}%"))
                       ->orWhereHas('contract.project', fn ($p) => $p->where('title', 'like', "%{$search}%"));
                });
            })
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
            ->orderByDesc('id');

        $page = $query->paginate($perPage);

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'has_more'     => $page->hasMorePages(),
                'next_page'    => $page->hasMorePages() ? $page->currentPage() + 1 : null,
                'total'        => $page->total(),
            ],
        ]);
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

    /** Upload a file attachment for this conversation */
    public function upload(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->isParty($request->user()), 403);

        $allExts = implode(',', array_merge(...array_values(self::ALLOWED_EXTENSIONS)));

        $request->validate([
            'file' => ['required', 'file', 'max:25600', "mimes:{$allExts}"],
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $type = $this->detectFileType($ext);

        // Per-type size enforcement
        $limitBytes = self::SIZE_LIMITS_MB[$type] * 1024 * 1024;
        if ($file->getSize() > $limitBytes) {
            return response()->json([
                'message' => 'The file size exceeds the limit.',
                'errors'  => ['file' => [$type === 'image'
                    ? 'Images must be under 6 MB.'
                    : ($type === 'video' ? 'Videos must be under 20 MB.' : 'Documents must be under 25 MB.')]],
            ], 422);
        }

        $path = $file->storeAs(
            "chat/{$conversation->id}",
            Str::uuid() . '.' . $ext,
            'public'
        );

        return response()->json([
            'type'      => $type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'url'       => Storage::disk('public')->url($path),
        ]);
    }

    private function detectFileType(string $ext): string
    {
        foreach (self::ALLOWED_EXTENSIONS as $type => $exts) {
            if (in_array($ext, $exts)) return $type;
        }
        return 'file';
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
