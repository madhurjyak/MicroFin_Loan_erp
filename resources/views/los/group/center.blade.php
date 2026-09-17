@extends('layouts.app')

@section('title', 'Centers')
@section('page-title', '🏛️ Kendra Centers Management')

@section('content')
{{-- ── Quick Overview Metrics (Matching apply/dashboard style) ── --}}
<div class="dashboard-kpi-grid">
    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Total Centers</p>
                <p class="metric-value">{{ $centers->count() }}</p>
                <p class="metric-subtitle">Active Kendra locations</p>
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
                <p class="metric-title">Total Groups</p>
                <p class="metric-value text-emerald">{{ $centers->sum(fn($c) => $c->groups->count()) }}</p>
                <p class="metric-subtitle">JLGs linked to centers</p>
            </div>
            <div class="metric-icon metric-icon-emerald">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Branches Covered</p>
                <p class="metric-value" style="color: #2563eb;">{{ $centers->pluck('branch_name')->unique()->count() }}</p>
                <p class="metric-subtitle">Branch network reach</p>
            </div>
            <div class="metric-icon metric-icon-brand">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Field Officers</p>
                <p class="metric-value text-saffron">{{ $centers->pluck('field_officer')->unique()->count() }}</p>
                <p class="metric-subtitle">Assigned Kendra officers</p>
            </div>
            <div class="metric-icon metric-icon-saffron">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
        </div>
    </div>
</div>

{{-- ── Main Content Panel (Design matching apply.blade.php & my_applications.blade.php) ── --}}
<div class="panel">
    <div class="panel-header-action">
        <div>
            <h2 class="panel-title mb-1">Kendra Centers Directory</h2>
            <p class="text-muted">Manage Kendra collection centers, schedules, and field officer assignments</p>
        </div>
        <button type="button" class="btn-primary" onclick="openModal(document.getElementById('newCenterModal'))">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Center
        </button>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="centersTable">
            <thead>
                <tr>
                    <th class="text-left">Center ID</th>
                    <th class="text-left">Center Name & Code</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Meeting Cadence</th>
                    <th class="text-left">Field Officer</th>
                    <th class="text-center">Groups</th>
                    <th class="text-left">Created On</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($centers as $center)
                <tr>
                    <td class="font-mono font-medium" style="color: var(--brand-600);">
                        #{{ str_pad($center->id, 3, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>
                        <div class="font-medium" style="color: var(--text-primary); font-size: 14px;">
                            {{ $center->center_name }}
                        </div>
                        <span class="font-mono text-muted" style="display: inline-block; font-size: 11px; background: var(--brand-50); color: var(--brand-600); padding: 2px 8px; border-radius: 6px; font-weight: 600; margin-top: 2px;">
                            {{ $center->center_code }}
                        </span>
                    </td>
                    <td class="font-medium" style="color: var(--text-secondary);">
                        {{ $center->branch_name }}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge-sma0" style="background: #fef3c7; color: #92400e; font-weight: 600;">
                                📅 {{ $center->meeting_day }}
                            </span>
                            <span class="text-muted" style="font-size: 12px; font-weight: 500;">
                                {{ \Carbon\Carbon::parse($center->meeting_time)->format('h:i A') }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-100), var(--brand-50)); color: var(--brand-600); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                {{ strtoupper(substr($center->field_officer, 0, 2)) }}
                            </div>
                            <span class="font-medium" style="color: var(--text-primary);">
                                {{ $center->field_officer }}
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge-std" style="font-size: 12px; padding: 4px 12px;">
                            {{ $center->groups->count() }} {{ Str::plural('Group', $center->groups->count()) }}
                        </span>
                    </td>
                    <td class="text-muted" style="font-size: 13px;">
                        {{ $center->created_at->format('d M Y') }}
                    </td>
                    <td class="text-right">
                        <div class="actions-btn-group">
                            {{-- View Button --}}
                            <button type="button" class="btn-action-view" onclick="viewCenter({{ json_encode($center) }})" title="View Details" aria-label="View Details">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>

                            {{-- Edit Button --}}
                            <button type="button" class="btn-action-edit" onclick="openEditCenterModal({{ json_encode($center) }})" title="Edit Center" aria-label="Edit Center">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            {{-- Delete Button --}}
                            <form action="{{ route('los.center.destroy', $center->id) }}" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete center \'{{ $center->center_name }}\'?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-delete" title="Delete Center" aria-label="Delete Center">
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
                    <td colspan="8" class="empty-state">
                        <p style="font-size: 20px; margin-bottom: 8px;">🏛️ No Kendra Centers Found</p>
                        <p class="text-muted mb-4">Get started by setting up your first branch collection center</p>
                        <button type="button" class="btn-primary" onclick="openModal(document.getElementById('newCenterModal'))">
                            + Create First Center
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── New Center Modal (Styled to match apply.blade.php form & design system) ── --}}
<div id="newCenterModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1">🏛️ Add New Kendra Center</h2>
                <p class="text-muted" style="margin: 0;">Configure center credentials, meeting schedule, and field assignment</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <form action="{{ route('los.center.store') }}" method="POST" id="newCenterForm">
            @csrf
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="center_name">Center Name <span class="text-rose">*</span></label>
                    <input type="text" id="center_name" name="center_name" class="form-input" placeholder="e.g. Rampur Kendra 01" value="{{ old('center_name') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="center_code">Center Code <span class="text-rose">*</span></label>
                    <input type="text" id="center_code" name="center_code" class="form-input font-mono" placeholder="e.g. CEN-RAM-001" value="{{ old('center_code') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="branch_name">Branch Name <span class="text-rose">*</span></label>
                    <input type="text" id="branch_name" name="branch_name" class="form-input" placeholder="e.g. Bareilly Main Branch" value="{{ old('branch_name') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="field_officer">Field Officer (FO) <span class="text-rose">*</span></label>
                    <input type="text" id="field_officer" name="field_officer" class="form-input" placeholder="e.g. Rajesh Kumar" value="{{ old('field_officer') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="meeting_day">Weekly Meeting Day <span class="text-rose">*</span></label>
                    <select id="meeting_day" name="meeting_day" class="form-input" required>
                        <option value="">-- Select Meeting Day --</option>
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            <option value="{{ $day }}" {{ old('meeting_day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="meeting_time">Meeting Time <span class="text-rose">*</span></label>
                    <input type="time" id="meeting_time" name="meeting_time" class="form-input" value="{{ old('meeting_time', '10:00') }}" required>
                </div>
            </div>

            {{-- Informational Policy Panel (similar to customer-preview in apply.blade.php) --}}
            <div class="customer-preview" style="margin-top: 16px; margin-bottom: 24px;">
                <p class="preview-title">Center Operations & Governance</p>
                <div class="preview-grid">
                    <div>
                        <span class="preview-label">Cadence Type:</span>
                        <span class="preview-value">Weekly Kendra Meeting</span>
                    </div>
                    <div>
                        <span class="preview-label">JLG Capacity:</span>
                        <span class="preview-value">Up to 8 Groups (40 Members)</span>
                    </div>
                    <div>
                        <span class="preview-label">Collection Method:</span>
                        <span class="preview-value">Kendra Collection Sheet (CDS)</span>
                    </div>
                    <div>
                        <span class="preview-label">Compliance:</span>
                        <span class="preview-value text-emerald">RBI NBFC-MFI Compliant</span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="form-actions" style="justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                <button type="button" class="btn-secondary modal-close-btn">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    🚀 Create Center
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Center Modal ── --}}
<div id="editCenterModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1">✏️ Edit Kendra Center</h2>
                <p class="text-muted" style="margin: 0;">Update center details, schedule or assigned officer</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <form method="POST" id="editCenterForm">
            @csrf
            @method('PUT')
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="edit_center_name">Center Name <span class="text-rose">*</span></label>
                    <input type="text" id="edit_center_name" name="center_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_center_code">Center Code <span class="text-rose">*</span></label>
                    <input type="text" id="edit_center_code" name="center_code" class="form-input font-mono" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_branch_name">Branch Name <span class="text-rose">*</span></label>
                    <input type="text" id="edit_branch_name" name="branch_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_field_officer">Field Officer (FO) <span class="text-rose">*</span></label>
                    <input type="text" id="edit_field_officer" name="field_officer" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_meeting_day">Weekly Meeting Day <span class="text-rose">*</span></label>
                    <select id="edit_meeting_day" name="meeting_day" class="form-input" required>
                        <option value="">-- Select Meeting Day --</option>
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_meeting_time">Meeting Time <span class="text-rose">*</span></label>
                    <input type="time" id="edit_meeting_time" name="meeting_time" class="form-input" required>
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

{{-- ── View Center Modal ── --}}
<div id="viewCenterModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box" style="max-width: 600px;">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1" id="viewCenterTitle">🏛️ Center Details</h2>
                <p class="text-muted" style="margin: 0;" id="viewCenterSubtitle">Detailed profile and operations cadence</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <div class="customer-preview" style="margin-top: 0; margin-bottom: 20px;">
            <p class="preview-title">General Information</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">Center ID:</span>
                    <span class="preview-value font-mono" id="viewCenterId">—</span>
                </div>
                <div>
                    <span class="preview-label">Center Code:</span>
                    <span class="preview-value font-mono text-emerald" id="viewCenterCode">—</span>
                </div>
                <div>
                    <span class="preview-label">Center Name:</span>
                    <span class="preview-value" id="viewCenterName">—</span>
                </div>
                <div>
                    <span class="preview-label">Branch:</span>
                    <span class="preview-value" id="viewBranchName">—</span>
                </div>
            </div>
        </div>

        <div class="customer-preview" style="margin-bottom: 20px;">
            <p class="preview-title">Cadence & Assignment</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">Field Officer:</span>
                    <span class="preview-value" id="viewFieldOfficer">—</span>
                </div>
                <div>
                    <span class="preview-label">Meeting Day:</span>
                    <span class="preview-value" id="viewMeetingDay">—</span>
                </div>
                <div>
                    <span class="preview-label">Meeting Time:</span>
                    <span class="preview-value" id="viewMeetingTime">—</span>
                </div>
                <div>
                    <span class="preview-label">Total Groups:</span>
                    <span class="preview-value" id="viewGroupsCount">—</span>
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
    max-width: 720px;
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
        $('#centersTable').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength": 10,
            "language": {
                "search": "",
                "searchPlaceholder": "🔍 Search centers, codes, officers..."
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

    ['newCenterModal', 'editCenterModal', 'viewCenterModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal);
                }
            });
        }
    });

    // ── Edit Center Handler ──
    function openEditCenterModal(center) {
        const form = document.getElementById('editCenterForm');
        form.action = `/los/center/${center.id}`;

        document.getElementById('edit_center_name').value = center.center_name || '';
        document.getElementById('edit_center_code').value = center.center_code || '';
        document.getElementById('edit_branch_name').value = center.branch_name || '';
        document.getElementById('edit_field_officer').value = center.field_officer || '';
        document.getElementById('edit_meeting_day').value = center.meeting_day || '';

        // Formats time HH:MM if with seconds
        let time = center.meeting_time || '';
        if (time.length > 5) {
            time = time.substring(0, 5);
        }
        document.getElementById('edit_meeting_time').value = time;

        openModal(document.getElementById('editCenterModal'));
    }

    // ── View Center Handler ──
    function viewCenter(center) {
        document.getElementById('viewCenterId').innerText = '#' + String(center.id).padStart(3, '0');
        document.getElementById('viewCenterCode').innerText = center.center_code || '—';
        document.getElementById('viewCenterName').innerText = center.center_name || '—';
        document.getElementById('viewBranchName').innerText = center.branch_name || '—';
        document.getElementById('viewFieldOfficer').innerText = center.field_officer || '—';
        document.getElementById('viewMeetingDay').innerText = center.meeting_day || '—';
        document.getElementById('viewMeetingTime').innerText = center.meeting_time ? center.meeting_time.substring(0, 5) : '—';
        document.getElementById('viewGroupsCount').innerText = (center.groups ? center.groups.length : 0) + ' Groups';

        openModal(document.getElementById('viewCenterModal'));
    }
</script>
@endpush
