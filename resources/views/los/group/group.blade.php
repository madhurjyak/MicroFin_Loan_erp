@extends('layouts.app')

@section('title', 'Groups')
@section('page-title', '👥 JLG Groups Management')

@section('content')
{{-- ── Quick Overview Metrics (Matching apply/center style) ── --}}
<div class="dashboard-kpi-grid">
    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Total Groups</p>
                <p class="metric-value">{{ $groups->count() }}</p>
                <p class="metric-subtitle">Active JLG units</p>
            </div>
            <div class="metric-icon metric-icon-brand">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Total Members</p>
                <p class="metric-value text-emerald">{{ $groups->sum(fn($g) => $g->customers->count()) }}</p>
                <p class="metric-subtitle">Enrolled borrowers</p>
            </div>
            <div class="metric-icon metric-icon-emerald">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Centers Covered</p>
                <p class="metric-value" style="color: #2563eb;">{{ $groups->pluck('center_id')->filter()->unique()->count() }}</p>
                <p class="metric-subtitle">Linked Kendra centers</p>
            </div>
            <div class="metric-icon metric-icon-brand">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Avg Group Size</p>
                <p class="metric-value text-saffron">{{ $groups->count() > 0 ? round($groups->sum(fn($g) => $g->customers->count()) / $groups->count(), 1) : 0 }}</p>
                <p class="metric-subtitle">Members per group</p>
            </div>
            <div class="metric-icon metric-icon-saffron">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
        </div>
    </div>
</div>

{{-- ── Main Content Panel ── --}}
<div class="panel">
    <div class="panel-header-action">
        <div>
            <h2 class="panel-title mb-1">Joint Liability Groups Directory</h2>
            <p class="text-muted">Manage JLG units, group leaders, and Kendra center associations</p>
        </div>
        <button type="button" class="btn-primary" onclick="openModal(document.getElementById('newGroupModal'))">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Group
        </button>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="groupsTable">
            <thead>
                <tr>
                    <th class="text-left">Group ID</th>
                    <th class="text-left">Group Name</th>
                    <th class="text-left">Center</th>
                    <th class="text-left">Group Leader</th>
                    <th class="text-center">Members Enrolled</th>
                    <th class="text-left">Created On</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                <tr>
                    <td class="font-mono font-medium" style="color: var(--brand-600);">
                        #GRP-{{ str_pad($group->id, 3, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>
                        <div class="font-medium" style="color: var(--text-primary); font-size: 14px;">
                            {{ $group->group_name }}
                        </div>
                    </td>
                    <td>
                        @if($group->center)
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="badge-sma0" style="background: #e0f2fe; color: #0369a1; font-weight: 600;">
                                    🏛️ {{ $group->center->center_name }}
                                </span>
                                <span class="font-mono text-muted" style="font-size: 11px;">
                                    {{ $group->center->center_code }}
                                </span>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-100), var(--brand-50)); color: var(--brand-600); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                {{ strtoupper(substr($group->group_leader_name, 0, 2)) }}
                            </div>
                            <span class="font-medium" style="color: var(--text-primary);">
                                {{ $group->group_leader_name }}
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge-std" style="font-size: 12px; padding: 4px 12px;">
                            {{ $group->customers->count() }} {{ Str::plural('Member', $group->customers->count()) }}
                        </span>
                    </td>
                    <td class="text-muted" style="font-size: 13px;">
                        {{ $group->created_at->format('d M Y') }}
                    </td>
                    <td class="text-right">
                        <div class="actions-btn-group">
                            {{-- View Button --}}
                            <button type="button" class="btn-action-view" onclick="viewGroup({{ json_encode($group) }})" title="View Details" aria-label="View Details">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>

                            {{-- Edit Button --}}
                            <button type="button" class="btn-action-edit" onclick="openEditGroupModal({{ json_encode($group) }})" title="Edit Group" aria-label="Edit Group">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            {{-- Delete Button --}}
                            <form action="{{ route('los.group.destroy', $group->id) }}" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete group \'{{ $group->group_name }}\'?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-delete" title="Delete Group" aria-label="Delete Group">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <p style="font-size: 20px; margin-bottom: 8px;">👥 No Groups Found</p>
                        <p class="text-muted mb-4">Create your first Joint Liability Group linked to a Kendra center</p>
                        <button type="button" class="btn-primary" onclick="openModal(document.getElementById('newGroupModal'))">
                            + Create First Group
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── New Group Modal ── --}}
<div id="newGroupModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1">👥 Add New Joint Liability Group</h2>
                <p class="text-muted" style="margin: 0;">Register a new JLG unit under a Kendra collection center</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <form action="{{ route('los.group.store') }}" method="POST" id="newGroupForm">
            @csrf
            
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label" for="center_id">Associated Kendra Center <span class="text-rose">*</span></label>
                    <select id="center_id" name="center_id" class="form-input" required>
                        <option value="">-- Select Kendra Center --</option>
                        @foreach($centers as $c)
                            <option value="{{ $c->id }}" {{ old('center_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->center_name }} ({{ $c->center_code }}) — {{ $c->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="group_name">Group Name <span class="text-rose">*</span></label>
                    <input type="text" id="group_name" name="group_name" class="form-input" placeholder="e.g. Saraswati SHG" value="{{ old('group_name') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="group_leader_name">Group Leader Name <span class="text-rose">*</span></label>
                    <input type="text" id="group_leader_name" name="group_leader_name" class="form-input" placeholder="e.g. Sunita Devi" value="{{ old('group_leader_name') }}" required>
                </div>
            </div>

            {{-- Policy Info Panel --}}
            <div class="customer-preview" style="margin-top: 16px; margin-bottom: 24px;">
                <p class="preview-title">JLG Mutual Guarantee & Peer Framework</p>
                <div class="preview-grid">
                    <div>
                        <span class="preview-label">Liability:</span>
                        <span class="preview-value">Joint & Several Guarantee</span>
                    </div>
                    <div>
                        <span class="preview-label">Target Size:</span>
                        <span class="preview-value">5 Members per JLG</span>
                    </div>
                    <div>
                        <span class="preview-label">Governance:</span>
                        <span class="preview-value">Kendra Day Peer Review</span>
                    </div>
                    <div>
                        <span class="preview-label">Compliance:</span>
                        <span class="preview-value text-emerald">RBI MFI Directives</span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="form-actions" style="justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                <button type="button" class="btn-secondary modal-close-btn">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    🚀 Create Group
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Group Modal ── --}}
<div id="editGroupModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1">✏️ Edit Joint Liability Group</h2>
                <p class="text-muted" style="margin: 0;">Update group information and center affiliation</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <form method="POST" id="editGroupForm">
            @csrf
            @method('PUT')
            
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label" for="edit_center_id">Associated Kendra Center <span class="text-rose">*</span></label>
                    <select id="edit_center_id" name="center_id" class="form-input" required>
                        <option value="">-- Select Kendra Center --</option>
                        @foreach($centers as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->center_name }} ({{ $c->center_code }}) — {{ $c->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_group_name">Group Name <span class="text-rose">*</span></label>
                    <input type="text" id="edit_group_name" name="group_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_group_leader_name">Group Leader Name <span class="text-rose">*</span></label>
                    <input type="text" id="edit_group_leader_name" name="group_leader_name" class="form-input" required>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="form-actions" style="justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                <button type="button" class="btn-secondary modal-close-btn">
                    Cancel
                </button>
                <button type="submit" class="btn-primary" style="background-color: #d97706;">
                    💾 Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── View Group Modal ── --}}
<div id="viewGroupModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box" style="max-width: 600px;">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1" id="viewGroupTitle">👥 Group Details</h2>
                <p class="text-muted" style="margin: 0;">Comprehensive JLG profile and enrollment information</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <div class="customer-preview" style="margin-top: 0; margin-bottom: 20px;">
            <p class="preview-title">Group Profile</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">Group ID:</span>
                    <span class="preview-value font-mono" id="viewGroupId">—</span>
                </div>
                <div>
                    <span class="preview-label">Group Name:</span>
                    <span class="preview-value" id="viewGroupName">—</span>
                </div>
                <div>
                    <span class="preview-label">Group Leader:</span>
                    <span class="preview-value" id="viewGroupLeader">—</span>
                </div>
                <div>
                    <span class="preview-label">Enrolled Members:</span>
                    <span class="preview-value text-emerald" id="viewGroupMembers">—</span>
                </div>
            </div>
        </div>

        <div class="customer-preview" style="margin-bottom: 20px;">
            <p class="preview-title">Kendra Center Affiliation</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">Center Name:</span>
                    <span class="preview-value" id="viewGroupCenterName">—</span>
                </div>
                <div>
                    <span class="preview-label">Center Code:</span>
                    <span class="preview-value font-mono" id="viewGroupCenterCode">—</span>
                </div>
                <div>
                    <span class="preview-label">Branch:</span>
                    <span class="preview-value" id="viewGroupBranch">—</span>
                </div>
                <div>
                    <span class="preview-label">Meeting Cadence:</span>
                    <span class="preview-value" id="viewGroupMeeting">—</span>
                </div>
            </div>
        </div>

        <div class="form-actions" style="justify-content: flex-end; border-top: 1px solid var(--border-light); padding-top: 16px;">
            <button type="button" class="btn-secondary modal-close-btn">
                Close
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
/* ── Action Buttons Group in Table ── */
.actions-btn-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
}

.btn-action-view,
.btn-action-edit,
.btn-action-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition-smooth);
    text-decoration: none;
    padding: 0;
}

/* View Button */
.btn-action-view {
    background: var(--brand-50);
    color: var(--brand-600);
    border: 1px solid var(--brand-100);
}
.btn-action-view:hover {
    background: var(--brand-600);
    color: white;
    border-color: var(--brand-600);
    box-shadow: 0 2px 6px rgba(29, 78, 216, 0.2);
}

/* Edit Button */
.btn-action-edit {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.btn-action-edit:hover {
    background: #d97706;
    color: white;
    border-color: #d97706;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.2);
}

/* Delete Button */
.btn-action-delete {
    background: #ffe4e6;
    color: #e11d48;
    border: 1px solid #fecdd3;
}
.btn-action-delete:hover {
    background: #e11d48;
    color: white;
    border-color: #e11d48;
    box-shadow: 0 2px 6px rgba(225, 29, 72, 0.2);
}

/* ── Modal Overlay & Card Styling matching Design System ── */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999;
    opacity: 0;
    transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 20px;
}

.modal-overlay.show {
    opacity: 1;
}

.modal-content-box {
    width: 100%;
    max-width: 680px;
    margin-bottom: 0;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px) scale(0.98);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
}

.modal-overlay.show .modal-content-box {
    transform: translateY(0) scale(1);
}

.modal-close-btn:hover {
    background: var(--brand-100) !important;
    color: var(--brand-600) !important;
}

/* ── DataTable Polish (Outfit font & rounded aesthetics) ── */
.dataTables_wrapper {
    margin-top: 12px;
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--text-secondary);
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 8px 16px;
    font-family: inherit;
    font-size: 13px;
    background: var(--surface-light);
    outline: none;
    margin-left: 8px;
    transition: var(--transition-smooth);
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--brand-500);
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 6px 12px;
    font-family: inherit;
    font-size: 13px;
    margin: 0 4px;
    background: var(--surface-light);
}

.dataTables_wrapper .dataTables_info {
    font-size: 13px;
    color: var(--text-tertiary);
    padding-top: 16px;
}

.dataTables_wrapper .dataTables_paginate {
    padding-top: 16px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    border: 1px solid transparent !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    padding: 6px 12px !important;
    color: var(--text-secondary) !important;
    transition: var(--transition-smooth);
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--brand-50) !important;
    color: var(--brand-600) !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--brand-600) !important;
    color: white !important;
    box-shadow: 0 2px 6px rgba(29, 78, 216, 0.3) !important;
}
</style>

<script>
    $(document).ready(function() {
        $('#groupsTable').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength": 10,
            "language": {
                "search": "",
                "searchPlaceholder": "🔍 Search groups, leaders, centers..."
            }
        });
    });

    // ── Modal Interaction Logic ──
    const closeBtns = document.querySelectorAll('.modal-close-btn');

    function openModal(modal) {
        if (!modal) return;
        modal.style.display = 'flex';
        void modal.offsetWidth;
        modal.classList.add('show');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 250);
    }

    closeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    ['newGroupModal', 'editGroupModal', 'viewGroupModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal);
                }
            });
        }
    });

    // ── Edit Group Handler ──
    function openEditGroupModal(group) {
        const form = document.getElementById('editGroupForm');
        form.action = `/los/group/${group.id}`;

        document.getElementById('edit_center_id').value = group.center_id || '';
        document.getElementById('edit_group_name').value = group.group_name || '';
        document.getElementById('edit_group_leader_name').value = group.group_leader_name || '';

        openModal(document.getElementById('editGroupModal'));
    }

    // ── View Group Handler ──
    function viewGroup(group) {
        document.getElementById('viewGroupId').innerText = '#GRP-' + String(group.id).padStart(3, '0');
        document.getElementById('viewGroupName').innerText = group.group_name || '—';
        document.getElementById('viewGroupLeader').innerText = group.group_leader_name || '—';
        document.getElementById('viewGroupMembers').innerText = (group.customers ? group.customers.length : 0) + ' Members';

        if (group.center) {
            document.getElementById('viewGroupCenterName').innerText = group.center.center_name || '—';
            document.getElementById('viewGroupCenterCode').innerText = group.center.center_code || '—';
            document.getElementById('viewGroupBranch').innerText = group.center.branch_name || '—';
            document.getElementById('viewGroupMeeting').innerText = (group.center.meeting_day || '—') + ' @ ' + (group.center.meeting_time ? group.center.meeting_time.substring(0, 5) : '');
        } else {
            document.getElementById('viewGroupCenterName').innerText = '—';
            document.getElementById('viewGroupCenterCode').innerText = '—';
            document.getElementById('viewGroupBranch').innerText = '—';
            document.getElementById('viewGroupMeeting').innerText = '—';
        }

        openModal(document.getElementById('viewGroupModal'));
    }
</script>
@endpush
