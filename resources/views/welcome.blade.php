<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IndiaLend Pro — Core Banking System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-600: #1e40af;
            --brand-700: #1e3a8a;
            --brand-900: #172554;
            --saffron-500: #f97316;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-700) 50%, var(--brand-600) 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .hero {
            text-align: center;
            padding: 40px 20px;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-box {
            width: 96px; height: 96px; border-radius: 20px; background-color: var(--saffron-500);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 32px auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: float 6s ease-in-out infinite;
        }
        .logo-box span { color: white; font-weight: 700; font-size: 48px; }

        @keyframes float { 
            0%, 100% { transform: translateY(0); } 
            50% { transform: translateY(-10px); } 
        }

        h1 { font-size: 48px; font-weight: 700; margin: 0 0 16px 0; letter-spacing: -0.02em; }
        p.subtitle { font-size: 20px; color: #bfdbfe; max-width: 600px; margin: 0 auto 48px auto; line-height: 1.6; }

        .btn {
            display: inline-block;
            background: white;
            color: var(--brand-700);
            font-weight: 600;
            font-size: 16px;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .features {
            display: flex;
            gap: 24px;
            justify-content: center;
            margin-top: 64px;
            flex-wrap: wrap;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 24px;
            border-radius: 16px;
            width: 100%;
            max-width: 280px;
            backdrop-filter: blur(10px);
            text-align: left;
        }

        .feature-card h3 { font-size: 18px; margin: 0 0 8px 0; }
        .feature-card p { font-size: 14px; color: #93c5fd; margin: 0; line-height: 1.5; }

        .footer {
            margin-top: 80px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="hero">
        <div class="logo-box">
            <span>₹</span>
        </div>
        
        <h1>IndiaLend Pro</h1>
        <p class="subtitle">Next-generation core banking system designed specifically for Small Finance Banks and NBFC-MFIs. RBI Compliant out of the box.</p>

        @auth
            <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard →</a>
        @else
            <a href="{{ route('login') }}" class="btn">Login to ERP →</a>
        @endauth

        <div class="features">
            <div class="feature-card">
                <h3>🏦 LOS</h3>
                <p>Fully paperless Loan Origination System with integrated KYC, Bureau pulls, and FOIR analysis.</p>
            </div>
            <div class="feature-card">
                <h3>📒 LMS</h3>
                <p>Robust Loan Management System handling JLG Center meetings, disbursements, and NPA classification.</p>
            </div>
            <div class="feature-card">
                <h3>⚖️ DRMS</h3>
                <p>Delinquency and Recovery Management System with automated statutory notice generation (Sec 138, etc).</p>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} IndiaLend Microfinance Private Limited
        </div>
    </div>

</body>
</html>
