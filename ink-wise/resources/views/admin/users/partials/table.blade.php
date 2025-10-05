<div class="table-wrapper">
    <table class="table" role="grid">
        <thead>
            <tr>
                <th scope="col">Staff ID</th>
                <th scope="col">Role</th>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                @php
                    $staffProfile = $user->staff;
                    $fullName = collect([
                        optional($staffProfile)->first_name,
                        optional($staffProfile)->middle_name,
                        optional($staffProfile)->last_name,
                    ])->filter()->implode(' ');
                    $roleClass = match($user->role) {
                        'owner' => 'badge-role badge-role--owner',
                        'admin' => 'badge-role badge-role--admin',
                        default => 'badge-role badge-role--staff',
                    };
                    $status = optional($staffProfile)->status ?? $user->status;
                    $statusClass = match($status) {
                        'approved', 'active' => 'badge-status badge-status--active',
                        'pending' => 'badge-status badge-status--pending',
                        'archived' => 'badge-status badge-status--archived',
                        default => 'badge-status badge-status--inactive',
                    };
                    $rowClass = $status === 'pending' ? 'staff-row staff-row--pending' : 'staff-row';
                    $isHighlighted = request('highlight') && (int) request('highlight') === (int) $user->user_id;
                @endphp
                <tr {{ $isHighlighted ? 'id=highlighted-staff' : '' }} class="{{ $isHighlighted ? 'staff-row highlight-row' : $rowClass }}" onclick="window.location='{{ route('admin.users.show', $user->user_id) }}'">
                    <td>{{ $staffProfile->staff_id ?? '—' }}</td>
                    <td>
                        <span class="{{ $roleClass }}">{{ ucfirst($user->role) }}</span>
                    </td>
                    <td class="fw-bold">{{ $fullName ?: '—' }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="{{ $statusClass }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td class="table-actions" onclick="event.stopPropagation();">
                        @if(optional($staffProfile)->status !== 'archived')
                            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-warning">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                <span>Edit</span>
                            </a>
                            <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Archive this staff account?')" class="btn btn-danger">
                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                                    <span>Archive</span>
                                </button>
                            </form>
                        @else
                            <span class="badge-status badge-status--archived">Archived</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No staff found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
