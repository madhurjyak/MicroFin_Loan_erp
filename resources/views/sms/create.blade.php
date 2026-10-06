@extends('layouts.app')

@section('title', 'Open RD Account')
@section('page-title', 'Open New Recurring Deposit Account')

@section('content')

<div style="max-width:700px;margin:0 auto;">
    <div class="panel">
        <div class="panel-header-action mb-4">
            <h2 class="panel-title">New RD Account — Member Enrolment</h2>
            <a href="{{ route('sms.savings.index') }}" class="btn-secondary">← Back</a>
        </div>
        <div style="padding:1.5rem;">

            @if($errors->any())
            <div class="flash-message flash-error" style="margin-bottom:1rem;">
                @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('sms.savings.store') }}">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                    <div class="form-group">
                        <label for="center_id" class="form-label mb-1">Center</label>
                        <select id="center_id" class="form-input">
                            <option value="">-- Select a center --</option>
                            @foreach($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->center_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="group_id" class="form-label mb-1">Group</label>
                        <select id="group_id" class="form-input" disabled>
                            <option value="">-- Select a group --</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" data-center="{{ $group->center_id }}">{{ $group->group_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label mb-1" for="customer_id">Member *</label>
                    <select name="customer_id" id="customer_id" class="form-input" required disabled>
                        <option value="">— Select Member —</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" 
                            data-group="{{ $c->group_id }}"
                            {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }} ({{ $c->customer_code }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                    <div class="form-group">
                        <label class="form-label mb-1" for="deposit_amount">Periodic Deposit Amount (₹) *</label>
                        <input type="number" id="deposit_amount" name="deposit_amount" class="form-input"
                            value="{{ old('deposit_amount', 100) }}" step="0.01" min="50" required>
                        <small style="color:var(--text-muted);display:block;margin-top:4px;">Min: ₹50 (weekly) / ₹200 (monthly)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label mb-1" for="interest_rate">Interest Rate (% p.a.) *</label>
                        <input type="number" id="interest_rate" name="interest_rate" class="form-input"
                            value="{{ old('interest_rate', 5.50) }}" step="0.01" min="1" max="12" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label mb-1" for="tenure">Tenure (Months) *</label>
                        <input type="number" id="tenure" name="tenure" class="form-input"
                            value="{{ old('tenure', 12) }}" min="6" max="60" required>
                        <small style="color:var(--text-muted);display:block;margin-top:4px;">6 – 60 months</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label mb-1" for="frequency">Deposit Frequency *</label>
                        <select id="frequency" name="frequency" class="form-input" required>
                            <option value="weekly" {{ old('frequency','weekly')==='weekly'?'selected':'' }}>Weekly</option>
                            <option value="monthly" {{ old('frequency')==='monthly'?'selected':'' }}>Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label mb-1" for="opening_date">Opening Date *</label>
                        <input type="date" id="opening_date" name="opening_date" class="form-input"
                            value="{{ old('opening_date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                {{-- Live Maturity Estimate --}}
                <div id="maturityPreview" style="background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.2);border-radius:var(--border-radius-lg);padding:24px;margin-bottom:24px;display:none;">
                    <p style="font-weight:600;margin-bottom:12px;font-size:1.1rem;color:var(--text-primary);">📊 Maturity Estimate</p>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;font-size:1rem;">
                        <div><span style="color:var(--text-muted);font-size:0.85rem;display:block;margin-bottom:4px;">Total Principal</span><strong id="est-principal" style="font-size:1.2rem;">—</strong></div>
                        <div><span style="color:var(--text-muted);font-size:0.85rem;display:block;margin-bottom:4px;">Est. Interest</span><strong id="est-interest" style="color:#10b981;font-size:1.2rem;">—</strong></div>
                        <div><span style="color:var(--text-muted);font-size:0.85rem;display:block;margin-bottom:4px;">Maturity Amount</span><strong id="est-maturity" style="color:var(--brand-600);font-size:1.2rem;">—</strong></div>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width:100%;">Open RD Account</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Cascading Dropdowns
    const centerSelect = document.getElementById('center_id');
    const groupSelect = document.getElementById('group_id');
    const customerSelect = document.getElementById('customer_id');
    
    // Store original options
    const allGroups = Array.from(document.querySelectorAll('#group_id option')).slice(1);
    const allCustomers = Array.from(document.querySelectorAll('#customer_id option')).slice(1);

    centerSelect.addEventListener('change', function() {
        const centerId = this.value;
        
        groupSelect.innerHTML = '<option value="">-- Select a group --</option>';
        customerSelect.innerHTML = '<option value="">— Select Member —</option>';
        customerSelect.disabled = true;
        
        if (centerId) {
            groupSelect.disabled = false;
            allGroups.forEach(opt => {
                if (opt.dataset.center == centerId) {
                    groupSelect.appendChild(opt.cloneNode(true));
                }
            });
        } else {
            groupSelect.disabled = true;
        }
    });

    groupSelect.addEventListener('change', function() {
        const groupId = this.value;
        
        customerSelect.innerHTML = '<option value="">— Select Member —</option>';
        
        if (groupId) {
            customerSelect.disabled = false;
            allCustomers.forEach(opt => {
                if (opt.dataset.group == groupId) {
                    customerSelect.appendChild(opt.cloneNode(true));
                }
            });
        } else {
            customerSelect.disabled = true;
        }
    });

    // Restore dropdowns if old value exists
    if (customerSelect.value) {
        const selectedCustomerOpt = customerSelect.options[customerSelect.selectedIndex];
        if (selectedCustomerOpt) {
            const groupId = selectedCustomerOpt.dataset.group;
            // Find corresponding group to get center
            const groupOpt = allGroups.find(opt => opt.value === groupId);
            if (groupOpt) {
                const centerId = groupOpt.dataset.center;
                centerSelect.value = centerId;
                
                // Trigger change to populate groups
                centerSelect.dispatchEvent(new Event('change'));
                
                groupSelect.value = groupId;
                
                // Trigger change to populate customers
                groupSelect.dispatchEvent(new Event('change'));
                
                customerSelect.value = selectedCustomerOpt.value;
            }
        }
    }

function calcMaturity() {
    const deposit  = parseFloat(document.getElementById('deposit_amount').value) || 0;
    const rate     = parseFloat(document.getElementById('interest_rate').value) / 100 || 0;
    const tenure   = parseInt(document.getElementById('tenure').value) || 0;
    const freq     = document.getElementById('frequency').value;

    if (!deposit || !tenure) { document.getElementById('maturityPreview').style.display='none'; return; }

    const periodsPerYear = freq === 'weekly' ? 52 : 12;
    const n = freq === 'weekly' ? Math.round(tenure * 52 / 12) : tenure;
    const periodicRate = rate / periodsPerYear;

    const totalPrincipal = deposit * n;
    const interest = deposit * periodicRate * (n * (n + 1) / 2);
    const maturity = totalPrincipal + interest;

    const fmt = v => '₹' + v.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('est-principal').textContent = fmt(totalPrincipal);
    document.getElementById('est-interest').textContent  = fmt(interest);
    document.getElementById('est-maturity').textContent  = fmt(maturity);
    document.getElementById('maturityPreview').style.display = 'block';
}
['deposit_amount','interest_rate','tenure','frequency'].forEach(id =>
    document.getElementById(id).addEventListener('input', calcMaturity)
);
calcMaturity();
</script>
@endpush
