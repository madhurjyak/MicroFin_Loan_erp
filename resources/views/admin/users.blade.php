@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', '👥 Staff & User Management')

@section('content')
<div id="usersApp">

{{-- ── Role Summary Cards ── --}}
<div class="dashboard-panels-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 32px;">
    <div class="metric-card" style="margin-bottom: 0;">
        <p style="font-size: 11px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 4px 0;">Total Staff</p>
        <p style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin: 0;">{{ $staff->count() }}</p>
    </div>
    <div class="metric-card" style="margin-bottom: 0;">
        <div style="margin-bottom: 4px;">
            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #f3e8ff; color: #7e22ce;">Admin</span>
        </div>
        <p style="font-size: 24px; font-weight: 700; color: #9333ea; margin: 0;">{{ $roleCounts['admin'] ?? 0 }}</p>
    </div>
    <div class="metric-card" style="margin-bottom: 0;">
        <div style="margin-bottom: 4px;">
            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #dbeafe; color: #1d4ed8;">Manager</span>
        </div>
        <p style="font-size: 24px; font-weight: 700; color: #2563eb; margin: 0;">{{ $roleCounts['manager'] ?? 0 }}</p>
    </div>
    <div class="metric-card" style="margin-bottom: 0;">
        <div style="margin-bottom: 4px;">
            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #d1fae5; color: #047857;">Agent</span>
        </div>
        <p style="font-size: 24px; font-weight: 700; color: #059669; margin: 0;">{{ $roleCounts['agent'] ?? 0 }}</p>
    </div>
</div>

{{-- ── Staff Table ── --}}
<div class="panel" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-weight: 600; color: var(--text-primary); margin: 0; font-size: 16px;">All Staff Accounts</h3>
        <button id="btnCreateStaff" class="btn-primary" style="padding: 8px 16px; font-size: 14px;">
            + Add Staff Member
        </button>
    </div>

    <div class="table-responsive">
        <table id="staffTable" class="data-table">
            <thead>
                <tr style="background: rgba(15, 23, 42, 0.02);">
                    <th class="text-left" style="padding-left: 24px;">Name</th>
                    <th class="text-left">Email</th>
                    <th class="text-center">Role</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Created</th>
                    <th class="text-center" style="padding-right: 24px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- AJAX Data -->
            </tbody>
        </table>
    </div>
</div>

{{-- ── Create User Modal ── --}}
<div id="createModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px;">👤 Add New Staff Member</h3>

        <form method="POST" action="{{ route('admin.users.create') }}">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" required class="form-input" placeholder="e.g. Rajesh Kumar">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" required class="form-input" placeholder="e.g. rajesh@sfb.in">
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password" required minlength="6" class="form-input" placeholder="Min 6 characters">
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" required class="form-input">
                        <option value="agent">Agent (Field Officer)</option>
                        <option value="manager">Manager (Branch Supervisor)</option>
                        <option value="admin">Admin (Head Office)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Branch (Optional)</label>
                    <input type="text" name="branch_name" class="form-input" placeholder="e.g. Guwahati">
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-secondary modal-close-btn">Cancel</button>
                <button type="submit" class="btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit User Modal ── --}}
<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px;">✏️ Edit Staff Member</h3>

        <form method="POST" action="" id="editUserForm">
            @csrf
            @method('PUT')
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="edit_name" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="edit_email" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Password <span style="font-size: 12px; color: var(--text-tertiary); font-weight: normal;">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" minlength="6" class="form-input" placeholder="Min 6 characters">
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" id="edit_role" required class="form-input">
                        <option value="agent">Agent (Field Officer)</option>
                        <option value="manager">Manager (Branch Supervisor)</option>
                        <option value="admin">Admin (Head Office)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Branch (Optional)</label>
                    <input type="text" name="branch_name" id="edit_branch" class="form-input">
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-secondary modal-close-btn">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</div>

@push('scripts')
<style>
@media (max-width: 1024px) {
    div[style*="grid-template-columns: repeat(4, 1fr)"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 640px) {
    div[style*="grid-template-columns: repeat(4, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.modal-overlay.show {
    opacity: 1;
}
.modal-content {
    background: var(--surface-light);
    border-radius: var(--border-radius-xl);
    padding: 24px;
    width: 100%;
    max-width: 450px;
    box-shadow: var(--shadow-glass);
    transform: scale(0.95);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-overlay.show .modal-content {
    transform: scale(1);
}

/* ── DataTable Polish ── */
.dataTables_wrapper { margin-top: 12px; }
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter { margin-bottom: 16px; font-size: 13px; color: var(--text-secondary); padding: 0 24px; }
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border-light); border-radius: 12px; padding: 8px 16px;
    font-family: inherit; font-size: 13px; background: var(--surface-light);
    outline: none; margin-left: 8px; transition: var(--transition-smooth);
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--brand-500); box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
}
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border-light); border-radius: 8px; padding: 6px 12px;
    font-family: inherit; font-size: 13px; margin: 0 4px; background: var(--surface-light);
}
.dataTables_wrapper .dataTables_info { font-size: 13px; color: var(--text-tertiary); padding: 16px 24px; }
.dataTables_wrapper .dataTables_paginate { padding: 16px 24px; }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important; border: 1px solid transparent !important;
    font-size: 13px !important; font-weight: 500 !important; padding: 6px 12px !important;
    color: var(--text-secondary) !important; transition: var(--transition-smooth);
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--brand-50) !important; color: var(--brand-600) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--brand-600) !important; color: white !important;
    box-shadow: 0 2px 6px rgba(29, 78, 216, 0.3) !important;
}
</style>
<script>
$(document).ready(function() {
    $('#staffTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "{{ route('admin.users.data') }}",
            "type": "GET"
        },
        "columns": [
            {
                "data": "name",
                "render": function(data, type, row) {
                    let initials = data.substring(0, 2).toUpperCase();
                    let bg, textCol;
                    if(row.role === 'admin') { bg = '#f3e8ff'; textCol = '#7e22ce'; }
                    else if(row.role === 'manager') { bg = '#dbeafe'; textCol = '#1d4ed8'; }
                    else { bg = '#d1fae5'; textCol = '#047857'; }
                    
                    return `<div style="display: flex; align-items: center; gap: 12px; padding-left: 24px;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: ${bg};">
                                <span style="font-weight: 700; font-size: 14px; color: ${textCol};">${initials}</span>
                            </div>
                            <span style="font-size: 14px; font-weight: 500; color: var(--text-primary);">${data}</span>
                        </div>`;
                }
            },
            {
                "data": "email",
                "className": "font-mono text-muted",
                "render": function(data) { return `<span style="font-size: 12px;">${data}</span>`; }
            },
            {
                "data": "role",
                "className": "text-center",
                "render": function(data) {
                    let bg, textCol;
                    if(data === 'admin') { bg = '#f3e8ff'; textCol = '#7e22ce'; }
                    else if(data === 'manager') { bg = '#dbeafe'; textCol = '#1d4ed8'; }
                    else { bg = '#d1fae5'; textCol = '#047857'; }
                    
                    return `<span style="display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: ${bg}; color: ${textCol};">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            {
                "data": "branch_name",
                "className": "text-muted",
                "render": function(data) { return `<span style="font-size: 14px;">${data || '—'}</span>`; }
            },
            {
                "data": "created_at",
                "className": "text-muted",
                "render": function(data) {
                    if(!data) return '';
                    let d = new Date(data);
                    return `<span style="font-size: 14px;">${d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</span>`;
                }
            },
            {
                "data": "id",
                "className": "text-center",
                "render": function(data, type, row) {
                    let nameEscaped = row.name.replace(/'/g, "\\'");
                    let emailEscaped = row.email.replace(/'/g, "\\'");
                    let branchEscaped = (row.branch_name || '').replace(/'/g, "\\'");
                    
                    let deleteUrl = "{{ route('admin.users.destroy', ':id') }}".replace(':id', data);
                    
                    return `<div style="padding-right: 24px; white-space: nowrap;">
                        <button type="button" title="Edit" style="background: none; border: none; cursor: pointer; font-size: 16px; margin-right: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" onclick="openEditModal(${data}, '${nameEscaped}', '${emailEscaped}', '${row.role}', '${branchEscaped}')">✏️</button>
                        <form action="${deleteUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="background: none; border: none; cursor: pointer; font-size: 16px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">🗑️</button>
                        </form>
                    </div>`;
                }
            }
        ],
        "pageLength": 20,
        "ordering": true,
        "language": {
            "search": "",
            "searchPlaceholder": "🔍 Search staff...",
            "emptyTable": "No staff accounts found"
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const btnCreateStaff = document.getElementById('btnCreateStaff');
    const createModal = document.getElementById('createModal');
    const closeBtns = document.querySelectorAll('.modal-close-btn');

    function openModal(modal) {
        modal.style.display = 'flex';
        void modal.offsetWidth;
        modal.classList.add('show');
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    if(btnCreateStaff) btnCreateStaff.addEventListener('click', () => openModal(createModal));

    closeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    const editModal = document.getElementById('editModal');
    
    window.openEditModal = function(id, name, email, role, branch) {
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_branch').value = branch;
        
        // Update form action dynamically
        const form = document.getElementById('editUserForm');
        form.action = `/admin/users/${id}`;
        
        openModal(editModal);
    };

    if(createModal) {
        createModal.addEventListener('click', (e) => {
            if (e.target === createModal) {
                closeModal(createModal);
            }
        });
    }
    
    if(editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) {
                closeModal(editModal);
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (createModal && createModal.classList.contains('show')) closeModal(createModal);
            if (editModal && editModal.classList.contains('show')) closeModal(editModal);
        }
    });
});
</script>
@endpush
@endsection
