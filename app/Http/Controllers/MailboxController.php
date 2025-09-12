<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MailboxModel;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 10);
        
        // Get filter values
        $filterId = $request->input('filter_id');
        $filterFrom = $request->input('filter_from');
        $filterTo = $request->input('filter_to');
        $filterType = $request->input('filter_type');
        $filterRead = $request->input('filter_read');
        $filterScope = $request->input('filter_scope');
        
        // Get inbox with user names
        $inboxQuery = MailboxModel::where(function ($query) use ($user) {
                $query->where('to_user_id', $user->id)
                    ->orWhere('scope', 'global');
            })
            ->leftJoin('users as sender', 'sender.id', '=', 'mailbox.from_user_id')
            ->leftJoin('users as recipient', 'recipient.id', '=', 'mailbox.to_user_id')
            ->select(
                'mailbox.id',
                'mailbox.from_user_id',
                'mailbox.to_user_id',
                'mailbox.subject',
                'mailbox.content',
                'mailbox.type',
                'mailbox.is_read',
                'mailbox.scope',
                'mailbox.created_at',
                'sender.name as from_user_name',
                'recipient.name as to_user_name'
            );
        
        // Apply filters to inbox
        if ($filterId) {
            $inboxQuery->where('mailbox.id', $filterId);
        }
        if ($filterFrom) {
            $inboxQuery->where('sender.name', 'like', "%{$filterFrom}%");
        }
        if ($filterTo) {
            $inboxQuery->where(function($q) use ($filterTo) {
                $q->where('recipient.name', 'like', "%{$filterTo}%")
                ->orWhere(function($qq) use ($filterTo) {
                    $qq->where('mailbox.scope', 'global')
                        ->where('recipient.name', 'like', "%{$filterTo}%");
                });
            });
        }
        if ($filterType) {
            $inboxQuery->where('mailbox.type', $filterType);
        }
        if ($filterRead !== null && $filterRead !== '') {
            $inboxQuery->where('mailbox.is_read', $filterRead === 'true' || $filterRead === '1');
        }
        if ($filterScope) {
            $inboxQuery->where('mailbox.scope', $filterScope);
        }
        
        $inbox = $inboxQuery->orderBy('mailbox.created_at', 'desc')
            ->paginate($perPage, ['*'], 'inbox_page');
        
        // Get outbox with user names
        $outboxQuery = MailboxModel::where('from_user_id', $user->id)
            ->leftJoin('users as recipient', 'recipient.id', '=', 'mailbox.to_user_id')
            ->select(
                'mailbox.id',
                'mailbox.to_user_id',
                'mailbox.subject',
                'mailbox.content',
                'mailbox.type',
                'mailbox.is_read',
                'mailbox.scope',
                'mailbox.created_at',
                'recipient.name as to_user_name'
            );
        
        // Apply filters to outbox
        if ($filterId) {
            $outboxQuery->where('mailbox.id', $filterId);
        }
        if ($filterTo) {
            $outboxQuery->where(function($q) use ($filterTo) {
                $q->where('recipient.name', 'like', "%{$filterTo}%")
                ->orWhere(function($qq) use ($filterTo) {
                    $qq->where('mailbox.scope', 'global')
                        ->where('recipient.name', 'like', "%{$filterTo}%");
                });
            });
        }
        if ($filterType) {
            $outboxQuery->where('mailbox.type', $filterType);
        }
        if ($filterRead !== null && $filterRead !== '') {
            $outboxQuery->where('mailbox.is_read', $filterRead === 'true' || $filterRead === '1');
        }
        if ($filterScope) {
            $outboxQuery->where('mailbox.scope', $filterScope);
        }
        
        $outbox = $outboxQuery->orderBy('mailbox.created_at', 'desc')
            ->paginate($perPage, ['*'], 'outbox_page');
        
        // Map inbox items
        $inbox->getCollection()->transform(function ($item) use ($user) {
            $item->from_display = $item->from_user_id === $user->id
                ? 'Me'
                : ($item->from_user_name ?? 'Unknown');
            $item->to_display = $item->to_user_id === $user->id
                ? 'Me'
                : ($item->scope === 'global' ? 'Global' : ($item->to_user_name ?? 'Unknown'));
            return $item;
        });
        
        // Map outbox items
        $outbox->getCollection()->transform(function ($item) {
            $item->from_display = 'Me';
            $item->to_display = $item->scope === 'global'
                ? 'Global'
                : ($item->to_user_name ?? 'Unknown');
            return $item;
        });
        
        // Get unique values for filter dropdowns
        $types = MailboxModel::distinct()->pluck('type')->filter()->values();
        $scopes = MailboxModel::distinct()->pluck('scope')->filter()->values();
        $users = User::orderBy('name')->pluck('name');
        
        return Inertia::render('Mailbox', [
            'inbox' => $inbox,
            'outbox' => $outbox,
            'currentUserId' => $user->id,
            'names' => $users,
            'types' => $types,
            'scopes' => $scopes,
            'filters' => $request->only([
                'filter_id', 
                'filter_from', 
                'filter_to', 
                'filter_type', 
                'filter_read', 
                'filter_scope',
                'per_page'
            ]),
        ]);
    }

    public function send(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'to_user' => 'nullable|exists:users,name',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:normal,urgent,positive,negative',
            'scope' => 'required|in:local,global',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        $toUser = User::where('name', $request->input('to_user'))->first();
        MailboxModel::create([
            'from_user_id' => $user->id,
            'to_user_id' => $request->input('scope') === 'global' ? null : optional($toUser)->id,
            'subject' => $request->subject,
            'content' => $request->content,
            'type' => $request->type,
            'scope' => $request->scope,
            'is_read' => 0, // Integer
        ]);
        
        return redirect()->back()->with('success', 'Message sent successfully!');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $message = MailboxModel::findOrFail($id);
        
        if ($message->from_user_id !== $user->id) {
            return back()->withErrors(['message' => 'You are not authorized to edit this message']);
        }
        
        $validator = Validator::make($request->all(), [
            'to_user' => 'nullable|exists:users,name',
            'subject' => 'sometimes|nullable|string|max:255',
            'content' => 'sometimes|nullable|string',
            'type' => 'sometimes|in:normal,urgent,positive,negative',
            'scope' => 'sometimes|nullable|in:local,global',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        $data = $request->only(['subject', 'content', 'type', 'scope']);
        
        if ($request->has('to_user') && $request->input('scope') !== 'global') {
            $toUser = User::where('name', $request->to_user)->first();
            $data['to_user_id'] = optional($toUser)->id;
        } elseif ($request->input('scope') === 'global') {
            $data['to_user_id'] = null;
        }
        
        $message->update($data);
        
        return redirect()->back()->with('success', 'Message updated successfully!');
    }

    public function setToRead($id)
    {
        $user = auth()->user();
        $message = MailboxModel::findOrFail($id);
        
        if (
            $message->from_user_id !== $user->id &&
            $message->to_user_id !== $user->id &&
            $message->scope !== 'global'
        ) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized to mark this message as read.'
            ], 403);
        }
        
        $message->is_read = 1; // Set to integer 1
        $message->save();
        
        return response()->json(['status' => $message->is_read], 200);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $message = MailboxModel::findOrFail($id);
        
        if ($message->from_user_id !== $user->id) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized: You can only delete messages you sent.'
            ], 403);
        }
        
        $message->delete();
        
        return response()->json([
            'status' => 1,
            'message' => 'Message deleted successfully.'
        ], 200);
    }
}