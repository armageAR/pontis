<?php
namespace App\Http\Controllers;
use App\Models\PontisNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller {
    public function index(Request $request): JsonResponse {
        $notifications = PontisNotification::where('user_id', $request->user()->id)
            ->latest()->limit(50)->get();
        $unread = PontisNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count();
        return response()->json(['notifications' => $notifications, 'unread' => $unread]);
    }

    public function markRead(Request $request): JsonResponse {
        PontisNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }

    public function markOne(Request $request, PontisNotification $notification): JsonResponse {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->update(['read_at' => now()]);
        return response()->json($notification);
    }
}
