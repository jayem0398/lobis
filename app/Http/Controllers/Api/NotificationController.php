<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    /**
     * Auto-ensure notifications table exists in database.
     */
    private function ensureTableExists(): void
    {
        try {
            if (!Schema::hasTable('notifications')) {
                Schema::create('notifications', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->string('type')->default('general');
                    $table->string('title');
                    $table->text('message');
                    $table->unsignedBigInteger('invoice_id')->nullable();
                    $table->string('invoice_number')->nullable();
                    $table->boolean('is_read')->default(false);
                    $table->timestamps();
                });
            } else {
                // Drop foreign key if it exists to allow cross-database central_auth user_ids
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE notifications DROP FOREIGN KEY notifications_user_id_foreign");
                } catch (\Throwable $e) {
                    // Ignored: foreign key already dropped or constraint name differs
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ensureTableExists warning: ' . $e->getMessage());
        }
    }

    /**
     * Get unread notifications for the logged-in user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([], 401);
        }

        $this->ensureTableExists();

        try {
            $notifications = AppNotification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'notifications' => $notifications,
                'unread_count'  => $notifications->where('is_read', false)->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());
            return response()->json([
                'notifications' => [],
                'unread_count'  => 0,
            ]);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json([], 401);

        $this->ensureTableExists();

        try {
            AppNotification::where('id', $id)->where('user_id', $user->id)->update(['is_read' => true]);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json([], 401);

        $this->ensureTableExists();

        try {
            AppNotification::where('user_id', $user->id)->update(['is_read' => true]);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Trigger a test notification via Laravel Reverb.
     */
    public function testNotification(Request $request)
    {
        $this->ensureTableExists();

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated. Please log in first.',
                ], 401);
            }

            $notif = AppNotification::create([
                'user_id'        => $user->id,
                'type'           => 'system_alert',
                'title'          => 'Real-time Reverb Test',
                'message'        => 'LOBIS notification via Laravel Reverb is working live! 🚀',
                'invoice_id'     => null,
                'invoice_number' => null,
                'is_read'        => false,
            ]);

            $broadcasted = false;
            $broadcastError = null;

            try {
                broadcast(new \App\Events\NotificationCreated($notif));
                $broadcasted = true;
            } catch (\Throwable $e) {
                $broadcastError = $e->getMessage();
                Log::warning('Broadcast dispatch in testNotification: ' . $e->getMessage());
            }

            return response()->json([
                'status'          => 'success',
                'notification'    => $notif,
                'broadcasted'     => $broadcasted,
                'broadcast_error' => $broadcastError,
            ]);
        } catch (\Throwable $e) {
            Log::error('testNotification failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'location' => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }
}
