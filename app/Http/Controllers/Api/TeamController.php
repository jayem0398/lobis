<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the teams with their total members count.
     */
    public function index()
    {
        $teams = Team::withCount('users')->get();

        return response()->json([
            'status' => 'success',
            'data' => $teams
        ], 200);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_name' => 'required|string|unique:teams,team_name',
            'quota' => 'nullable|numeric|min:0'
        ]);

        $team = Team::create([
            'team_name' => $request->team_name,
            'quota' => $request->quota ?? 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Team created successfully!',
            'data' => $team
        ], 201);
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy($id)
    {
        $team = Team::findOrFail($id);
        $team->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Team deleted successfully.'
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        $request->validate([
            'team_name' => 'required|string|max:255|unique:teams,team_name,' . $id,
            'quota' => 'required|numeric|min:0',
        ]);

        $team->update([
            'team_name' => $request->team_name,
            'quota' => $request->quota,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Team updated successfully!',
            'data' => $team
        ], 200);
    }
}