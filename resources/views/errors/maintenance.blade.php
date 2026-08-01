<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Sedang dalam pemeliharaan</title>
    <style>
        *{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:#17212b}body{background:#f2f7f5}.page{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 20px;overflow:hidden}.glow{position:absolute;border-radius:999px;filter:blur(70px);pointer-events:none}.glow-a{width:360px;height:360px;left:-130px;top:-90px;background:rgba(153,225,211,.52)}.glow-b{width:360px;height:360px;right:-120px;bottom:-100px;background:rgba(250,211,130,.45)}.shell{position:relative;width:min(1080px,100%);display:grid;grid-template-columns:1.05fr .95fr;overflow:hidden;border:1px solid rgba(255,255,255,.95);border-radius:30px;background:rgba(255,255,255,.94);box-shadow:0 28px 90px rgba(15,88,81,.14)}.content{padding:58px 60px;display:flex;flex-direction:column;justify-content:center}.badge{display:inline-flex;align-items:center;gap:9px;width:max-content;padding:9px 13px;border-radius:999px;background:#fff7e6;color:#a85b08;font-size:11px;font-weight:800;letter-spacing:.13em}.dot{width:8px;height:8px;border-radius:50%;background:#f59e0b;box-shadow:0 0 0 5px rgba(245,158,11,.12)}h1{max-width:540px;margin:24px 0 0;font-size:42px;line-height:1.12;letter-spacing:-.035em}p{margin:0}.message{max-width:570px;margin-top:18px;color:#64748b;font-size:16px;line-height:1.75}.estimate{display:flex;align-items:center;gap:12px;width:max-content;max-width:100%;margin-top:24px;padding:13px 16px;border:1px solid #e2e8e4;border-radius:15px;background:#f8faf9}.estimate svg{width:21px;color:#0f766e;flex:none}.estimate small{display:block;color:#78908c;font-size:11px}.estimate strong{display:block;margin-top:2px;font-size:14px}.actions{display:flex;gap:11px;margin-top:30px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 20px;border:0;border-radius:12px;font:inherit;font-size:14px;font-weight:750;text-decoration:none;cursor:pointer}.primary{background:#0f766e;color:white;box-shadow:0 8px 20px rgba(15,118,110,.2)}.secondary{border:1px solid #dce6e3;background:white;color:#334155}.note{margin-top:18px;color:#7b8d89;font-size:12px;line-height:1.55}.visual{position:relative;min-height:540px;display:flex;align-items:center;justify-content:center;padding:46px;background:linear-gradient(145deg,#e9f7f4 0%,#f1f9f5 52%,#fff7e8 100%)}.visual:before{content:"";position:absolute;width:320px;height:320px;border-radius:50%;background:rgba(190,230,222,.58)}.illustration{position:relative;width:min(360px,100%);filter:drop-shadow(0 20px 24px rgba(15,118,110,.12))}.safe{position:absolute;right:28px;top:28px;display:flex;align-items:center;gap:7px;padding:8px 11px;border-radius:999px;background:rgba(255,255,255,.88);color:#0f766e;font-size:11px;font-weight:750;box-shadow:0 8px 24px rgba(15,118,110,.08)}.safe svg{width:16px}@media(max-width:800px){.page{padding:18px 14px}.shell{grid-template-columns:1fr;border-radius:24px}.content{padding:36px 26px 30px}.visual{min-height:280px;padding:20px}.illustration{width:240px}.safe{top:18px;right:18px}h1{font-size:32px}.actions{flex-direction:column}.button{width:100%}}@media(max-width:420px){.content{padding:30px 20px 25px}h1{font-size:28px}.message{font-size:14px}.visual{min-height:235px}.illustration{width:205px}}
    </style>
</head>
<body>
    <main class="page">
        <span class="glow glow-a"></span><span class="glow glow-b"></span>
        <section class="shell">
            <div class="content">
                <div class="badge"><span class="dot"></span> PEMELIHARAAN SISTEM</div>
                <h1>Layanan sedang kami siapkan kembali</h1>
                <p class="message">{{ $message }}</p>
                @if ($expected_end_at)
                    <div class="estimate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <div><small>Perkiraan selesai</small><strong>{{ \Illuminate\Support\Carbon::parse($expected_end_at)->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</strong></div>
                    </div>
                @endif
                <div class="actions">
                    <button type="button" onclick="window.location.reload()" class="button primary">Coba Lagi</button>
                    <a href="{{ route('maintenance.admin-login') }}" class="button secondary">Login Admin</a>
                </div>
                <p class="note">Transaksi dihentikan sementara untuk menjaga konsistensi dan keamanan data keuangan.</p>
            </div>
            <div class="visual" aria-hidden="true">
                <div class="safe"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 12 4 4 8-8"/><circle cx="12" cy="12" r="9"/></svg> Data tetap aman</div>
                <svg class="illustration" viewBox="0 0 420 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="210" cy="180" r="148" fill="#DDF2EE"/><path d="M91 285c32-45 75-68 129-68 56 0 99 23 129 68" fill="#C9E8E2"/>
                    <rect x="127" y="72" width="166" height="218" rx="34" fill="white" stroke="#B9D9D3" stroke-width="4"/><rect x="148" y="98" width="124" height="138" rx="18" fill="#F2F8F7"/>
                    <circle cx="210" cy="166" r="43" fill="#0F766E"/><path d="M210 136v15m0 30v15m-30-30h15m30 0h15m-8.8-21.2-10.6 10.6m-21.2 21.2-10.6 10.6m0-42.4 10.6 10.6m21.2 21.2 10.6 10.6" stroke="white" stroke-width="9" stroke-linecap="round"/>
                    <circle cx="210" cy="166" r="18" fill="#F4BF4F" stroke="white" stroke-width="6"/><rect x="167" y="252" width="86" height="9" rx="4.5" fill="#B9D9D3"/>
                    <path d="M101 114c-16 12-25 29-27 50m245-50c16 12 25 29 27 50" stroke="#0F766E" stroke-width="8" stroke-linecap="round"/><circle cx="74" cy="181" r="12" fill="#F4BF4F"/><circle cx="346" cy="181" r="12" fill="#F4BF4F"/><path d="M74 213v33m272-33v33" stroke="#0F766E" stroke-width="8" stroke-linecap="round"/>
                </svg>
            </div>
        </section>
    </main>
</body>
</html>
