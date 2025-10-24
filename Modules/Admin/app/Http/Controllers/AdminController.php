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
     * Show the specified resource.
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'data' => []]);
        }
        return view('admin::users');
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
                'role' => ['required', 'in:user,provider']
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

        // Set role
        $user->assignRole($validated['role']);
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
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($user);
        }
        
        return view('admin::users');
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
                'role' => ['required', 'in:user,provider']
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

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        
        $user->save();

        // Update role
        $user->syncRoles([$validated['role']]);
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
            ->addColumn('status', function ($row) {
                // Render a toggle to verify/unverify users directly from the table.
                // A user is considered verified if email_verified_at is not null.
                $checked = $row->email_verified_at ? 'checked' : '';
                $disabled = $row->hasRole('admin') ? 'disabled' : '';
                $title = $row->email_verified_at ? 'Verified' : 'Unverified';
                return '<div class="form-check form-switch" title="'.$title.'">'
                    .'<input type="checkbox" class="form-check-input js-verify-toggle" data-id="'.$row->id.'" '.$checked.' '.$disabled.'>'
                    .'</div>';
            })
            ->addColumn('actions', function ($row) {
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
            ->rawColumns(['status','actions'])
            ->toJson();
    }

    public function providersIndex(): View
    {
        return view('admin::providers');
    }

    /**
     * Show the form for creating a new provider.
     */
    public function createProvider()
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'data' => []]);
        }
        return view('admin::providers');
    }

    /**
     * Store a newly created provider.
     */
    public function storeProvider(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8']
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

        // Set provider role
        $user->assignRole('provider');
        $providerRoleId = Role::where('name', 'provider')->value('id');
        if ($providerRoleId) {
            $user->role_id = $providerRoleId;
            $user->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Provider created successfully')
            ]);
        }

        return redirect()->route('admin.providers.index')->with('status', __('Provider created'));
    }

    /**
     * Show the form for editing the specified provider.
     */
    public function editProvider($id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($user);
        }
        
        return view('admin::providers');
    }

    /**
     * Update the specified provider.
     */
    public function updateProvider(Request $request, $id)
    {
        $user = User::findOrFail($id);

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
                'password' => ['nullable', 'string', 'min:8']
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

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        
        $user->save();

        // Ensure provider role is maintained
        $user->syncRoles(['provider']);
        $providerRoleId = Role::where('name', 'provider')->value('id');
        if ($providerRoleId) {
            $user->role_id = $providerRoleId;
            $user->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Provider updated successfully')
            ]);
        }

        return redirect()->route('admin.providers.index')->with('status', __('Provider updated'));
    }

    /**
     * Remove the specified provider.
     */
    public function destroyProvider($id)
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
                'message' => __('Provider deleted successfully')
            ]);
        }

        return redirect()->route('admin.providers.index')->with('status', __('Provider deleted'));
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
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-outline-primary edit-provider" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#providerModal" onclick="openProviderModal('.$row->id.')">';
                $btns .= '<i class="fas fa-edit"></i> Edit</button>';
                if (!$row->hasRole('admin')) {
                    $btns .= '<button class="btn btn-sm btn-outline-danger delete-provider" data-id="'.$row->id.'" onclick="deleteProvider('.$row->id.')">';
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
