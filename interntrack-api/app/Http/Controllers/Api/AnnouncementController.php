<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Get announcements for the current user.
     * 
     * GET /api/announcements
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $announcements = Announcement::active()
            ->published()
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($announcement) use ($user) {
                return $announcement->isVisibleTo($user);
            })
            ->map(function ($announcement) use ($user) {
                $isRead = $announcement->readers()
                    ->where('user_id', $user->id)
                    ->exists();
                    
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'created_at' => $announcement->created_at,
                    'is_read' => $isRead,
                ];
            })
            ->values();
            
        return response()->json([
            'success' => true,
            'announcements' => $announcements,
            'unread_count' => $announcements->where('is_read', false)->count(),
        ]);
    }
    
    /**
     * Mark an announcement as read.
     * 
     * POST /api/announcements/{id}/read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $announcement = Announcement::findOrFail($id);
        
        $announcement->markAsReadBy($user);
        
        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read.',
        ]);
    }
    
    /**
     * Mark all announcements as read.
     * 
     * POST /api/announcements/read-all
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        $announcements = Announcement::active()
            ->published()
            ->get()
            ->filter(function ($announcement) use ($user) {
                return $announcement->isVisibleTo($user);
            });
            
        foreach ($announcements as $announcement) {
            $announcement->markAsReadBy($user);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'All announcements marked as read.',
        ]);
    }
}
