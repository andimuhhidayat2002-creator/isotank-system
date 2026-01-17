@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">User Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-circle"></i> Add New User
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="usersTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge 
                            @if($user->role === 'admin') bg-danger
                            @elseif($user->role === 'inspector') bg-primary
                            @elseif($user->role === 'maintenance') bg-warning
                            @elseif($user->role === 'management') bg-success
                            @elseif($user->role === 'receiver') bg-info
                            @else bg-secondary
                            @endif">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td>{{ $user->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <!-- Edit Button -->
                            <button class="btn btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal{{ $user->id }}"
                                    title="Edit User">
                                <i class="bi bi-pencil"></i>
                            </button>
                            
                            <!-- Change Role Button -->
                            <button class="btn btn-outline-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#changeRoleModal{{ $user->id }}"
                                    title="Change Role"
                                    @if($user->id === auth()->id()) disabled @endif>
                                <i class="bi bi-person-badge"></i>
                            </button>
                            
                            <!-- Reset Password Button -->
                            <button class="btn btn-outline-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#resetPasswordModal{{ $user->id }}"
                                    title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>
                            
                            <!-- Delete Button -->
                            <button class="btn btn-outline-danger" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteUserModal{{ $user->id }}"
                                    title="Delete User"
                                    @if($user->id === auth()->id()) disabled @endif>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit User: {{ $user->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" name="role" required @if($user->id === auth()->id()) disabled @endif>
                                            <option value="admin" @if($user->role === 'admin') selected @endif>Admin</option>
                                            <option value="inspector" @if($user->role === 'inspector') selected @endif>Inspector</option>
                                            <option value="maintenance" @if($user->role === 'maintenance') selected @endif>Maintenance</option>
                                            <option value="management" @if($user->role === 'management') selected @endif>Management</option>
                                            <option value="receiver" @if($user->role === 'receiver') selected @endif>Receiver</option>
                                        </select>
                                        @if($user->id === auth()->id())
                                            <input type="hidden" name="role" value="{{ $user->role }}">
                                            <small class="text-muted">You cannot change your own role</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Role Modal -->
                <div class="modal fade" id="changeRoleModal{{ $user->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.users.updateRole', $user->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h5 class="modal-title">Change Role: {{ $user->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Current Role: <strong>{{ ucfirst($user->role) }}</strong></label>
                                        <select class="form-select" name="role" required>
                                            <option value="">-- Select New Role --</option>
                                            <option value="admin" @if($user->role === 'admin') selected @endif>Admin</option>
                                            <option value="inspector" @if($user->role === 'inspector') selected @endif>Inspector</option>
                                            <option value="maintenance" @if($user->role === 'maintenance') selected @endif>Maintenance</option>
                                            <option value="management" @if($user->role === 'management') selected @endif>Management</option>
                                            <option value="receiver" @if($user->role === 'receiver') selected @endif>Receiver</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">Change Role</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Reset Password Modal -->
                <div class="modal fade" id="resetPasswordModal{{ $user->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.users.resetPassword', $user->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h5 class="modal-title">Reset Password: {{ $user->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="password" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" name="password_confirmation" required minlength="6">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-info">Reset Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete User Modal -->
                <div class="modal fade" id="deleteUserModal{{ $user->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Delete User</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete user <strong>{{ $user->name }}</strong>?</p>
                                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone!</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="inspector">Inspector</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="management">Management</option>
                            <option value="receiver">Receiver</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Search users:",
            lengthMenu: "Show _MENU_ users per page"
        }
    });
});
</script>
@endpush
