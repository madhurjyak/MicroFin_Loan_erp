<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Loan Recall Notice — {{ $notice->notice_ref_no }}</title>
<style>
    body { font-family: 'Times New Roman', serif; font-size: 13px; margin: 40px; line-height: 1.7; color: #111; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .notice-title { font-size: 15px; font-weight: bold; text-align: center; margin: 15px 0; text-decoration: underline; }
    @media print { button { display: none; } }
</style>
</head>
<body>
<div class="header">
    <p style="font-size:18px;font-weight:bold;">INDIALEND MICROFINANCE PRIVATE LIMITED</p>
    <p>Registered Office: 42, Mahatma Gandhi Road, Guwahati, Assam – 781001</p>
</div>

<div class="notice-title">LOAN RECALL NOTICE</div>

<p><strong>Ref.:</strong> {{ $notice->notice_ref_no }} &nbsp;&nbsp; <strong>Date:</strong> {{ $notice->dispatch_date->format('d F Y') }}</p>

<p><strong>To,</strong><br>
{{ $loan->customer->full_name }}<br>
{{ $loan->customer->address }},<br>
{{ $loan->customer->district }}, {{ $loan->customer->state }} – {{ $loan->customer->pincode }}</p>

<p><strong>Subject: Recall of Loan — Account No. {{ $loan->loan_account_no }}</strong></p>

<p>Dear {{ $loan->customer->full_name }},</p>
<p>This is to inform you that in view of persistent default in repayment of installments under the above referenced loan account (DPD: {{ $loan->recoveryCase?->dpd ?? '90+' }} days), we hereby recall the entire outstanding loan amount of <strong>₹{{ number_format($loan->recoveryCase?->total_outstanding ?? 0, 2) }}</strong> with immediate effect.</p>
<p>You are called upon to pay the said entire outstanding amount within <strong>7 (Seven) days</strong> from the date of this notice, failing which we shall be constrained to take legal action including but not limited to filing proceedings before the District Forum, Lok Adalat, or civil/criminal courts as may be applicable.</p>

<br><br>
<p>___________________________</p>
<p><strong>Branch Manager</strong><br>IndiaLend Microfinance Pvt. Ltd.</p>

<div style="margin-top:20px;text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px;background:#374151;color:white;border:none;border-radius:6px;cursor:pointer;">🖨️ Print / Save as PDF</button>
</div>
</body>
</html>
