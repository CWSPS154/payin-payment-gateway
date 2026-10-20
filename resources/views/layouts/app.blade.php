<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Payin') · Payment Gateway</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #172033; background: #f4f6fa; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        header { background: #14223c; color: #fff; padding: 16px max(24px, calc((100% - 1180px) / 2)); display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        header a { color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; }
        header .brand { font-size: 18px; font-weight: 800; }
        .site-nav { display: flex; align-items: center; flex-wrap: wrap; gap: 18px; }
        .site-nav > a { padding: 8px 0; }
        .site-nav > a.nav-primary { color: #fff; font-weight: 750; }
        .site-nav > a[aria-current="page"] { border-bottom: 2px solid #8db7ff; }
        .site-nav > a.nav-secondary { color: #d3dbea; }
        .site-nav > a.nav-secondary:hover, .site-nav > a.nav-primary:hover { color: #fff; }
        .site-nav form { margin: 0; }
        .site-nav button { padding: 7px 11px; background: #334563; }
        .account-context { display: inline-flex; align-items: center; gap: 9px; padding-left: 16px; border-left: 1px solid #42516a; }
        .account-mark { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 7px; font-size: 14px; font-weight: 800; }
        .account-context.admin .account-mark { color: #d9eaff; background: #284b7a; }
        .account-context.merchant .account-mark { color: #d8ffeb; background: #285947; }
        .account-copy { display: grid; gap: 2px; line-height: 1.15; }
        .account-copy strong { font-size: 13px; }
        .account-copy small { color: #b7c2d3; font-size: 11px; }
        main { max-width: 1180px; margin: 36px auto; padding: 0 24px; }
        h1 { margin: 0 0 8px; font-size: 28px; }
        h2 { margin: 0 0 8px; font-size: 19px; }
        p { line-height: 1.55; }
        .muted { color: #68758a; }
        .landing-page { display: grid; gap: 20px; }
        .landing-hero { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr); align-items: center; gap: 46px; min-height: 350px; padding: 42px 46px; overflow: hidden; border-radius: 15px; background: #14223c; color: #fff; }
        .hero-copy { max-width: 520px; }
        .hero-kicker, .section-kicker, .customer-path-kicker { margin: 0 0 14px; font-size: 12px; font-weight: 700; letter-spacing: .06em; }
        .hero-kicker { color: #a9c9f7; }
        .hero-copy h1 { max-width: 480px; margin: 0; font-size: clamp(36px, 4.2vw, 56px); line-height: 1.04; letter-spacing: -.045em; }
        .hero-copy > p:last-child { max-width: 440px; margin: 18px 0 0; color: #c6d1e2; font-size: 16px; line-height: 1.65; }
        .flow-preview { padding: 22px; border-radius: 12px; color: #172033; background: #fff; box-shadow: 0 18px 45px #080f1d55; }
        .flow-topline { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-bottom: 17px; border-bottom: 1px solid #e8edf4; color: #526078; font-size: 12px; font-weight: 650; }
        .flow-status { display: inline-flex; align-items: center; gap: 6px; color: #25815b; font-size: 11px; }
        .flow-status i { width: 7px; height: 7px; border-radius: 50%; background: #42b883; }
        .flow-step { display: grid; grid-template-columns: 40px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 18px 0 12px; }
        .flow-icon { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 9px; font-size: 13px; font-weight: 800; }
        .buyer-icon { color: #2255a4; background: #e8f0ff; }
        .wallet-icon { color: #1d7652; background: #e5f6ec; }
        .flow-label { display: grid; gap: 4px; }
        .flow-label strong, .flow-amount { font-size: 13px; }
        .flow-label small { color: #738096; font-size: 11px; }
        .flow-amount { white-space: nowrap; }
        .flow-amount.credit { color: #218255; }
        .flow-connector { height: 14px; margin-left: 19px; border-left: 1px dashed #b8c7db; }
        .flow-footer { margin-top: 10px; padding-top: 13px; border-top: 1px solid #e8edf4; color: #738096; font-size: 11px; }
        .landing-lower { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr); gap: 20px; }
        .account-paths { padding: 24px 28px; border: 1px solid #e1e7ef; border-radius: 12px; background: #fff; }
        .section-kicker { color: #2c5b9f; }
        .account-paths h2, .customer-path h2 { margin: 0 0 8px; font-size: 21px; letter-spacing: -.02em; }
        .account-paths > .muted { max-width: 460px; margin: 0 0 16px; font-size: 14px; }
        .account-link { display: grid; grid-template-columns: 36px 1fr auto; align-items: center; gap: 12px; padding: 13px 0; color: #172033; text-decoration: none; border-top: 1px solid #e9edf3; }
        .account-link:hover strong, .register-line a:hover { color: #2255a4; }
        .account-link > span:nth-child(2) { display: grid; gap: 3px; }
        .account-link strong { font-size: 14px; }
        .account-link small { color: #738096; font-size: 12px; }
        .account-link-mark { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 8px; font-size: 13px; font-weight: 800; }
        .admin-mark { color: #2255a4; background: #e8f0ff; }
        .merchant-mark { color: #1d7652; background: #e5f6ec; }
        .link-arrow { color: #8794a8; font-size: 16px; }
        .register-line { margin: 10px 0 0; padding-top: 13px; border-top: 1px solid #e9edf3; color: #738096; font-size: 12px; }
        .register-line a { color: #2255a4; font-weight: 700; text-decoration: none; }
        .text-action { display: inline-block; margin-top: 2px; color: #2255a4; font-size: 14px; font-weight: 700; text-decoration: none; }
        .text-action span { margin-left: 4px; }
        .customer-path { display: flex; flex-direction: column; align-items: flex-start; min-height: 100%; padding: 26px 28px; border-radius: 12px; color: #fff; background: #2255a4; text-decoration: none; }
        .customer-path-kicker { color: #c5d9ff; }
        .customer-path h2 { max-width: 260px; margin-top: 10px; font-size: 26px; line-height: 1.12; }
        .customer-path p { max-width: 300px; margin: 4px 0 24px; color: #dce8fc; font-size: 14px; line-height: 1.55; }
        .customer-path-action { display: inline-flex; align-items: center; gap: 12px; margin-top: auto; padding: 10px 13px; border-radius: 7px; color: #172033; background: #fff; font-size: 13px; font-weight: 750; }
        .customer-path-action span { font-size: 16px; }
        .dashboard-shell > .panel:first-child { margin-top: 0; }
        .dashboard-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .dashboard-heading h1 { margin: 0; }
        .dashboard-heading p { margin: 0 0 6px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .panel { background: #fff; border: 1px solid #e3e8f0; border-radius: 12px; padding: 22px; margin-top: 22px; box-shadow: 0 2px 8px #1720330a; }
        .filters { display: grid; grid-template-columns: repeat(4, minmax(130px, 1fr)) auto; gap: 12px; align-items: end; }
        .filter-actions { display: flex; align-items: center; gap: 12px; white-space: nowrap; }
        .filter-actions a { color: #526078; font-size: 13px; font-weight: 650; text-decoration: none; }
        .filter-actions a:hover { color: #2255a4; text-decoration: underline; }
        .merchant-filters { grid-template-columns: repeat(3, minmax(130px, 1fr)) auto; margin-bottom: 22px; }
        .section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 18px; }
        .section-heading h2 { margin-bottom: 5px; }
        .section-heading p { margin-top: 0; margin-bottom: 0; }
        .merchant-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
        .stat-card { display: grid; gap: 8px; padding: 18px 20px; border: 1px solid #e1e7ef; border-radius: 10px; background: #fff; }
        .stat-card > span { color: #68758a; font-size: 12px; font-weight: 650; }
        .stat-card strong { font-size: 24px; letter-spacing: -.03em; }
        .stat-card small { color: #8490a2; font-size: 11px; }
        .wallet-stat { color: #fff; border-color: #1d3b68; background: #18345e; }
        .wallet-stat > span, .wallet-stat small { color: #c4d5ee; }
        .offer-section { padding: 26px; }
        .offer-form { display: grid; grid-template-columns: minmax(220px, 1.4fr) minmax(150px, .8fr) auto; align-items: end; gap: 14px; }
        .offer-field { display: grid; gap: 6px; }
        .offer-form button { min-height: 40px; padding: 9px 16px; }
        .offer-note { margin: 12px 0 0; color: #738096; font-size: 12px; }
        .offer-note a { color: #2255a4; font-weight: 650; text-decoration: none; }
        .success { padding: 11px 13px; border: 1px solid #b8e4ca; border-radius: 7px; color: #176a3a; background: #edf9f1; font-size: 13px; }
        .error { margin: -7px 0 0; color: #9a2525; font-size: 12px; }
        .balance-note { display: block; margin-top: 3px; color: #738096; font-size: 11px; }
        label { display: grid; gap: 6px; color: #526078; font-size: 13px; }
        input, select { width: 100%; min-height: 40px; padding: 8px 10px; color: inherit; background: #fff; border: 1px solid #ccd5e2; border-radius: 7px; font: inherit; }
        button, .button { display: inline-block; border: 0; border-radius: 7px; padding: 11px 16px; background: #2255a4; color: #fff; font: inherit; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #eaf0f8; color: #284266; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 10px; border-bottom: 1px solid #edf0f5; text-align: left; font-size: 14px; white-space: nowrap; }
        th { color: #657188; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        td a { color: #2255a4; font-weight: 600; text-decoration: none; }
        .badge { display: inline-block; border-radius: 999px; padding: 4px 9px; font-size: 12px; font-weight: 700; background: #fff3d8; color: #8a5a00; }
        .badge.SUCCESS { background: #dcf5e8; color: #176a3a; }
        .badge.FAILED { background: #ffe3e3; color: #9a2525; }
        .details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 28px; }
        .details dt { color: #68758a; font-size: 13px; }
        .details dd { margin: 5px 0 0; font-weight: 600; }
        .pagination { margin-top: 18px; }
        .auth-card { max-width: 520px; margin: 38px auto; }
        .form-stack { display: grid; gap: 16px; margin: 22px 0; }
        .checkbox { display: flex; align-items: center; gap: 9px; }
        .checkbox input { width: auto; min-height: auto; }
        .alert { padding: 12px 14px; color: #8d2222; background: #fff0f0; border: 1px solid #f3caca; border-radius: 8px; margin-top: 18px; }
        .checkout-status { display: flex; align-items: center; gap: 8px; font-weight: 650; }
        .checkout-form { display: grid; gap: 18px; max-width: 620px; margin-top: 24px; }
        .mode-options { display: grid; gap: 8px; margin: 0; padding: 14px; border: 1px solid #dfe6ef; border-radius: 9px; }
        .mode-options legend { padding: 0 5px; color: #526078; font-size: 13px; font-weight: 700; }
        .mode-option { display: flex; align-items: flex-start; gap: 10px; padding: 10px; border-radius: 7px; background: #f7f9fc; cursor: pointer; }
        .mode-option input { width: 16px; min-height: 16px; margin-top: 2px; accent-color: #2255a4; }
        .mode-option span { display: grid; gap: 3px; }
        .mode-option strong { color: #25334a; font-size: 13px; }
        .mode-option small { color: #68758a; font-size: 12px; line-height: 1.4; }
        .notice { padding: 11px 13px; border: 1px solid #c8daf5; border-radius: 7px; color: #244b80; background: #f0f6ff; font-size: 13px; }
        .manual-webhook-pending { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 22px; padding: 18px; border: 1px solid #e6d6a7; border-radius: 9px; background: #fffaf0; }
        .manual-webhook-pending strong { color: #6a4d12; }
        .manual-webhook-pending p { margin: 5px 0 0; color: #786c52; font-size: 13px; }
        @media (max-width: 620px) { .manual-webhook-pending { align-items: flex-start; flex-direction: column; } }
        @media (max-width: 900px) { .landing-hero { grid-template-columns: 1fr; gap: 28px; padding: 34px; } .hero-copy { max-width: 620px; } .flow-preview { max-width: 560px; width: 100%; } .landing-lower { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 800px) { .filters, .merchant-filters { grid-template-columns: repeat(2, 1fr); } .merchant-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } .table-wrap { overflow-x: auto; } }
        @media (max-width: 620px) { .landing-hero { min-height: 0; padding: 28px 22px; } .hero-copy h1 { font-size: 38px; } .flow-preview { padding: 16px; } .flow-step { grid-template-columns: 36px minmax(0, 1fr) auto; gap: 9px; } .flow-amount { font-size: 12px; } .landing-lower { grid-template-columns: 1fr; } .account-paths, .customer-path { padding: 22px; } .merchant-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .offer-form { grid-template-columns: 1fr; } .offer-form button { justify-self: start; } }
        @media (max-width: 520px) { header { align-items: flex-start; flex-direction: column; padding: 16px; } .site-nav { gap: 12px; } main { margin: 24px auto; padding: 0 14px; } .filters, .details { grid-template-columns: 1fr; } .panel { padding: 16px; } }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="{{ route('home') }}">Payin · Payment Gateway</a>
        <nav class="site-nav" aria-label="Main navigation">
            @auth
                @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                    <a class="nav-primary" href="{{ route('admin.payments.index') }}" @if(request()->routeIs('admin.payments.*')) aria-current="page" @endif>Payments</a>
                    <a class="nav-secondary" href="{{ route('customer.offers') }}" @if(request()->routeIs('customer.*')) aria-current="page" @endif>Pay as Customer</a>
                    <span class="account-context admin" aria-label="Signed in as admin">
                        <span class="account-mark" aria-hidden="true">A</span>
                        <span class="account-copy"><strong>Admin</strong><small>{{ auth()->user()->name }}</small></span>
                    </span>
                @elseif (auth()->user()->role === \App\Models\User::ROLE_MERCHANT)
                    <a class="nav-primary" href="{{ route('merchant.dashboard') }}" @if(request()->routeIs('merchant.dashboard')) aria-current="page" @endif>Dashboard</a>
                    <a class="nav-secondary" href="{{ route('customer.offers') }}" @if(request()->routeIs('customer.*')) aria-current="page" @endif>Pay as Customer</a>
                    <span class="account-context merchant" aria-label="Signed in as merchant">
                        <span class="account-mark" aria-hidden="true">M</span>
                        <span class="account-copy"><strong>Merchant</strong><small>{{ auth()->user()->name }}</small></span>
                    </span>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
            @else
                <a class="nav-primary" href="{{ route('admin.login') }}" @if(request()->routeIs('admin.login')) aria-current="page" @endif>Admin Login</a>
                <a class="nav-primary" href="{{ route('merchant.login') }}" @if(request()->routeIs('merchant.login')) aria-current="page" @endif>Merchant Login</a>
                <a class="nav-secondary" href="{{ route('customer.offers') }}" @if(request()->routeIs('customer.*')) aria-current="page" @endif>Pay as Customer</a>
            @endauth
        </nav>
    </header>
    <main>@yield('content')</main>
</body>
</html>
