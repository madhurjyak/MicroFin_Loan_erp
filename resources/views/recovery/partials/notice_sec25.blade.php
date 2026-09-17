<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notice u/s 25 PSSA — {{ $notice->notice_ref_no }}</title>
<style>
    body { font-family: 'Times New Roman', serif; font-size: 13px; margin: 40px; line-height: 1.6; color: #111; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .notice-title { font-size: 16px; font-weight: bold; text-align: center; margin: 15px 0; text-decoration: underline; }
    .section { margin: 12px 0; }
    .label { font-weight: bold; }
    .footer { margin-top: 40px; }
    .signature { margin-top: 60px; }
    @media print { button { display: none; } }
</style>
</head>
<body>

<div class="header">
    <p style="font-size:18px;font-weight:bold;">INDIALEND MICROFINANCE PRIVATE LIMITED</p>
    <p>Registered Office: 42, Mahatma Gandhi Road, Guwahati, Assam – 781001</p>
    <p>NBFC-MFI Registration No.: N-01.00000 | Phone: 0361-2000000</p>
</div>

<div class="notice-title">
    STATUTORY DEMAND NOTICE UNDER SECTION 25 OF THE<br>
    PAYMENT AND SETTLEMENT SYSTEMS ACT, 2007<br>
    (AUTO-DEBIT / e-NACH MANDATE DISHONOUR)
</div>

<div class="section">
    <p><span class="label">Ref No.:</span> {{ $notice->notice_ref_no }}</p>
    <p><span class="label">Date:</span> {{ $notice->dispatch_date->format('d F Y') }}</p>
</div>

<div class="section">
    <p><span class="label">To,</span></p>
    <p>{{ $loan->customer->full_name }}</p>
    <p>{{ $loan->customer->address }},</p>
    <p>{{ $loan->customer->district }}, {{ $loan->customer->state }} – {{ $loan->customer->pincode }}</p>
</div>

<div class="section">
    <p><strong>Subject: Demand Notice for Dishonoured Auto-Debit Mandate — Loan Account No. {{ $loan->loan_account_no }}</strong></p>
</div>

<div class="section">
    <p>Dear {{ $loan->customer->full_name }},</p>

    <p>This is to inform you that an Electronic Clearing Service (ECS) / National Automated Clearing House (NACH) auto-debit mandate issued by you bearing your Bank Account bearing IFSC Code <strong>{{ $loan->customer->ifsc_code ?? 'XXXX0000000' }}</strong>, presented by us for recovery of the loan repayment installment due under Loan Account No. <strong>{{ $loan->loan_account_no }}</strong>, has been dishonoured / returned unpaid by your bank.</p>

    <p>The details of the dishonoured mandate are as under:</p>

    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;margin:10px 0;">
        <tr><td class="label">Loan Account Number</td><td>{{ $loan->loan_account_no }}</td></tr>
        <tr><td class="label">Principal Amount Disbursed</td><td>₹{{ number_format($loan->principal_amount, 2) }}</td></tr>
        <tr><td class="label">Disbursement Date</td><td>{{ $loan->disbursement_date->format('d-M-Y') }}</td></tr>
        <tr><td class="label">Outstanding Amount (approx.)</td><td>₹{{ number_format($loan->recoveryCase?->total_outstanding ?? 0, 2) }}</td></tr>
        <tr><td class="label">Penal Charges (Non-capitalised)</td><td>₹{{ number_format($loan->recoveryCase?->total_penal_charges ?? 0, 2) }}</td></tr>
        <tr><td class="label">Aadhaar Reference</td><td>XXXX-XXXX-{{ $loan->customer->aadhaar_last4 }}</td></tr>
    </table>

    <p>The dishonour of NACH mandate constitutes an offence under <strong>Section 25 of the Payment and Settlement Systems Act, 2007</strong>. You are hereby required to make good the outstanding dues within <strong>15 (Fifteen) days</strong> from the date of receipt of this notice.</p>

    <p>Failure to comply within the stipulated period shall compel us to initiate legal proceedings under applicable laws including Section 25 PSSA, Section 138 of the Negotiable Instruments Act, 1881, and such other civil and criminal remedies as may be available under law.</p>

    <p><strong>Note (RBI Compliance):</strong> As per RBI Microfinance Directions 2022, penal charges of ₹100 + 18% GST per dishonoured mandate have been levied. These charges have NOT been capitalised into the principal outstanding and shall NOT attract compound interest.</p>
</div>

<div class="footer">
    <p>This notice is issued without prejudice to the rights and remedies of IndiaLend Microfinance Private Limited.</p>
</div>

<div class="signature">
    <p>Yours faithfully,</p>
    <br><br>
    <p>___________________________</p>
    <p><strong>Authorised Signatory</strong></p>
    <p>IndiaLend Microfinance Private Limited</p>
    <p>Guwahati, Assam</p>
</div>

<div style="margin-top:20px;text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px;background:#1e3a8a;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">🖨️ Print / Save as PDF</button>
</div>

</body>
</html>
