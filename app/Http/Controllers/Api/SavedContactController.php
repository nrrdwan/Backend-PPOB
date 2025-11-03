<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedContact;
use Illuminate\Support\Facades\Log;

class SavedContactController extends Controller
{
    /**
     * 📌 SIMPAN KONTAK BARU
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'provider' => 'required|string',
                'name' => 'nullable|string', // ✅ Tambahkan field name
            ]);

            // ✅ Cek apakah kontak sudah ada
            $existingContact = SavedContact::where('user_id', Auth::id())
                ->where('phone_number', $validated['phone_number'])
                ->where('provider', $validated['provider'])
                ->first();

            if ($existingContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kontak ini sudah tersimpan sebelumnya',
                    'data' => $existingContact,
                ], 409); // 409 Conflict
            }

            // ✅ Simpan kontak baru
            $contact = SavedContact::create([
                'user_id' => Auth::id(),
                'phone_number' => $validated['phone_number'],
                'provider' => $validated['provider'],
                'name' => $validated['name'] ?? null, // ✅ Simpan nama jika ada
            ]);

            Log::info('Kontak berhasil disimpan', [
                'user_id' => Auth::id(),
                'contact_id' => $contact->id,
                'phone_number' => $contact->phone_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil disimpan',
                'data' => $contact,
            ], 201); // 201 Created

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saat menyimpan kontak', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan kontak',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 📌 AMBIL SEMUA KONTAK USER
     */
    public function index()
    {
        try {
            $contacts = SavedContact::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc') // ✅ Urutkan dari yang terbaru
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil diambil',
                'data' => $contacts,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error saat mengambil kontak', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar kontak',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 📌 HAPUS KONTAK
     */
    public function destroy(Request $request, $id)
    {
        try {
            // ✅ Cari kontak milik user yang sedang login
            $contact = SavedContact::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kontak tidak ditemukan atau bukan milik Anda',
                ], 404);
            }

            // ✅ Hapus kontak
            $contact->delete();

            Log::info('Kontak berhasil dihapus', [
                'user_id' => Auth::id(),
                'contact_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error saat menghapus kontak', [
                'user_id' => Auth::id(),
                'contact_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kontak',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 📌 UPDATE KONTAK (OPSIONAL)
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'sometimes|required|string',
                'provider' => 'sometimes|required|string',
                'name' => 'nullable|string',
            ]);

            $contact = SavedContact::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kontak tidak ditemukan',
                ], 404);
            }

            $contact->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil diupdate',
                'data' => $contact,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saat update kontak', [
                'user_id' => Auth::id(),
                'contact_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate kontak',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}