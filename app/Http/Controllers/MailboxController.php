<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MailboxModel;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class MailboxController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get inbox with user names
        $inbox = MailboxModel::where(function($query) use ($user) {
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
                'mailbox.is_starred', 
                'mailbox.scope', 
                'mailbox.created_at',
                'sender.name as from_user_name',
                'recipient.name as to_user_name'
            )
            ->orderBy('mailbox.created_at', 'desc')
            ->get()
            ->map(function ($item) use ($user) {
                // Handle display names
                $item->from_display = $item->from_user_id === $user->id ? 'Me' : ($item->from_user_name ?? 'Unknown');
                $item->to_display = $item->to_user_id === $user->id ? 'Me' : 
                                    ($item->scope === 'global' ? 'Global' : ($item->to_user_name ?? 'Unknown'));
                return $item;
            });
            
        // Get outbox with user names
        $outbox = MailboxModel::where('from_user_id', $user->id)
            ->leftJoin('users as recipient', 'recipient.id', '=', 'mailbox.to_user_id')
            ->select(
                'mailbox.id', 
                'mailbox.to_user_id', 
                'mailbox.subject', 
                'mailbox.content', 
                'mailbox.type', 
                'mailbox.is_read', 
                'mailbox.is_starred', 
                'mailbox.scope', 
                'mailbox.created_at',
                'recipient.name as to_user_name'
            )
            ->orderBy('mailbox.created_at', 'desc')
            ->get()
            ->map(function ($item) use ($user) {
                // Handle display names
                $item->from_display = 'Me'; // Current user is always the sender in outbox
                $item->to_display = $item->scope === 'global' ? 'Global' : ($item->to_user_name ?? 'Unknown');
                return $item;
            });
            
        return Inertia::render('Mailbox', [
            'inbox' => $inbox,
            'outbox' => $outbox,
            'currentUserId' => $user->id,
            'names' => User::pluck('name')
        ]);
    }

    public function send(Request $request)
    {
        $user = auth()->user();
        
        // Validate input
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
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'type' => $request->input('type'),
            'scope' => $request->input('scope'),
            'is_read' => false,
            'is_starred' => false,
        ]);
        
        return redirect()->back()->with('success', 'Message sent successfully!');
    }
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        // Find the message
        $message = MailboxModel::findOrFail($id);

        // Check if user owns the message
        if ($message->from_user_id !== $user->id) {
            return back()->withErrors(['message' => 'You are not authorized to edit this message']);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'to_user_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'type' => 'sometimes|in:normal,urgent,positive,negative',
            'scope' => 'nullable|in:local,global',
            'is_starred' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'to_user_id', 'subject', 'content', 'type', 'scope', 'is_starred'
        ]);

        if (isset($data['scope']) && $data['scope'] === 'global') {
            $data['to_user_id'] = null;
        }

        $message->update($data);

        return redirect()->back()->with('success', 'Message updated successfully!');
    }
    public function setToRead($id)
    {
        $user = auth()->user();
        $message = MailboxModel::findOrFail($id);
        if ($message->from_user_id !== $user->id && 
            $message->to_user_id !== $user->id && 
            $message->scope !== 'global') {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized to mark this message as read.'
            ], 403);
        }
        $message->is_read = true;
        $message->save();
        return response()->json([
            'status' => (int) $message->is_read
        ], 200);
    }
    public function starOrUnStar($id)
    {
        $user = auth()->user();
        $message = MailboxModel::findOrFail($id);
        if ($message->from_user_id !== $user->id && 
            $message->to_user_id !== $user->id && 
            $message->scope !== 'global') {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized to star/unstar this message.'
            ], 403);
        }
        $message->is_starred = !$message->is_starred;
        $message->save();
        return response()->json([
            'status' => (int) $message->is_starred
        ], 200);
    }
    public function destroy($id)
    {
        $user = auth()->user();

        // Find message
        $message = MailboxModel::findOrFail($id);

        // Allow delete only if sender is current user
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