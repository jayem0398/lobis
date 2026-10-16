<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the teams with their total members count.
     */
    public function index()
    {
        $teams = Team::all();

        // Cross-database safe aggregation query
        $userCounts = User::whereIn('team_id', $teams->pluck('id'))
            ->selectRaw('team_id, count(*) as count')
            ->groupBy('team_id')
            ->pluck('count', 'team_id');

        $teams->each(function ($team) use ($userCounts) {
            $team->users_count = $userCounts->get($team->id, 0);
        });

        return response()->json([
            'status' => 'success',
            'data'   => $teams,
        ], 200);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_name' => 'required|string|max:255|unique:teams,team_name',
            'quota'     => 'nullable|numeric|min:0',
        ]);

        $validated['quota'] = $validated['quota'] ?? 0;

        $team = Team::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Team created successfully!',
            'data'    => $team,
        ], 201);
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'team_name' => 'required|string|max:255|unique:teams,team_name,' . $team->id,
            'quota'     => 'nullable|numeric|min:0',
        ]);

        $team->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Team updated successfully!',
            'data'    => $team,
        ], 200);
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        // Explicitly clean up cross-database foreign key references
        User::where('team_id', $team->id)->update(['team_id' => null]);

        $team->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Team deleted successfully.',
        ], 200);
    }
}