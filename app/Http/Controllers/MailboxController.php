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
        $to_id = User::where('name', $request->input('to_user'))->get();
        // Create message
        $message = MailboxModel::create([
            'from_user_id' => $user->id,
            'to_user_id' => $request->input('scope') === 'global' ? null : $to_id,
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
    public function update_view_or_star($request){
        // TODO: ...
    }
}