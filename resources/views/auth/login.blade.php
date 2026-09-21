<!DOCTYPE html>
<html lang="en" style="height: 100%;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — IndiaLend Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-50: #eff6ff;
            --brand-100: #dbeafe;
            --brand-500: #1d4ed8;
            --brand-600: #1e40af;
            --brand-700: #1e3a8a;
            --brand-800: #1e3274;
            --brand-900: #172554;
            --saffron-400: #fb923c;
            --saffron-500: #f97316;
            --saffron-600: #ea580c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            height: 100%;
            margin: 0;
            display: flex;
            background-color: #f8fafc;
        }

        .left-panel {
            display: none;
            width: 50%;
            background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-700) 40%, var(--brand-500) 70%, var(--saffron-500) 100%);
            position: relative;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        @media (min-width: 1024px) {
            .left-panel {
                display: flex;
            }
        }

        .overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.2);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-anim {
            animation: float 6s ease-in-out infinite;
            position: relative;
            z-index: 10;
            text-align: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            background-color: var(--saffron-500);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .logo-box span {
            color: white;
            font-weight: 700;
            font-size: 36px;
        }

        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            padding: 32px;
            width: 100%;
            max-width: 400px;
        }

        .form-input {
            width: 100%;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.2);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--brand-600);
            color: white;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 16px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-submit:hover {
            background-color: var(--brand-700);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .demo-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-top: 24px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <!-- ── Left: Branding Panel ───────────────────────────────────── -->
    <div class="left-panel">
        <div class="overlay"></div>
        <div class="float-anim">
            <div class="logo-box">
                <span>₹</span>
            </div>
            <h1 style="color: white; font-size: 36px; font-weight: 700; margin: 0 0 12px 0;">IndiaLend Pro</h1>
            <p style="color: #bfdbfe; font-size: 18px; max-width: 400px; margin: 0 auto; line-height: 1.5;">
                Small Finance Bank & NBFC-MFI ERP<br>
                <span style="color: #93c5fd; font-size: 14px;">RBI Compliant · Microfinance Directions 2022</span>
            </p>
        </div>
        <div style="position: absolute; bottom: 32px; left: 0; right: 0; text-align: center; z-index: 10;">
            <p style="color: rgba(147, 197, 253, 0.6); font-size: 12px; margin: 0;">Loan Origination · Loan Management · Recovery & Legal</p>
        </div>
    </div>

    <!-- ── Right: Login Form ──────────────────────────────────────── -->
    <div class="right-panel">
        <div style="width: 100%; max-width: 400px;" class="fade-in">

            <!-- Mobile logo -->
            <div style="text-align: center; margin-bottom: 32px; display: block;" class="mobile-logo">
                <style>
                    @media (min-width: 1024px) {
                        .mobile-logo {
                            display: none !important;
                        }
                    }
                </style>
                <div style="width: 56px; height: 56px; border-radius: 12px; background-color: var(--saffron-500); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                    <span style="color: white; font-weight: 700; font-size: 24px;">₹</span>
                </div>
                <h1 style="color: #1e293b; font-size: 24px; font-weight: 700; margin: 0;">IndiaLend Pro</h1>
            </div>

            <div class="login-card">
                <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0;">Welcome back</h2>
                <p style="color: #64748b; font-size: 14px; margin: 0 0 24px 0;">Sign in to your ERP account</p>

                @if ($errors->any())
                <div style="margin-bottom: 16px; padding: 12px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; font-size: 14px; color: #b91c1c; display: flex; align-items: flex-start; gap: 8px;">
                    <svg style="width: 20px; height: 20px; color: #f87171; flex-shrink: 0; margin-top: 2px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span style="margin: 0;">{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div style="margin-bottom: 16px;">
                        <label for="email" style="display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 6px;">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="form-input" placeholder="you@sfb.in">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="password" style="display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 6px;">Password</label>
                        <input type="password" id="password" name="password" required
                            class="form-input" placeholder="••••••••">
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="remember" style="border-radius: 4px; border: 1px solid #cbd5e1; accent-color: var(--brand-600);">
                            <span style="font-size: 14px; color: #475569;">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">
                        Sign In
                    </button>
                </form>
            </div>

            <!-- Demo credentials -->
            <div class="demo-card">
                <p style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0;">
                    🔑 Demo Credentials (pwd: <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: var(--brand-600); font-family: monospace; text-transform: lowercase !important; font-variant: normal;">password</code>)
                </p>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 14px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge" style="background: #f3e8ff; color: #7e22ce;">Admin</span>
                            <code style="color: #475569; font-family: monospace; font-size: 12px;">admin@sfb.in</code>
                        </div>
                        <span style="color: #94a3b8; font-size: 12px;">Full Access</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 14px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge" style="background: #dbeafe; color: #1d4ed8;">Manager</span>
                            <code style="color: #475569; font-family: monospace; font-size: 12px;">manager@sfb.in</code>
                        </div>
                        <span style="color: #94a3b8; font-size: 12px;">Underwriting</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 14px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge" style="background: #d1fae5; color: #047857;">Agent</span>
                            <code style="color: #475569; font-family: monospace; font-size: 12px;">agent@sfb.in</code>
                        </div>
                        <span style="color: #94a3b8; font-size: 12px;">Origination</span>
                    </div>
                </div>
            </div>

            <p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 16px;">IndiaLend Pro v2.0 — RBI Compliant ERP</p>
        </div>
    </div>

</body>

</html>