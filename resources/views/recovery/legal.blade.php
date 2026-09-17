@extends('layouts.app')
@section('title', 'Legal & OTS Hub')
@section('page-title', '⚖️ Indian Statutory Legal & OTS Hub')

@section('content')
@php
    function inrLg(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a,2)); $i = str_replace(',','',$p[0]);
        $l = strlen($i); if ($l<=3) return '₹'.$i.'.'.$p[1];
        $r = substr($i,0,$l-3); $r = preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$r);
        return '₹'.$r.','.substr($i,-3).'.'.$p[1];
    }
@endphp

<div id="legalHub" style="display: flex; flex-direction: column; gap: 20px;">

    <!-- Tab Bar -->
    <div style="background: var(--surface-light); border-radius: var(--border-radius-xl); box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); padding: 6px; display: inline-flex; gap: 4px; align-self: flex-start;">
        <button type="button" class="tab-btn active" data-tab="notices" style="padding: 8px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: var(--transition-smooth); background: var(--brand-600); color: white; box-shadow: var(--shadow-sm);">
            📄 Statutory Notices
        </button>
        <button type="button" class="tab-btn" data-tab="ots" style="padding: 8px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: var(--transition-smooth); background: transparent; color: var(--text-secondary);">
            🤝 OTS Calculator
        </button>
    </div>

    <!-- ── Notices Tab ─────────────────────────────────────────────────── -->
    <div id="tab-notices" class="tab-pane active" style="display: block;">
        <div class="panel" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-weight: 600; color: var(--text-secondary); margin: 0; font-size: 16px;">Statutory Notices Registry</h2>
                <span class="text-muted" style="font-size: 12px;">{{ $notices->total() }} total notices</span>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr style="background: rgba(15, 23, 42, 0.02);">
                            <th class="text-left" style="padding-left: 20px;">Notice Ref</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Borrower</th>
                            <th class="text-left">Loan A/c</th>
                            <th class="text-left">Dispatched</th>
                            <th class="text-left">Speed Post</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="padding-right: 20px;">Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notices as $notice)
                        <tr>
                            <td class="font-mono text-muted" style="font-size: 12px; padding-left: 20px;">{{ $notice->notice_ref_no }}</td>
                            <td>
                                @php
                                    $noticeTypeColors = [
                                        'Sec_138_NI_Act'               => 'badge-npa',
                                        'Sec_25_PSSA_AutoDebit_Bounce' => 'badge-sma1',
                                        'Loan_Recall_Notice'           => 'badge-sma0',
                                        'SARFAESI_13_2'                => 'badge-npa',
                                    ];
                                @endphp
                                <span class="{{ $noticeTypeColors[$notice->notice_type] ?? 'badge-std' }}">
                                    {{ str_replace(['Sec_','_',' '],[' §',' ',' '],$notice->notice_type) }}
                                </span>
                            </td>
                            <td style="font-weight: 500; color: var(--text-primary); font-size: 14px;">{{ $notice->loan->customer->full_name }}</td>
                            <td class="font-mono text-muted" style="font-size: 12px;">{{ $notice->loan->loan_account_no }}</td>
                            <td class="text-muted" style="font-size: 12px;">{{ $notice->dispatch_date->format('d-M-Y') }}</td>
                            <td class="font-mono text-muted" style="font-size: 12px;">{{ $notice->tracking_speedpost_no ?? '—' }}</td>
                            <td class="text-center">
                                <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
                                    {{ $notice->status === 'served' ? 'background: #d1fae5; color: #047857;'
                                        : ($notice->status === 'dispatched' ? 'background: #dbeafe; color: #1d4ed8;'
                                        : 'background: var(--border-light); color: var(--text-secondary);') }}">
                                    {{ ucfirst(str_replace('_',' ',$notice->status)) }}
                                </span>
                            </td>
                            <td class="text-center" style="padding-right: 20px;">
                                <a href="{{ route('recovery.legal.notice', $notice->id) }}" target="_blank" class="text-link" style="font-weight: 600;">🖨 Print</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="empty-state">No statutory notices generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($notices->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border-light);">{{ $notices->appends(['ots_page' => request('ots_page')])->links() }}</div>
            @endif
        </div>
    </div>

    <!-- ── OTS Calculator Tab ──────────────────────────────────────────── -->
    <div id="tab-ots" class="tab-pane" style="display: none; grid-template-columns: repeat(2, 1fr); gap: 20px;">

        <!-- Calculator Form -->
        <div class="panel" style="grid-column: span 1; display: flex; flex-direction: column;">
            <h2 style="font-weight: 600; color: var(--text-secondary); margin: 0 0 20px 0; font-size: 16px;">One-Time Settlement Calculator</h2>

            <form id="otsForm" method="POST" action="{{ route('recovery.legal.ots') }}">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label class="form-label">Select Loan Account *</label>
                        <select name="loan_id" id="ots_loan_id" required class="form-input">
                            <option value="">— Select NPA / SMA-2 Loan —</option>
                            @foreach($eligibleLoans as $loan)
                            <option value="{{ $loan->id }}">
                                {{ $loan->loan_account_no }} — {{ $loan->customer->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Proposed Settlement Amount (₹) *</label>
                        <input type="number" name="proposed_amount" id="proposed_amount" step="100" min="1" required class="form-input" placeholder="e.g. 45000">
                    </div>
                    <button type="button" id="btnCalculateOts" class="btn-primary" style="width: 100%;">
                        Calculate OTS Breakdown
                    </button>
                </div>
            </form>

            <!-- OTS Result -->
            <div id="otsResult" style="display: none; flex-direction: column; gap: 12px; margin-top: 20px;">
                <div style="background: var(--brand-50); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 8px; font-size: 14px;">
                    <div style="display: flex; justify-content: space-between;"><span class="text-muted">Total Outstanding</span><span id="res_outstanding" style="font-weight: 700; color: var(--text-primary);"></span></div>
                    <div style="display: flex; justify-content: space-between;"><span class="text-muted">Proposed Amount</span><span id="res_proposed" style="font-weight: 700; color: #047857;"></span></div>
                    <hr style="border: none; border-top: 1px solid var(--brand-100); margin: 4px 0;">
                    <div style="display: flex; justify-content: space-between;"><span class="text-muted">Waiver: Penal + GST</span><span id="res_waiver_penal" style="font-weight: 600; color: #e11d48;"></span></div>
                    <div style="display: flex; justify-content: space-between;"><span class="text-muted">Waiver: Interest</span><span id="res_waiver_interest" style="font-weight: 600; color: #e11d48;"></span></div>
                    <div style="display: flex; justify-content: space-between;"><span class="text-muted">Waiver: Principal</span><span id="res_waiver_principal" style="font-weight: 600; color: #e11d48;"></span></div>
                    <hr style="border: none; border-top: 1px solid var(--brand-100); margin: 4px 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 18px;">
                        <span style="font-weight: 700; color: var(--text-secondary);">Haircut %</span>
                        <span id="res_haircut" style="font-weight: 900; color: #be123c;"></span>
                    </div>
                </div>
                <div id="res_authority_box" style="border-radius: 12px; padding: 16px; font-size: 14px; font-weight: 600; text-align: center;"></div>
                <form method="POST" action="{{ route('recovery.legal.ots') }}" id="otsSubmitForm">
                    @csrf
                    <input type="hidden" name="loan_id" id="hidden_loan_id">
                    <input type="hidden" name="proposed_amount" id="hidden_proposed">
                    <button type="submit" class="btn-success" style="width: 100%;">
                        ✅ Save OTS Proposal
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing OTS Proposals -->
        <div class="panel" style="grid-column: span 1; padding: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light);">
                <h2 style="font-weight: 600; color: var(--text-secondary); margin: 0; font-size: 16px;">Pending OTS Proposals</h2>
            </div>
            <div style="display: flex; flex-direction: column;">
                @forelse($otsList as $ots)
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light);">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px;">
                        <div>
                            <p style="font-weight: 600; color: var(--text-primary); font-size: 14px; margin: 0;">{{ $ots->loan->loan_account_no }}</p>
                            <p class="text-muted" style="font-size: 12px; margin: 0;">{{ $ots->loan->customer->full_name }}</p>
                        </div>
                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;
                            {{ $ots->status === 'approved' ? 'background: #d1fae5; color: #065f46;'
                             : ($ots->status === 'rejected' ? 'background: #fee2e2; color: #b91c1c;'
                             : 'background: #fef9c3; color: #854d0e;') }}">
                            {{ ucfirst($ots->status) }}
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                        <div><span class="text-muted">Outstanding:</span> <span style="font-weight: 600; color: var(--text-secondary);">{{ inrLg((float)$ots->total_outstanding) }}</span></div>
                        <div><span class="text-muted">Proposed:</span> <span style="font-weight: 600; color: #047857;">{{ inrLg((float)$ots->proposed_amount) }}</span></div>
                        <div><span class="text-muted">Haircut:</span> <span style="font-weight: 700; color: #e11d48;">{{ $ots->haircut_pct }}%</span></div>
                        <div><span class="text-muted">Authority:</span> <span style="font-weight: 600; color: var(--brand-700);">{{ str_replace('_',' ',$ots->approval_authority) }}</span></div>
                    </div>
                </div>
                @empty
                <div class="empty-state">No OTS proposals yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
@media (max-width: 1024px) {
    #tab-ots {
        grid-template-columns: 1fr !important;
    }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tabs logic
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            
            // Update buttons
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color = 'var(--text-secondary)';
                b.style.boxShadow = 'none';
            });
            btn.classList.add('active');
            btn.style.background = 'var(--brand-600)';
            btn.style.color = 'white';
            btn.style.boxShadow = 'var(--shadow-sm)';

            // Update panes
            tabPanes.forEach(pane => {
                if (pane.id === 'tab-' + target) {
                    if (target === 'ots') {
                        pane.style.display = 'grid';
                    } else {
                        pane.style.display = 'block';
                    }
                } else {
                    pane.style.display = 'none';
                }
            });
        });
    });

    // Handle initial state if there's a hash or page param
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('ots_page') || window.location.hash === '#ots') {
        document.querySelector('.tab-btn[data-tab="ots"]').click();
    }

    // Calculator logic
    function inrFormat(n) {
        n = parseFloat(n).toFixed(2);
        let parts = n.split('.');
        let i = parts[0].replace(/,/g,'');
        let l = i.length;
        if (l <= 3) return '₹' + i + '.' + parts[1];
        let last3 = i.slice(-3);
        let rest = i.slice(0, l-3);
        rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',');
        return '₹' + rest + ',' + last3 + '.' + parts[1];
    }

    const btnCalculateOts = document.getElementById('btnCalculateOts');
    if(btnCalculateOts) {
        btnCalculateOts.addEventListener('click', async () => {
            const loanId = document.getElementById('ots_loan_id').value;
            const proposed = document.getElementById('proposed_amount').value;
            if (!loanId || !proposed) { alert('Please select a loan and enter proposed amount.'); return; }

            try {
                const res = await fetch('{{ route("recovery.legal.ots") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ loan_id: loanId, proposed_amount: proposed })
                });
                
                if(!res.ok) {
                    alert('Error calculating OTS.');
                    return;
                }
                
                const data = await res.json();

                document.getElementById('res_outstanding').textContent       = inrFormat(data.total_outstanding);
                document.getElementById('res_proposed').textContent          = inrFormat(data.proposed_amount);
                document.getElementById('res_waiver_penal').textContent      = '– ' + inrFormat(data.waiver_penal_gst);
                document.getElementById('res_waiver_interest').textContent   = '– ' + inrFormat(data.waiver_interest);
                document.getElementById('res_waiver_principal').textContent  = '– ' + inrFormat(data.waiver_principal);
                document.getElementById('res_haircut').textContent           = data.haircut_pct + '%';

                const authColors = { 
                    Branch_Manager: 'background: #d1fae5; color: #065f46;', 
                    Regional_Credit_Committee: 'background: #ffedd5; color: #9a3412;', 
                    Board: 'background: #fee2e2; color: #b91c1c;' 
                };
                const authorityBox = document.getElementById('res_authority_box');
                authorityBox.style = 'border-radius: 12px; padding: 16px; font-size: 14px; font-weight: 600; text-align: center; ' + (authColors[data.approval_authority] || 'background: var(--border-light); color: var(--text-secondary);');
                authorityBox.textContent = '🏛️ Approval Required: ' + data.approval_authority.replace(/_/g,' ');

                document.getElementById('hidden_loan_id').value = loanId;
                document.getElementById('hidden_proposed').value = proposed;
                
                const otsResult = document.getElementById('otsResult');
                otsResult.style.display = 'flex';
            } catch (error) {
                console.error(error);
                alert('An error occurred.');
            }
        });
    }
});
</script>
@endpush
@endsection
