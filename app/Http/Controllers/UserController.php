<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->input('role'));
            });
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $users = $query->with('roles')->paginate(15);
        return response()->json($users);
    }

    // Read - Get a specific user
    public function show(User $user)
    {
        return response()->json($user);
    }

    // Create - Add a new user
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json($user, 201);
    }

    // Update - Modify an existing user
    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
        ]);

        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        $user->update($validatedData);

        return response()->json($user);
    }

    // Delete - Remove a user
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function toggleActivation(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['message' => $user->is_active ? 'User activated' : 'User deactivated']);
    }

    public function assignRole(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->assignRole($validatedData['role']);

        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function removeRole(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->removeRole($validatedData['role']);

        return response()->json(['message' => 'Role removed successfully']);
    }

    public function getUserActivity(User $user)
    {
        $activity = $user->activities()->latest()->take(50)->get();
        return response()->json($activity);
    }

    public function bulkDelete(Request $request)
    {
        $validatedData = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        User::whereIn('id', $validatedData['user_ids'])->delete();

        return response()->json(['message' => 'Users deleted successfully']);
    }

    public function exportUsers(Request $request)
    {
        $users = User::with('roles')->get();
        $csvFileName = 'users_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID', 'Name', 'Email', 'Roles', 'Created At'];

        $callback = function () use ($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->roles->pluck('name')->implode(', '),
                    $user->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getUserStats()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $usersByRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get();

        return response()->json([
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'users_by_role' => $usersByRole
        ]);
    }
}
