<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of all clients.
     */
    public function index()
    {
        $clients = Client::with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $clients
        ], 200);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            // FIX: Using Rule::exists with the User model points the validator to the central_auth database
            'user_id' => ['required', Rule::exists(User::class, 'id')],
            'contact_number' => 'nullable|string|max:50',
            'client_address' => 'nullable|string',
            'tin_number' => 'nullable|string|max:50',
            'business_style' => 'nullable|string|max:255',
        ]);

        // 1. Awtomatikong i-generate ang Client Code (e.g., CL-0001)
        $latestClient = Client::latest('id')->first();
        $nextId = $latestClient ? $latestClient->id + 1 : 1;
        $clientCode = 'CL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 2. I-save ang Client kasama ang user_id at client_code
        $client = Client::create([
            'user_id' => $request->user_id,
            'client_code' => $clientCode,
            'client_name' => $request->client_name,
            'contact_person' => $request->contact_person,
            'position' => $request->position,
            'contact_number' => $request->contact_number,
            'client_address' => $request->client_address,
            'tin_number' => $request->tin_number,
            'business_style' => $request->business_style,
            'client_status' => 'Approved',
        ]);

        $client->load('user');

        return response()->json([
            'status' => 'success',
            'message' => 'Client created successfully!',
            'data' => $client
        ], 201);
    }

    /**
     * Display the specified client profile and invoice history.
     */
    public function show($id)
    {
        $client = Client::with(['invoices', 'user'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $client
        ], 200);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $request->validate([
            'client_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            // FIX: Applied the same validation rule for updates
            'user_id' => ['required', Rule::exists(User::class, 'id')],
            'contact_number' => 'nullable|string|max:50',
            'client_address' => 'nullable|string',
            'tin_number' => 'nullable|string|max:50',
            'business_style' => 'nullable|string|max:255',
            'client_status' => 'required|string|in:Approved,Pending Approval,Suspended',
        ]);

        $client->update($request->only([
            'client_name', 'contact_person', 'position', 
            'user_id', 'contact_number', 'client_address', 
            'tin_number', 'business_style', 'client_status'
        ]));

        $client->load('user');

        return response()->json([
            'status' => 'success',
            'message' => 'Client updated successfully!',
            'data' => $client
        ], 200);
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Client deleted successfully!'
        ], 200);
    }
}