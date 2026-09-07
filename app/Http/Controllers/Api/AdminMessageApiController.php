<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Barangay;
use App\Models\GroupMessage;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminMessageApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $admin = Auth::guard('admin')->user();

        switch ($action) {
            case 'heartbeat':
                Barangay::where('barangay_id', $request->barangay_id)
                    ->update(['last_active' => now()]);
                return response()->json(['ok' => true]);

            case 'group_accounts':
                $admins = Admin::orderBy('username')->get()->map(fn ($a) => [
                    'id' => $a->id, 'name' => $a->username, 'type' => 'admin', 'online' => true,
                    'is_me' => $a->id === $admin->id, 'is_online' => true,
                    'logo' => null, 'sub' => $a->id === $admin->id ? 'MSWD Admin (You)' : 'MSWD Admin',
                ]);
                $barangays = Barangay::with('detail')->orderBy('barangay_name')->get()->map(fn ($b) => [
                    'id' => $b->barangay_id, 'name' => $b->barangay_name, 'type' => 'barangay',
                    'online' => $b->last_active ? $b->last_active->gt(now()->subMinutes(2)) : false,
                    'is_me' => false,
                    'is_online' => $b->last_active ? $b->last_active->gt(now()->subMinutes(2)) : false,
                    'logo' => $b->detail && $b->detail->logo ? (strpos($b->detail->logo, 'uploads/') === 0 ? asset($b->detail->logo) : $b->detail->logo) : null,
                    'sub' => $b->address ?: 'Barangay account',
                ]);
                return response()->json(['ok' => true, 'accounts' => $admins->merge($barangays)->values()]);

            case 'group_fetch':
                $after = $request->integer('after', 0);
                $messages = GroupMessage::where('gm_id', '>', $after)
                    ->orderBy('gm_id')->limit(200)->get()
                    ->map(fn ($m) => $this->decorateGroupMessage($m));
                $memberCount = DB::table('group_messages')
                    ->selectRaw('COUNT(DISTINCT sender_type, sender_id) as c')->first()->c ?? 0;
                return response()->json(['ok' => true, 'messages' => $messages, 'member_count' => $memberCount]);

            case 'group_send':
                $gm = GroupMessage::create([
                    'sender_type' => 'admin',
                    'sender_id' => $admin->id,
                    'message' => $request->message,
                ]);
                $memberCount = DB::table('group_messages')
                    ->selectRaw('COUNT(DISTINCT sender_type, sender_id) as c')->first()->c ?? 0;
                return response()->json(['ok' => true, 'message' => $this->decorateGroupMessage($gm), 'member_count' => $memberCount]);

            // Legacy 1-on-1
            case 'list':
                $threads = $this->legacyThreads($admin);
                return response()->json(['ok' => true, 'threads' => $threads]);

            case 'fetch':
                $msgs = Message::where(function ($q) use ($admin, $request) {
                    $q->where('sender_type', 'admin')->where('sender_id', $admin->id)
                        ->where('receiver_type', 'barangay')->where('receiver_id', $request->barangay_id)
                        ->orWhere(function ($q2) use ($admin, $request) {
                            $q2->where('sender_type', 'barangay')->where('sender_id', $request->barangay_id)
                                ->where('receiver_type', 'admin')->where('receiver_id', $admin->id);
                        });
                })->orderBy('message_id')->get();
                Message::where('sender_type', 'barangay')->where('sender_id', $request->barangay_id)
                    ->where('receiver_type', 'admin')->where('receiver_id', $admin->id)
                    ->where('is_read', 0)->update(['is_read' => 1]);
                return response()->json(['ok' => true, 'messages' => $msgs]);

            case 'send':
                $m = Message::create([
                    'sender_type' => 'admin', 'sender_id' => $admin->id,
                    'receiver_type' => 'barangay', 'receiver_id' => $request->barangay_id,
                    'message' => $request->message, 'is_read' => 0,
                ]);
                return response()->json(['ok' => true, 'message' => $m]);

            case 'unread':
                $count = Message::where('receiver_type', 'admin')->where('receiver_id', $admin->id)
                    ->where('is_read', 0)->count();
                return response()->json(['ok' => true, 'count' => $count]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }

    private function decorateGroupMessage(GroupMessage $m)
    {
        $name = $m->sender_type === 'admin'
            ? (Admin::find($m->sender_id)->username ?? 'Admin')
            : (Barangay::find($m->sender_id)->barangay_name ?? 'Barangay');
        return [
            'gm_id' => $m->gm_id,
            'sender_type' => $m->sender_type,
            'sender_id' => $m->sender_id,
            'sender_name' => $name,
            'message' => $m->message,
            'created_at' => $m->created_at ? $m->created_at->toDateTimeString() : null,
        ];
    }

    private function legacyThreads($admin)
    {
        $barangays = Barangay::orderBy('barangay_name')->get();
        $threads = [];
        foreach ($barangays as $b) {
            $last = Message::where(function ($q) use ($admin, $b) {
                $q->where(function ($x) use ($admin, $b) {
                    $x->where('sender_type', 'admin')->where('sender_id', $admin->id)
                        ->where('receiver_type', 'barangay')->where('receiver_id', $b->barangay_id);
                })->orWhere(function ($x) use ($admin, $b) {
                    $x->where('sender_type', 'barangay')->where('sender_id', $b->barangay_id)
                        ->where('receiver_type', 'admin')->where('receiver_id', $admin->id);
                });
            })->orderByDesc('message_id')->first();
            $threads[] = [
                'barangay_id' => $b->barangay_id,
                'name' => $b->barangay_name,
                'last_message' => $last->message ?? null,
                'last_at' => $last->created_at ? $last->created_at->format('M d h:i A') : null,
            ];
        }
        return $threads;
    }
}
