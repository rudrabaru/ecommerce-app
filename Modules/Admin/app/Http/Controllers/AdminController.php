<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;
use Spatie\Permission\Models\Role;

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

        // Keep role_id column in sync with Spatie role pivot
        $roleId = Role::where('name', $validated['role'])->value('id');
        if ($roleId) {
            $user->role_id = $roleId;
            $user->save();
        }

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

        // Keep role_id column in sync with Spatie role pivot
        $roleId = Role::where('name', $validated['role'])->value('id');
        if ($roleId) {
            $user->role_id = $roleId;
            $user->save();
        }

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
        // Users (role = user) — filter by role_id to ensure consistency
        $userRoleId = Role::where('name', 'user')->value('id');
        $query = User::with('roles')
            ->when($userRoleId, function ($q) use ($userRoleId) {
                $q->where('role_id', $userRoleId);
            })
            // Normalize status for existing users: verified if email_verified_at not null
            ->select('*');

        return $dataTables->eloquent($query)
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)
                    ? $row->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : null;
            })
            ->addColumn('status', function($row){
                // Render a toggle to verify/unverify users directly from the table.
                // A user is considered verified if email_verified_at is not null.
                $checked = $row->email_verified_at ? 'checked' : '';
                $disabled = $row->hasRole('admin') ? 'disabled' : '';
                $title = $row->email_verified_at ? 'Verified' : 'Unverified';
                return '<div class="form-check form-switch" title="'.$title.'">'
                    .'<input type="checkbox" class="form-check-input js-verify-toggle" data-id="'.$row->id.'" '.$checked.' '.$disabled.'>'
                    .'</div>';
            })
            ->addColumn('actions', function($row){
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user-id="'.$row->id.'">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                if (!$row->hasRole('admin')) {
                    $btns .= '<button class="btn btn-sm btn-outline-danger delete-user js-delete" data-id="'.$row->id.'" data-delete-url="'.route('admin.users.destroy', $row->id).'">';
                    $btns .= '<i class="fas fa-trash"></i> Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['status','actions'])
            ->toJson();
    }

    public function providersIndex(): View
    {
        return view('admin::providers');
    }

    public function providersData(DataTables $dataTables)
    {
        // Providers (role = provider) — filter by role_id to ensure consistency
        $providerRoleId = Role::where('name', 'provider')->value('id');
        $query = User::with('roles')
            ->when($providerRoleId, function ($q) use ($providerRoleId) {
                $q->where('role_id', $providerRoleId);
            });

        return $dataTables->eloquent($query)
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)
                    ? $row->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                    : null;
            })
            ->addColumn('actions', function($row){
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProviderModal" data-user-id="'.$row->id.'">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                if (!$row->hasRole('admin')) {
                    $btns .= '<button class="btn btn-sm btn-outline-danger js-delete" data-id="'.$row->id.'" data-delete-url="'.route('admin.users.destroy', $row->id).'">';
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
        $providerRoleId = Role::where('name', 'provider')->value('id');
        if ($providerRoleId) {
            $user->role_id = $providerRoleId;
            $user->save();
        }
        return back()->with('status', 'User promoted to provider.');
    }

    /**
     * Toggle a user's email verification status from the Admin panel.
     * If "verify" is true, set email_verified_at to now(); otherwise null.
     */
    public function verify(Request $request, User $user)
    {
        // Do not allow verifying/unverifying admin accounts from this switch
        if ($user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change verification state of admin accounts.'
            ], 403);
        }

        $request->validate([
            'verify' => ['required','boolean']
        ]);

        $user->email_verified_at = $request->boolean('verify') ? now() : null;
        // Keep optional status column in sync if your UI uses it elsewhere
        $user->status = $request->boolean('verify') ? 'verified' : 'unverified';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $request->boolean('verify') ? 'User verified.' : 'User unverified.',
            'email_verified_at' => $user->email_verified_at,
        ]);
    }
}
