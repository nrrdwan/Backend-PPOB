<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedContact;

class SavedContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'provider' => 'required|string',
        ]);

        $contact = SavedContact::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'phone_number' => $request->phone_number,
            ],
            [
                'provider' => $request->provider,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Nomor berhasil disimpan ke daftar tersimpan',
            'data' => $contact,
        ]);
    }

    public function index()
    {
        $contacts = SavedContact::where('user_id', Auth::id())->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }
}