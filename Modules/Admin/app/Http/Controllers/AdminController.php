<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin::users');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::all();
        return response()->json($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'string', 'exists:roles,name']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('User created successfully')
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', __('User created'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $roles = \Spatie\Permission\Models\Role::all();
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'user' => $user,
                'roles' => $roles
            ]);
        }
        
        return view('admin::edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
                'password' => ['nullable', 'string', 'min:8'],
                'role' => ['required', 'string', 'exists:roles,name']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('User updated successfully')
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', __('User updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting admin users
        if ($user->hasRole('admin')) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cannot delete admin users')
                ], 403);
            }
            return redirect()->back()->withErrors(['user' => __('Cannot delete admin users')]);
        }
        
        $user->delete();
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('User deleted successfully')
            ]);
        }
        
        return redirect()->route('admin.users.index')->with('status', __('User deleted'));
    }

    public function data(DataTables $dataTables)
    {
        $query = User::with('roles');
        return $dataTables->eloquent($query)
            ->addColumn('role', fn($row) => $row->roles->pluck('name')->implode(', '))
            ->addColumn('actions', function($row){
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-user" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal('.$row->id.')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                
                if (!$row->hasRole('admin')) {
                    $btns .= '<button class="btn btn-sm btn-outline-danger delete-user" data-id="'.$row->id.'" onclick="deleteUser('.$row->id.')">';
                    $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                }
                
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function promoteToProvider(User $user): RedirectResponse
    {
        if ($user->hasRole('admin')) {
            return back()->withErrors(['user' => 'Cannot change admin role.']);
        }
        $user->syncRoles(['provider']);
        return back()->with('status', 'User promoted to provider.');
    }
}
