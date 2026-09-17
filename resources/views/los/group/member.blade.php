@extends('layouts.app')

@section('title', 'Members')
@section('page-title', '🧑‍🤝‍🧑 Kendra Members Management')

@section('content')
{{-- ── Quick Overview Metrics (Matching apply/center style) ── --}}
<div class="dashboard-kpi-grid">
    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Total Members</p>
                <p class="metric-value">{{ $members->count() }}</p>
                <p class="metric-subtitle">Enrolled Kendra borrowers</p>
            </div>
            <div class="metric-icon metric-icon-brand">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Women Borrowers</p>
                <p class="metric-value text-emerald">
                    {{ $members->where('gender', 'female')->count() }}
                </p>
                <p class="metric-subtitle">
                    {{ $members->count() > 0 ? round(($members->where('gender', 'female')->count() / $members->count()) * 100) : 0 }}% Women JLG Focus
                </p>
            </div>
            <div class="metric-icon metric-icon-emerald">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">RBI Income Compliant</p>
                <p class="metric-value" style="color: #2563eb;">
                    {{ $members->where('annual_household_income', '<=', 300000)->count() }}
                </p>
                <p class="metric-subtitle">≤ ₹3,00,000 RBI cap</p>
            </div>
            <div class="metric-icon metric-icon-brand">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-card-content">
            <div>
                <p class="metric-title">Active Borrowers</p>
                <p class="metric-value text-saffron">
                    {{ $members->filter(fn($m) => $m->loans->where('status', 'active')->count() > 0)->count() }}
                </p>
                <p class="metric-subtitle">With active loan ledger</p>
            </div>
            <div class="metric-icon metric-icon-saffron">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
        </div>
    </div>
</div>

{{-- ── Main Content Panel ── --}}
<div class="panel">
    <div class="panel-header-action">
        <div>
            <h2 class="panel-title mb-1">Kendra Member Directory</h2>
            <p class="text-muted">Manage borrower KYC, financial assessments, and group enrollments</p>
        </div>
        <button type="button" class="btn-primary" onclick="openModal(document.getElementById('newMemberModal'))">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Member
        </button>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="membersTable">
            <thead>
                <tr>
                    <th class="text-left">Member ID</th>
                    <th class="text-left">Member Name & Contact</th>
                    <th class="text-left">Center & Group</th>
                    <th class="text-left">KYC (Aadhaar / PAN)</th>
                    <th class="text-right">Annual Income</th>
                    <th class="text-center">FOIR Status</th>
                    <th class="text-center">Active Loans</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $m)
                <tr>
                    <td class="font-mono font-medium" style="color: var(--brand-600);">
                        #CUS-{{ str_pad($m->id, 3, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>
                        <div class="font-medium" style="color: var(--text-primary); font-size: 14px; display: flex; align-items: center; gap: 6px;">
                            <span>{{ $m->full_name }}</span>
                        </div>
                        <div class="text-muted" style="font-size: 12px; margin-top: 2px;">
                            📞 {{ $m->phone }}
                        </div>
                    </td>
                    <td>
                        @if($m->group)
                        <div class="font-medium" style="color: var(--text-primary);">
                            {{ $m->group->group_name }}
                        </div>
                        @if($m->group->center)
                        <div class="text-muted" style="font-size: 11px;">
                            🏛️ {{ $m->group->center->center_name }}
                        </div>
                        @endif
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="font-mono" style="font-size: 12px; font-weight: 500; color: var(--text-secondary);">
                            {{ $m->masked_aadhaar }}
                        </div>
                        @if($m->pan_number)
                        <span class="font-mono text-muted" style="font-size: 11px; background: var(--brand-50); color: var(--brand-600); padding: 1px 6px; border-radius: 4px;">
                            PAN: {{ $m->pan_number }}
                        </span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="font-medium" style="color: var(--text-primary);">
                            ₹{{ number_format($m->annual_household_income, 0, '.', ',') }}
                        </div>
                        <div class="text-muted" style="font-size: 11px;">
                            Debt: ₹{{ number_format($m->monthly_debt_obligations, 0, '.', ',') }}/mo
                        </div>
                    </td>
                    <td class="text-center">
                        @php
                        $foir = $m->foir_percent;
                        @endphp
                        @if($foir <= 40)
                            <span class="badge-std" style="font-size: 11px;">
                            {{ $foir }}% Safe
                            </span>
                            @elseif($foir <= 50)
                                <span class="badge-sma0" style="font-size: 11px; background: #fef3c7; color: #92400e;">
                                {{ $foir }}% Caution
                                </span>
                                @else
                                <span class="badge-npa" style="font-size: 11px;">
                                    {{ $foir }}% High
                                </span>
                                @endif
                    </td>
                    <td class="text-center">
                        @php $activeCount = $m->loans->where('status', 'active')->count(); @endphp
                        @if($activeCount > 0)
                        <span class="badge-std" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                            {{ $activeCount }} Active
                        </span>
                        @else
                        <span class="text-muted" style="font-size: 12px;">None</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="actions-btn-group">
                            {{-- View Button --}}
                            <button type="button" class="btn-action-view" onclick="viewMember({{ json_encode($m) }})" title="View Details" aria-label="View Details">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>

                            {{-- Edit Button --}}
                            <button type="button" class="btn-action-edit" onclick="openEditMemberModal({{ json_encode($m) }})" title="Edit Member" aria-label="Edit Member">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            {{-- Delete Button --}}
                            <form action="{{ route('los.member.destroy', $m->id) }}" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete member \'{{ $m->full_name }}\'?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-delete" title="Delete Member" aria-label="Delete Member">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <p style="font-size: 20px; margin-bottom: 8px;">🧑‍🤝‍🧑 No Members Registered</p>
                        <p class="text-muted mb-4">Enroll your first Kendra borrower and associate them with a JLG group</p>
                        <button type="button" class="btn-primary" onclick="openModal(document.getElementById('newMemberModal'))">
                            + Enroll First Member
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── New Member Modal ── --}}
<div id="newMemberModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box" style="max-width: 800px;">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1">🧑‍🤝‍🧑 Enroll New Kendra Member</h2>
                <p class="text-muted" style="margin: 0;">Register borrower KYC, financial profile, and group membership</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <form action="{{ route('los.member.store') }}" method="POST" id="newMemberForm">
            @csrf

            <div class="form-grid">
                {{-- Group Selection --}}
                <div class="form-group col-span-2">
                    <label class="form-label" for="group_id">Associated JLG Group & Center <span class="text-rose">*</span></label>
                    <select id="group_id" name="group_id" class="form-input" required>
                        <option value="">-- Select Group --</option>
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                            {{ $g->group_name }} — {{ $g->center->center_name ?? 'No Center' }} ({{ $g->center->branch_name ?? '' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Personal Details --}}
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name <span class="text-rose">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-input" placeholder="e.g. Radhika Devi" value="{{ old('full_name') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="customer_code">Customer Code <span class="text-rose">*</span></label>
                    <input type="text" id="customer_code" name="customer_code" class="form-input font-mono" placeholder="e.g. CUS-2026-001" value="{{ old('customer_code', 'CUS-' . rand(1000, 9999)) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gender">Gender <span class="text-rose">*</span></label>
                    <select id="gender" name="gender" class="form-input" required>
                        <option value="female" {{ old('gender', 'female') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Mobile Phone <span class="text-rose">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="e.g. 9876543210" value="{{ old('phone') }}" required>
                </div>

                {{-- KYC Details --}}
                <div class="form-group">
                    <label class="form-label" for="aadhaar_last4">Aadhaar (Last 4 Digits) <span class="text-rose">*</span></label>
                    <input type="text" id="aadhaar_last4" name="aadhaar_last4" class="form-input font-mono" placeholder="e.g. 8492" maxlength="4" pattern="\d{4}" value="{{ old('aadhaar_last4') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pan_number">PAN Number</label>
                    <input type="text" id="pan_number" name="pan_number" class="form-input font-mono" placeholder="e.g. ABCDE1234F" maxlength="10" value="{{ old('pan_number') }}" style="text-transform: uppercase;">
                </div>

                {{-- Financial Details --}}
                <div class="form-group">
                    <label class="form-label" for="annual_household_income">Annual Household Income (₹) <span class="text-rose">*</span></label>
                    <input type="number" id="annual_household_income" name="annual_household_income" class="form-input" placeholder="e.g. 180000" min="0" max="300000" step="1000" value="{{ old('annual_household_income', 150000) }}" required>
                    <small class="text-muted" style="display: block; margin-top: 4px;">RBI Limit: ₹3,00,000 max per household</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="monthly_debt_obligations">Monthly Debt Obligations (₹) <span class="text-rose">*</span></label>
                    <input type="number" id="monthly_debt_obligations" name="monthly_debt_obligations" class="form-input" placeholder="e.g. 2000" min="0" step="100" value="{{ old('monthly_debt_obligations', 0) }}" required>
                </div>

                {{-- Address --}}
                <div class="form-group col-span-2">
                    <label class="form-label" for="address">Residential Address <span class="text-rose">*</span></label>
                    <input type="text" id="address" name="address" class="form-input" placeholder="e.g. Village Rampur, Near Primary School" value="{{ old('address') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="district">District <span class="text-rose">*</span></label>
                    <input type="text" id="district" name="district" class="form-input" placeholder="e.g. Bareilly" value="{{ old('district') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="state">State <span class="text-rose">*</span></label>
                    <input type="text" id="state" name="state" class="form-input" placeholder="e.g. Uttar Pradesh" value="{{ old('state', 'Uttar Pradesh') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pincode">Pincode <span class="text-rose">*</span></label>
                    <input type="text" id="pincode" name="pincode" class="form-input" placeholder="e.g. 243001" maxlength="6" value="{{ old('pincode') }}" required>
                </div>

                {{-- Banking --}}
                <div class="form-group">
                    <label class="form-label" for="bank_account_no">Bank Account Number</label>
                    <input type="text" id="bank_account_no" name="bank_account_no" class="form-input font-mono" placeholder="e.g. 918237192837" value="{{ old('bank_account_no') }}">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="ifsc_code">Bank IFSC Code</label>
                    <input type="text" id="ifsc_code" name="ifsc_code" class="form-input font-mono" placeholder="e.g. SBIN0001234" maxlength="11" value="{{ old('ifsc_code') }}" style="text-transform: uppercase;">
                </div>
            </div>

            {{-- Policy Info Panel --}}
            <div class="customer-preview" style="margin-top: 16px; margin-bottom: 24px;">
                <p class="preview-title">RBI Microfinance Underwriting Guidelines</p>
                <div class="preview-grid">
                    <div>
                        <span class="preview-label">Household Income Cap:</span>
                        <span class="preview-value">₹3,00,000 / annum</span>
                    </div>
                    <div>
                        <span class="preview-label">FOIR Max Limit:</span>
                        <span class="preview-value">50% of Monthly Income</span>
                    </div>
                    <div>
                        <span class="preview-label">Collateral Requirement:</span>
                        <span class="preview-value">Zero Collateral (JLG Pure MFI)</span>
                    </div>
                    <div>
                        <span class="preview-label">Disbursement:</span>
                        <span class="preview-value text-emerald">Direct Bank Account Transfer</span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="form-actions" style="justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                <button type="button" class="btn-secondary modal-close-btn">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    🚀 Register Member
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Member Modal ── --}}
<div id="editMemberModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box" style="max-width: 800px;">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1">✏️ Edit Kendra Member</h2>
                <p class="text-muted" style="margin: 0;">Update member particulars, KYC or financial data</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        <form method="POST" id="editMemberForm">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label" for="edit_group_id">Associated JLG Group & Center <span class="text-rose">*</span></label>
                    <select id="edit_group_id" name="group_id" class="form-input" required>
                        <option value="">-- Select Group --</option>
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}">
                            {{ $g->group_name }} — {{ $g->center->center_name ?? 'No Center' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_full_name">Full Name <span class="text-rose">*</span></label>
                    <input type="text" id="edit_full_name" name="full_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_customer_code">Customer Code <span class="text-rose">*</span></label>
                    <input type="text" id="edit_customer_code" name="customer_code" class="form-input font-mono" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_gender">Gender <span class="text-rose">*</span></label>
                    <select id="edit_gender" name="gender" class="form-input" required>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_phone">Mobile Phone <span class="text-rose">*</span></label>
                    <input type="tel" id="edit_phone" name="phone" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_aadhaar_last4">Aadhaar (Last 4 Digits) <span class="text-rose">*</span></label>
                    <input type="text" id="edit_aadhaar_last4" name="aadhaar_last4" class="form-input font-mono" maxlength="4" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_pan_number">PAN Number</label>
                    <input type="text" id="edit_pan_number" name="pan_number" class="form-input font-mono" maxlength="10" style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_annual_household_income">Annual Household Income (₹) <span class="text-rose">*</span></label>
                    <input type="number" id="edit_annual_household_income" name="annual_household_income" class="form-input" min="0" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_monthly_debt_obligations">Monthly Debt Obligations (₹) <span class="text-rose">*</span></label>
                    <input type="number" id="edit_monthly_debt_obligations" name="monthly_debt_obligations" class="form-input" min="0" required>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="edit_address">Residential Address <span class="text-rose">*</span></label>
                    <input type="text" id="edit_address" name="address" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_district">District <span class="text-rose">*</span></label>
                    <input type="text" id="edit_district" name="district" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_state">State <span class="text-rose">*</span></label>
                    <input type="text" id="edit_state" name="state" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_pincode">Pincode <span class="text-rose">*</span></label>
                    <input type="text" id="edit_pincode" name="pincode" class="form-input" maxlength="6" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_bank_account_no">Bank Account Number</label>
                    <input type="text" id="edit_bank_account_no" name="bank_account_no" class="form-input font-mono">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="edit_ifsc_code">Bank IFSC Code</label>
                    <input type="text" id="edit_ifsc_code" name="ifsc_code" class="form-input font-mono" maxlength="11" style="text-transform: uppercase;">
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

{{-- ── View Member Modal ── --}}
<div id="viewMemberModal" class="modal-overlay" style="display: none;">
    <div class="panel modal-content-box" style="max-width: 700px;">
        <div class="panel-header-action" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
            <div>
                <h2 class="panel-title mb-1" id="viewMemberTitle">🧑‍🤝‍🧑 Member Profile</h2>
                <p class="text-muted" style="margin: 0;" id="viewMemberSubtitle">Full KYC, Kendra alignment, and financial health</p>
            </div>
            <button type="button" class="btn-icon modal-close-btn" style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-secondary); transition: var(--transition-smooth);">
                ✕
            </button>
        </div>

        {{-- Member Primary & KYC --}}
        <div class="customer-preview" style="margin-top: 0; margin-bottom: 16px;">
            <p class="preview-title">KYC & Identity</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">Customer ID:</span>
                    <span class="preview-value font-mono" id="viewMemberId">—</span>
                </div>
                <div>
                    <span class="preview-label">Customer Code:</span>
                    <span class="preview-value font-mono text-emerald" id="viewMemberCode">—</span>
                </div>
                <div>
                    <span class="preview-label">Full Name:</span>
                    <span class="preview-value" id="viewMemberName">—</span>
                </div>
                <div>
                    <span class="preview-label">Gender / Phone:</span>
                    <span class="preview-value" id="viewMemberGenderPhone">—</span>
                </div>
                <div>
                    <span class="preview-label">Aadhaar Card:</span>
                    <span class="preview-value font-mono" id="viewMemberAadhaar">—</span>
                </div>
                <div>
                    <span class="preview-label">PAN Number:</span>
                    <span class="preview-value font-mono" id="viewMemberPan">—</span>
                </div>
            </div>
        </div>

        {{-- Center & Group --}}
        <div class="customer-preview" style="margin-bottom: 16px;">
            <p class="preview-title">Kendra Center & JLG Assignment</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">JLG Group:</span>
                    <span class="preview-value" id="viewMemberGroup">—</span>
                </div>
                <div>
                    <span class="preview-label">Kendra Center:</span>
                    <span class="preview-value" id="viewMemberCenter">—</span>
                </div>
                <div>
                    <span class="preview-label">Branch:</span>
                    <span class="preview-value" id="viewMemberBranch">—</span>
                </div>
                <div>
                    <span class="preview-label">Meeting Cadence:</span>
                    <span class="preview-value" id="viewMemberMeeting">—</span>
                </div>
            </div>
        </div>

        {{-- Financial Assessment --}}
        <div class="customer-preview" style="margin-bottom: 16px;">
            <p class="preview-title">Financial Assessment & Underwriting</p>
            <div class="preview-grid">
                <div>
                    <span class="preview-label">Annual Income:</span>
                    <span class="preview-value font-bold" id="viewMemberIncome">—</span>
                </div>
                <div>
                    <span class="preview-label">Monthly Obligations:</span>
                    <span class="preview-value text-rose" id="viewMemberObligations">—</span>
                </div>
                <div>
                    <span class="preview-label">Derived Monthly Income:</span>
                    <span class="preview-value" id="viewMemberMonthlyIncome">—</span>
                </div>
                <div>
                    <span class="preview-label">Calculated FOIR:</span>
                    <span class="preview-value font-bold" id="viewMemberFoir">—</span>
                </div>
                <div>
                    <span class="preview-label">Bank Account:</span>
                    <span class="preview-value font-mono" id="viewMemberBank">—</span>
                </div>
                <div>
                    <span class="preview-label">IFSC Code:</span>
                    <span class="preview-value font-mono" id="viewMemberIfsc">—</span>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="customer-preview" style="margin-bottom: 20px;">
            <p class="preview-title">Residential Location</p>
            <p class="preview-value" id="viewMemberAddress" style="margin: 0;">—</p>
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
        max-width: 800px;
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
        $('#membersTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "pageLength": 10,
            "language": {
                "search": "",
                "searchPlaceholder": "🔍 Search members, codes, Aadhaar, groups..."
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

    ['newMemberModal', 'editMemberModal', 'viewMemberModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal);
                }
            });
        }
    });

    // ── Edit Member Handler ──
    function openEditMemberModal(m) {
        const form = document.getElementById('editMemberForm');
        form.action = `/los/member/${m.id}`;

        document.getElementById('edit_group_id').value = m.group_id || '';
        document.getElementById('edit_full_name').value = m.full_name || '';
        document.getElementById('edit_customer_code').value = m.customer_code || '';
        document.getElementById('edit_gender').value = m.gender || 'female';
        document.getElementById('edit_phone').value = m.phone || '';
        document.getElementById('edit_aadhaar_last4').value = m.aadhaar_last4 || '';
        document.getElementById('edit_pan_number').value = m.pan_number || '';
        document.getElementById('edit_annual_household_income').value = m.annual_household_income || '';
        document.getElementById('edit_monthly_debt_obligations').value = m.monthly_debt_obligations || '';
        document.getElementById('edit_address').value = m.address || '';
        document.getElementById('edit_district').value = m.district || '';
        document.getElementById('edit_state').value = m.state || '';
        document.getElementById('edit_pincode').value = m.pincode || '';
        document.getElementById('edit_bank_account_no').value = m.bank_account_no || '';
        document.getElementById('edit_ifsc_code').value = m.ifsc_code || '';

        openModal(document.getElementById('editMemberModal'));
    }

    // ── View Member Handler ──
    function viewMember(m) {
        document.getElementById('viewMemberId').innerText = '#CUS-' + String(m.id).padStart(3, '0');
        document.getElementById('viewMemberCode').innerText = m.customer_code || '—';
        document.getElementById('viewMemberName').innerText = m.full_name || '—';
        document.getElementById('viewMemberGenderPhone').innerText = (m.gender ? m.gender.toUpperCase() : '—') + ' · ' + (m.phone || '—');
        document.getElementById('viewMemberAadhaar').innerText = m.aadhaar_last4 ? ('XXXX-XXXX-' + m.aadhaar_last4) : '—';
        document.getElementById('viewMemberPan').innerText = m.pan_number || 'Not Provided';

        if (m.group) {
            document.getElementById('viewMemberGroup').innerText = m.group.group_name || '—';
            if (m.group.center) {
                document.getElementById('viewMemberCenter').innerText = m.group.center.center_name + ' (' + m.group.center.center_code + ')';
                document.getElementById('viewMemberBranch').innerText = m.group.center.branch_name || '—';
                document.getElementById('viewMemberMeeting').innerText = (m.group.center.meeting_day || '—') + ' @ ' + (m.group.center.meeting_time ? m.group.center.meeting_time.substring(0, 5) : '');
            } else {
                document.getElementById('viewMemberCenter').innerText = '—';
                document.getElementById('viewMemberBranch').innerText = '—';
                document.getElementById('viewMemberMeeting').innerText = '—';
            }
        } else {
            document.getElementById('viewMemberGroup').innerText = '—';
            document.getElementById('viewMemberCenter').innerText = '—';
            document.getElementById('viewMemberBranch').innerText = '—';
            document.getElementById('viewMemberMeeting').innerText = '—';
        }

        const annualIncome = parseFloat(m.annual_household_income) || 0;
        const monthlyDebt = parseFloat(m.monthly_debt_obligations) || 0;
        const monthlyIncome = annualIncome / 12;
        const foir = monthlyIncome > 0 ? ((monthlyDebt / monthlyIncome) * 100).toFixed(1) : 0;

        document.getElementById('viewMemberIncome').innerText = '₹' + annualIncome.toLocaleString('en-IN') + ' / yr';
        document.getElementById('viewMemberObligations').innerText = '₹' + monthlyDebt.toLocaleString('en-IN') + ' / mo';
        document.getElementById('viewMemberMonthlyIncome').innerText = '₹' + Math.round(monthlyIncome).toLocaleString('en-IN') + ' / mo';
        document.getElementById('viewMemberFoir').innerText = foir + '% (RBI Cap: 50%)';

        document.getElementById('viewMemberBank').innerText = m.bank_account_no || 'Not on file';
        document.getElementById('viewMemberIfsc').innerText = m.ifsc_code || '—';

        const addressParts = [m.address, m.district, m.state, m.pincode].filter(Boolean);
        document.getElementById('viewMemberAddress').innerText = addressParts.length ? addressParts.join(', ') : 'Address not specified';

        openModal(document.getElementById('viewMemberModal'));
    }
</script>
@endpush