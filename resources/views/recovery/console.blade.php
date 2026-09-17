@extends('layouts.app')
@section('title', 'Delinquency & Recovery Console')
@section('page-title', '🚨 Delinquency & Recovery Console')

@section('content')
@php
    function inrR(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a,2)); $i = str_replace(',','',$p[0]);
        $l = strlen($i); if ($l<=3) return '₹'.$i.'.'.$p[1];
        $r = substr($i,0,$l-3); $r = preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$r);
        return '₹'.$r.','.substr($i,-3).'.'.$p[1];
    }

    $bucketMeta = [
        'all'           => ['All Cases',       'background: var(--surface-light); color: var(--text-secondary); border-color: var(--border-light);'],
        'SMA-0'         => ['SMA-0 (1–30 DPD)','background: #fef9c3; color: #854d0e; border-color: #fde047;'],
        'SMA-1'         => ['SMA-1 (31–60)',   'background: #ffedd5; color: #9a3412; border-color: #fdba74;'],
        'SMA-2'         => ['SMA-2 (61–90)',   'background: #fee2e2; color: #b91c1c; border-color: #fca5a5;'],
        'NPA_SubStandard'=>['NPA (91+ DPD)',   'background: #fecdd3; color: #881337; border-color: #fb7185;'],
        'Doubtful'      => ['Doubtful',        'background: #fda4af; color: #4c0519; border-color: #f43f5e;'],
    ];
@endphp

<div id="recoveryConsole">

<!-- Bucket Filter Tabs -->
<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px;">
    @foreach($bucketMeta as $key => $meta)
    <a href="{{ route('recovery.console') }}?bucket={{ $key }}"
       style="padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; border: 1px solid; text-decoration: none; transition: var(--transition-smooth);
              {{ $bucket === $key ? $meta[1].' box-shadow: 0 0 0 2px var(--surface-light), 0 0 0 4px currentColor;' : 'background: var(--surface-light); color: var(--text-secondary); border-color: var(--border-light);' }}">
        {{ $meta[0] }}
        @if($key !== 'all' && isset($bucketCounts[$key]))
            <span style="margin-left: 4px; background: rgba(255,255,255,0.6); border-radius: 20px; padding: 2px 6px; color: inherit;">{{ $bucketCounts[$key] }}</span>
        @endif
    </a>
    @endforeach
</div>

<!-- Cases Table -->
<div class="panel" style="padding: 0; overflow: hidden;">

    <!-- Table Header -->
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between;">
        <h2 style="font-weight: 600; color: var(--text-secondary); margin: 0; font-size: 16px;">
            {{ $bucketMeta[$bucket][0] ?? 'All Cases' }}
            <span style="margin-left: 8px; color: var(--text-tertiary); font-size: 14px; font-weight: 400;">({{ $cases->total() }} accounts)</span>
        </h2>
        <button id="btnLogGlobal" class="btn-primary-sm" style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 12px;">
            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Log Contact
        </button>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr style="background: rgba(15, 23, 42, 0.02);">
                    <th class="text-left" style="padding-left: 20px;">Customer</th>
                    <th class="text-left">Loan A/c</th>
                    <th class="text-left">Center</th>
                    <th class="text-center">DPD</th>
                    <th class="text-center">Bucket</th>
                    <th class="text-right">Overdue Amt</th>
                    <th class="text-left">Officer</th>
                    <th class="text-left">Last Contact</th>
                    <th class="text-center" style="padding-right: 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                <tr>
                    <td style="padding-left: 20px;">
                        <p style="font-weight: 500; color: var(--text-primary); margin: 0; font-size: 14px;">{{ $case->loan->customer->full_name }}</p>
                        <p style="font-size: 12px; color: var(--text-tertiary); margin: 0;">{{ $case->loan->customer->phone }}</p>
                    </td>
                    <td class="font-mono" style="font-size: 12px;">
                        <a href="{{ route('lms.loans.show', $case->loan_id) }}" class="text-link">{{ $case->loan->loan_account_no }}</a>
                    </td>
                    <td class="text-muted" style="font-size: 12px;">{{ $case->loan->customer->group->center->center_name ?? '—' }}</td>
                    <td class="text-center">
                        <span style="font-size: 18px; font-weight: 700; color: {{ $case->dpd > 90 ? '#be123c' : ($case->dpd > 60 ? '#dc2626' : ($case->dpd > 30 ? '#ea580c' : '#ca8a04')) }};">
                            {{ $case->dpd }}
                        </span>
                    </td>
                    <td class="text-center">
                        @php
                            $badgeMap = ['Standard'=>'badge-std','SMA-0'=>'badge-sma0','SMA-1'=>'badge-sma1','SMA-2'=>'badge-sma2','NPA_SubStandard'=>'badge-npa','Doubtful'=>'badge-npa'];
                            $bc = $badgeMap[$case->asset_classification] ?? 'badge-std';
                        @endphp
                        <span class="{{ $bc }}">{{ $case->asset_classification }}</span>
                    </td>
                    <td class="text-right font-bold" style="color: #be123c; font-size: 14px;">
                        {{ inrR((float)$case->total_outstanding) }}
                    </td>
                    <td class="text-muted" style="font-size: 12px;">{{ $case->assigned_officer ?? '—' }}</td>
                    <td class="text-muted" style="font-size: 12px;">
                        {{ $case->last_contacted_at ? $case->last_contacted_at->format('d-M-Y H:i') : '—' }}
                    </td>
                    <td class="text-center" style="padding-right: 20px;">
                        <button type="button" class="btn-log-case" data-case-id="{{ $case->id }}" style="font-size: 12px; background: var(--brand-100); color: var(--brand-700); padding: 4px 12px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: var(--transition-smooth);">
                            Log
                        </button>
                        @if(auth()->user()->hasRole('manager', 'admin'))
                        <button type="button" class="btn-assign-case" data-case-id="{{ $case->id }}" style="font-size: 12px; background: #d1fae5; color: #047857; padding: 4px 12px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; margin-left: 4px; transition: var(--transition-smooth);">
                            Assign
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty-state">No cases in this bucket 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($cases->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border-light);">
        {{ $cases->links() }}
    </div>
    @endif

</div>

<!-- ── Log Contact Modal ──────────────────────────────────────────── -->
<div id="logModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h3 style="font-weight: 700; color: var(--text-primary); font-size: 18px; margin: 0;">Log Field Visit / Telecall</h3>
            <button type="button" class="modal-close-btn" style="background: none; border: none; cursor: pointer; color: var(--text-tertiary);">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if($errors->any())
        <div class="alert-danger" style="margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <p style="margin: 0;">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px; margin-bottom: 16px;">
            <p style="font-size: 12px; font-weight: 600; color: #92400e; margin: 0;">⚖️ RBI Fair Practices Code</p>
            <p style="font-size: 12px; color: #b45309; margin: 4px 0 0 0;">Contact hours strictly restricted to <strong>08:00 AM – 07:00 PM</strong> only.</p>
        </div>

        <form method="POST" action="{{ route('recovery.log-contact') }}" id="logContactForm">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label class="form-label">Recovery Case *</label>
                    <select name="recovery_case_id" id="logCaseSelect" required class="form-input">
                        <option value="">— Select Case —</option>
                        @foreach($cases as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->loan->loan_account_no }} — {{ $c->loan->customer->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label">Type *</label>
                        <select name="interaction_type" required class="form-input">
                            <option value="telecalling">📞 Telecalling</option>
                            <option value="field_visit">🚗 Field Visit</option>
                            <option value="whatsapp">💬 WhatsApp</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Contact Date & Time *</label>
                        <input type="datetime-local" name="contact_time" id="contact_time_input" required
                            min="{{ now()->format('Y-m-d') }}T08:00"
                            max="{{ now()->format('Y-m-d') }}T19:00"
                            class="form-input {{ $errors->has('contact_time') ? 'border-rose' : '' }}">
                    </div>
                </div>
                <div>
                    <label class="form-label">Disposition *</label>
                    <select name="disposition" required class="form-input">
                        <option value="PTP">Promise to Pay (PTP)</option>
                        <option value="Broken_PTP">Broken PTP</option>
                        <option value="Dispute">Dispute</option>
                        <option value="Absconding">Absconding</option>
                        <option value="Crop_Failure">Crop Failure</option>
                        <option value="Medical_Emergency">Medical Emergency</option>
                        <option value="RNR">RNR (Ringing, No Response)</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label">PTP Date</label>
                        <input type="date" name="ptp_date" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">PTP Amount (₹)</label>
                        <input type="number" name="ptp_amount" step="0.01" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Additional remarks..." class="form-input"></textarea>
                </div>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn-secondary modal-close-btn" style="flex: 1;">Cancel</button>
                <button type="submit" class="btn-primary" style="flex: 1;">Save Contact Log</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Assign Agent Modal (Manager/Admin only) ── -->
@if(auth()->user()->hasRole('manager', 'admin'))
<div id="assignModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h3 style="font-weight: 700; color: var(--text-primary); font-size: 18px; margin: 0;">👤 Assign Recovery Agent</h3>
            <button type="button" class="modal-close-btn" style="background: none; border: none; cursor: pointer; color: var(--text-tertiary);">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('recovery.assign-agent') }}">
            @csrf
            <input type="hidden" name="recovery_case_id" id="assignCaseIdInput">
            <div class="form-group">
                <label class="form-label">Select Agent</label>
                <select name="agent_id" required class="form-input">
                    <option value="">— Select Agent —</option>
                    @foreach($agents as $ag)
                    <option value="{{ $ag->id }}">{{ $ag->name }} ({{ $ag->branch_name ?? 'No Branch' }})</option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn-secondary modal-close-btn" style="flex: 1;">Cancel</button>
                <button type="submit" class="btn-success" style="flex: 1;">Assign Agent</button>
            </div>
        </form>
    </div>
</div>
@endif

</div>

@push('scripts')
<style>
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
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const logModal = document.getElementById('logModal');
    const assignModal = document.getElementById('assignModal');
    const btnLogGlobal = document.getElementById('btnLogGlobal');
    const closeBtns = document.querySelectorAll('.modal-close-btn');
    const logCaseSelect = document.getElementById('logCaseSelect');
    const assignCaseIdInput = document.getElementById('assignCaseIdInput');

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

    if(btnLogGlobal) {
        btnLogGlobal.addEventListener('click', () => {
            if(logCaseSelect) logCaseSelect.value = "";
            openModal(logModal);
        });
    }

    document.querySelectorAll('.btn-log-case').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if(logCaseSelect) logCaseSelect.value = btn.dataset.caseId;
            openModal(logModal);
        });
    });

    document.querySelectorAll('.btn-assign-case').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if(assignCaseIdInput) assignCaseIdInput.value = btn.dataset.caseId;
            if(assignModal) openModal(assignModal);
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    [logModal, assignModal].forEach(modal => {
        if(modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal);
                }
            });
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (logModal && logModal.classList.contains('show')) closeModal(logModal);
            if (assignModal && assignModal.classList.contains('show')) closeModal(assignModal);
        }
    });

    const logForm = document.getElementById('logContactForm');
    if(logForm) {
        logForm.addEventListener('submit', (e) => {
            const t = document.getElementById('contact_time_input').value;
            if (!t) return;
            const h = parseInt(t.split('T')[1].split(':')[0]);
            const m = parseInt(t.split('T')[1].split(':')[1]);
            const mins = h * 60 + m;
            if (mins < 480 || mins > 1140) {
                alert('⛔ RBI Fair Practices Code: Contact not allowed before 08:00 AM or after 07:00 PM.');
                e.preventDefault();
            }
        });
    }
});
</script>
@endpush
@endsection
