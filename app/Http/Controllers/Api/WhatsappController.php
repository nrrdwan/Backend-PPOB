<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsappController extends Controller
{
    /**
     * Get WhatsApp Group Link
     * Endpoint: GET /api/whatsapp/group-link
     */
    public function getGroupLink(Request $request)
    {
        try {
            $user = $request->user(); // User yang sedang login

            // Ambil link group dari database
            $groupLink = DB::table('whatsapp_groups')
                ->where('is_active', true)
                ->where('type', 'main_group') // Bisa disesuaikan: main_group, vip_group, reseller_group
                ->first();

            if (!$groupLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link group WhatsApp tidak tersedia saat ini',
                ], 404);
            }

            // Log analytics - tracking siapa yang akses link
            DB::table('whatsapp_group_clicks')->insert([
                'user_id' => $user ? $user->id : null,
                'group_id' => $groupLink->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'clicked_at' => now(),
            ]);

            // Update counter klik
            DB::table('whatsapp_groups')
                ->where('id', $groupLink->id)
                ->increment('click_count');

            return response()->json([
                'success' => true,
                'message' => 'Link group berhasil diambil',
                'data' => [
                    'link' => $groupLink->link,
                    'group_name' => $groupLink->name,
                    'description' => $groupLink->description,
                    'is_active' => (bool) $groupLink->is_active,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting WhatsApp group link: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil link group WhatsApp',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get WhatsApp Admin Contact
     * Endpoint: GET /api/whatsapp/admin-contact
     */
    public function getAdminContact(Request $request)
    {
        try {
            $adminContact = DB::table('whatsapp_contacts')
                ->where('is_active', true)
                ->where('type', 'customer_service')
                ->first();

            if (!$adminContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kontak admin tidak tersedia',
                ], 404);
            }

            // Log tracking
            $user = $request->user();
            DB::table('whatsapp_contact_clicks')->insert([
                'user_id' => $user ? $user->id : null,
                'contact_id' => $adminContact->id,
                'ip_address' => $request->ip(),
                'clicked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kontak admin berhasil diambil',
                'data' => [
                    'phone_number' => $adminContact->phone_number,
                    'name' => $adminContact->name,
                    'default_message' => $adminContact->default_message,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting admin contact: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil kontak admin',
            ], 500);
        }
    }

    /**
     * Create/Update WhatsApp Group Link (Admin Only)
     * Endpoint: POST /api/admin/whatsapp/group-link
     */
    public function createOrUpdateGroupLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link' => 'required|url|starts_with:https://chat.whatsapp.com/',
            'name' => 'required|string|max:255',
            'type' => 'required|in:main_group,vip_group,reseller_group,info_group',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = [
                'link' => $request->link,
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'updated_at' => now(),
            ];

            // Check if group with this type already exists
            $existingGroup = DB::table('whatsapp_groups')
                ->where('type', $request->type)
                ->first();

            if ($existingGroup) {
                // Update existing
                DB::table('whatsapp_groups')
                    ->where('id', $existingGroup->id)
                    ->update($data);

                $message = 'Link group berhasil diupdate';
                $groupId = $existingGroup->id;
            } else {
                // Create new
                $data['created_at'] = now();
                $data['click_count'] = 0;
                $groupId = DB::table('whatsapp_groups')->insertGetId($data);

                $message = 'Link group berhasil dibuat';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $groupId,
                    'link' => $request->link,
                    'name' => $request->name,
                    'type' => $request->type,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error creating/updating WhatsApp group: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan link group',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get All WhatsApp Groups (Admin Only)
     * Endpoint: GET /api/admin/whatsapp/groups
     */
    public function getAllGroups(Request $request)
    {
        try {
            $groups = DB::table('whatsapp_groups')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil',
                'data' => $groups,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting all groups: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data groups',
            ], 500);
        }
    }

    /**
     * Toggle Group Status (Admin Only)
     * Endpoint: PATCH /api/admin/whatsapp/group/{id}/toggle
     */
    public function toggleGroupStatus($id)
    {
        try {
            $group = DB::table('whatsapp_groups')->where('id', $id)->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group tidak ditemukan',
                ], 404);
            }

            $newStatus = !$group->is_active;

            DB::table('whatsapp_groups')
                ->where('id', $id)
                ->update([
                    'is_active' => $newStatus,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Status group berhasil diubah',
                'data' => [
                    'id' => $id,
                    'is_active' => $newStatus,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error toggling group status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status group',
            ], 500);
        }
    }

    /**
     * Delete WhatsApp Group (Admin Only)
     * Endpoint: DELETE /api/admin/whatsapp/group/{id}
     */
    public function deleteGroup($id)
    {
        try {
            $deleted = DB::table('whatsapp_groups')->where('id', $id)->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Group berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting group: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus group',
            ], 500);
        }
    }

    /**
     * Get Analytics (Admin Only)
     * Endpoint: GET /api/admin/whatsapp/analytics
     */
    public function getAnalytics(Request $request)
    {
        try {
            $period = $request->input('period', 30); // Default 30 hari

            // Total clicks per group
            $groupClicks = DB::table('whatsapp_groups')
                ->select('id', 'name', 'type', 'click_count')
                ->orderBy('click_count', 'desc')
                ->get();

            // Clicks over time (last N days)
            $clicksOverTime = DB::table('whatsapp_group_clicks')
                ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('clicked_at', '>=', now()->subDays($period))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            // Top users (most clicks)
            $topUsers = DB::table('whatsapp_group_clicks')
                ->join('users', 'whatsapp_group_clicks.user_id', '=', 'users.id')
                ->select('users.name', 'users.email', DB::raw('COUNT(*) as click_count'))
                ->where('whatsapp_group_clicks.clicked_at', '>=', now()->subDays($period))
                ->groupBy('users.id', 'users.name', 'users.email')
                ->orderBy('click_count', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Analytics berhasil diambil',
                'data' => [
                    'period_days' => $period,
                    'group_clicks' => $groupClicks,
                    'clicks_over_time' => $clicksOverTime,
                    'top_users' => $topUsers,
                    'total_clicks' => $groupClicks->sum('click_count'),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting analytics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil analytics',
            ], 500);
        }
    }
}