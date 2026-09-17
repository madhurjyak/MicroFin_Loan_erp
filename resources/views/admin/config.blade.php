@extends('layouts.app')
@section('title', 'System Configuration')
@section('page-title', '⚙️ RBI Policy Configuration')

@section('content')

<div style="max-width: 800px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-900) 100%); border-radius: var(--border-radius-xl); padding: 24px; margin-bottom: 32px; color: white;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🏛️
            </div>
            <div>
                <h2 style="font-weight: 700; font-size: 18px; margin: 0;">RBI Regulatory Parameters</h2>
                <p style="color: #bfdbfe; font-size: 14px; margin: 4px 0 0 0;">These values are enforced system-wide across LOS, LMS, and DRMS modules.</p>
            </div>
        </div>
        <p style="font-size: 12px; color: #93c5fd; margin: 12px 0 0 0; line-height: 1.5;">
            Based on RBI Master Direction – Regulatory Framework for Microfinance Loans (2022) and Fair Lending Practice Code.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
        @foreach($params as $param)
        <div class="panel" style="margin-bottom: 0;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">{{ $param['icon'] }}</span>
                    <div>
                        <p style="font-weight: 600; color: var(--text-primary); font-size: 14px; margin: 0;">{{ $param['label'] }}</p>
                        <p class="text-muted" style="font-size: 12px; margin: 0;">{{ $param['source'] }}</p>
                    </div>
                </div>
            </div>

            <div style="background: var(--bg-primary); border-radius: 12px; padding: 16px; margin-bottom: 12px;">
                <p style="font-size: 24px; font-weight: 700; color: var(--brand-600); margin: 0;">{{ $param['value'] }}</p>
            </div>

            <p class="text-muted" style="font-size: 12px; line-height: 1.6; margin: 0;">{{ $param['description'] }}</p>
        </div>
        @endforeach
    </div>

    <div style="margin-top: 32px; background: #fefce8; border: 1px solid #fef08a; border-radius: var(--border-radius-xl); padding: 20px;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <span style="font-size: 20px;">💡</span>
            <div>
                <p style="font-weight: 600; color: #854d0e; font-size: 14px; margin: 0;">Configuration Note</p>
                <p style="font-size: 12px; color: #a16207; margin: 4px 0 0 0; line-height: 1.6;">
                    These parameters are currently defined as constants in
                    <code style="background: #fef08a; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px;">app/Services/AmortizationService.php</code>.
                    In a production environment, these would be stored in a <code style="background: #fef08a; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px;">system_configs</code>
                    database table with an admin UI for real-time modification.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(2, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

@endsection
