<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Barangay;
use App\Models\GroupMessage;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayMessageApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('manage_messages')) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to access messages.'], 403);
        }
        $id = $barangay->barangay_id;

        switch ($action) {
            case 'heartbeat':
                $barangay->forceFill(['last_active' => now()])->save();
                return response()->json(['ok' => true]);

            case 'group_accounts':
                $accounts = [
                    [
                        'id' => 0,
                        'name' => 'DSWD - Municipal Office',
                        'logo' => 'mapa.png',
                        'is_online' => true,
                        'sub' => 'Municipal Office',
                        'address' => 'Municipality of Malilipot',
                    ],
                ];
                $barangays = Barangay::with('detail')->orderBy('barangay_name')->get();
                foreach ($barangays as $b) {
                    $online = $b->last_active ? $b->last_active->gt(now()->subSeconds(120)) : false;
                    $sub = $online
                        ? 'Online'
                        : ($b->last_active ? 'Last active ' . $b->last_active->diffForHumans() : 'Offline');
                    $accounts[] = [
                        'id' => $b->barangay_id,
                        'name' => $b->barangay_name,
                        'logo' => $b->detail->logo ?? null,
                        'is_online' => $online,
                        'sub' => $sub,
                        'address' => $b->address,
                        'is_me' => (int) $b->barangay_id === (int) $id,
                    ];
                }
                return response()->json(['ok' => true, 'accounts' => $accounts]);

            case 'group_fetch':
                $after = $request->integer('after', 0);
                $messages = GroupMessage::where('gm_id', '>', $after)
                    ->orderBy('gm_id')
                    ->limit(200)
                    ->get()
                    ->map(fn ($m) => $this->decorateGroupMessage($m, $id))
                    ->values();
                return response()->json([
                    'ok' => true,
                    'messages' => $messages,
                    'member_count' => Barangay::count() + 1,
                ]);

            case 'group_send':
                $message = trim((string) $request->input('message', ''));
                if ($message === '') {
                    return response()->json(['ok' => false, 'error' => 'Message cannot be empty.']);
                }
                $gm = GroupMessage::create([
                    'sender_type' => 'barangay',
                    'sender_id' => $id,
                    'message' => substr($message, 0, 2000),
                ]);
                return response()->json([
                    'ok' => true,
                    'message_id' => $gm->gm_id,
                    'server_time' => now()->format('M d, Y h:i A'),
                ]);

            case 'fetch':
                $messages = Message::where(function ($q) use ($id) {
                    $q->where(function ($x) use ($id) {
                        $x->where('sender_type', 'barangay')->where('sender_id', $id)
                            ->where('receiver_type', 'admin');
                    })->orWhere(function ($x) use ($id) {
                        $x->where('sender_type', 'admin')
                            ->where('receiver_type', 'barangay')->where('receiver_id', $id);
                    });
                })->orderBy('message_id')->get();
                Message::where('receiver_type', 'barangay')->where('receiver_id', $id)
                    ->where('is_read', 0)
                    ->update(['is_read' => 1]);
                return response()->json(['ok' => true, 'messages' => $messages]);

            case 'send':
                $m = Message::create([
                    'sender_type' => 'barangay',
                    'sender_id' => $id,
                    'receiver_type' => 'admin',
                    'receiver_id' => 1,
                    'message' => (string) $request->input('message', ''),
                    'is_read' => 0,
                ]);
                return response()->json(['ok' => true, 'message' => $m]);

            case 'unread':
                $count = Message::where('receiver_type', 'barangay')->where('receiver_id', $id)
                    ->where('is_read', 0)
                    ->count();
                return response()->json(['ok' => true, 'count' => $count]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }

    private function decorateGroupMessage(GroupMessage $message, int $myId): array
    {
        $senderName = 'Unknown';
        $senderLogo = null;
        if ($message->sender_type === 'admin') {
            $senderName = Admin::find($message->sender_id)->username ?? 'DSWD - Municipal Office';
            $senderLogo = 'mapa.png';
        } else {
            $b = Barangay::with('detail')->find($message->sender_id);
            $senderName = $b->barangay_name ?? 'Barangay';
            $senderLogo = $b->detail->logo ?? null;
        }

        return [
            'gm_id' => $message->gm_id,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id,
            'sender_name' => $senderName,
            'sender_logo' => $senderLogo,
            'message' => $message->message,
            'created_at' => ($message->created_at ?? now())->format('Y-m-d H:i:s'),
            'own' => $message->sender_type === 'barangay' && (int) $message->sender_id === (int) $myId,
        ];
    }
}