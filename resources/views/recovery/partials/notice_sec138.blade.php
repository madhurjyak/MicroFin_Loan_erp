<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Legal Notice u/s 138 NI Act — {{ $notice->notice_ref_no }}</title>
<style>
    body { font-family: 'Times New Roman', serif; font-size: 13px; margin: 40px; line-height: 1.6; color: #111; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .notice-title { font-size: 16px; font-weight: bold; text-align: center; margin: 15px 0; text-decoration: underline; }
    .section { margin: 12px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    td, th { border: 1px solid #444; padding: 6px; }
    @media print { button { display: none; } }
</style>
</head>
<body>

<div class="header">
    <p style="font-size:18px;font-weight:bold;">ADVOCATE'S OFFICE</p>
    <p>On behalf of: IndiaLend Microfinance Private Limited</p>
    <p>42, Mahatma Gandhi Road, Guwahati, Assam – 781001</p>
</div>

<div class="notice-title">
    LEGAL NOTICE UNDER SECTION 138 OF THE<br>
    NEGOTIABLE INSTRUMENTS ACT, 1881<br>
    (DISHONOUR OF CHEQUE / NACH MANDATE)
</div>

<div class="section">
    <p><strong>Notice Ref.:</strong> {{ $notice->notice_ref_no }}</p>
    <p><strong>Date:</strong> {{ $notice->dispatch_date->format('d F Y') }}</p>
</div>

<div class="section">
    <p><strong>To,</strong></p>
    <p>{{ $loan->customer->full_name }}</p>
    <p>{{ $loan->customer->address }},</p>
    <p>{{ $loan->customer->district }}, {{ $loan->customer->state }} – {{ $loan->customer->pincode }}</p>
</div>

<div class="section">
    <p><strong>Subject: Demand Notice — Dishonour of Cheque/NACH under Section 138 NI Act — Loan: {{ $loan->loan_account_no }}</strong></p>
</div>

<div class="section">
    <p>Dear {{ $loan->customer->full_name }},</p>

    <p>I am instructed by my client, <strong>IndiaLend Microfinance Private Limited</strong> (hereinafter called "the Payee"), to issue this statutory demand notice against you as follows:</p>

    <p>1. That you had availed a loan facility from my client vide Loan Account No. <strong>{{ $loan->loan_account_no }}</strong>, and in discharge of your legal liability had provided post-dated cheques / NACH mandate for repayment of the said loan.</p>

    <p>2. That the cheque(s) / NACH auto-debit mandate issued by you when presented to your banker was returned dishonoured with the endorsement "Funds Insufficient / Account Closed / Payment Stopped" or similar reason.</p>

    <table>
        <tr><th>Particulars</th><th>Details</th></tr>
        <tr><td>Loan Account No.</td><td>{{ $loan->loan_account_no }}</td></tr>
        <tr><td>Principal Disbursed</td><td>₹{{ number_format($loan->principal_amount, 2) }}</td></tr>
        <tr><td>Disbursement Date</td><td>{{ $loan->disbursement_date->format('d-M-Y') }}</td></tr>
        <tr><td>Total Outstanding (approx.)</td><td>₹{{ number_format($loan->recoveryCase?->total_outstanding ?? 0, 2) }}</td></tr>
        <tr><td>KYC Aadhaar Ref</td><td>XXXX-XXXX-{{ $loan->customer->aadhaar_last4 }}</td></tr>
    </table>

    <p>3. You are hereby called upon to pay the said outstanding amount of <strong>₹{{ number_format($loan->recoveryCase?->total_outstanding ?? 0, 2) }}</strong> (Rupees {{ $loan->recoveryCase?->total_outstanding ?? 0 }} only) together with applicable penal charges within <strong>15 (Fifteen) days</strong> from the receipt of this notice.</p>

    <p>4. If payment is not made within the stipulated period, my client shall be compelled to file a criminal complaint against you under Section 138 read with Section 141 of the Negotiable Instruments Act, 1881, before the competent Magistrate Court having jurisdiction, without any further notice.</p>

    <p><strong>RBI Note:</strong> Penal charges of ₹100 + 18% GST per dishonoured instrument have been levied as a fixed fee. These are NOT compounded and NOT added to the loan principal as per RBI Fair Lending Practices (Penal Charges) Circular, 2023.</p>
</div>

<div style="margin-top:40px;">
    <p>This notice is issued without prejudice.</p>
    <br><br>
    <p>___________________________</p>
    <p><strong>Advocate for the Complainant</strong></p>
    <p>Guwahati, Assam</p>
</div>

<div style="margin-top:20px;text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px;background:#991b1b;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">🖨️ Print / Save as PDF</button>
</div>
</body>
</html>
