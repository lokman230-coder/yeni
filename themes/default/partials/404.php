<?php
$path = $path ?? (parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Sayfa Bulunamadi - Ahost Bilisim</title>
    <link rel="stylesheet" href="/themes/default/css/site/theme.css">
    <link rel="stylesheet" href="/themes/default/css/site/forms.css">
    <link rel="stylesheet" href="/themes/default/css/site/cards.css">
    <style>
        body { margin:0;background:linear-gradient(180deg,#f8fafc,#eef6ff);font-family:Inter,Arial,sans-serif;color:#0f172a; }
        .err { min-height:100vh;display:grid;place-items:center;padding:32px; }
        .err__wrap { width:min(980px,100%);display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:center; }
        .err__code { font-size:120px;line-height:.9;font-weight:800;color:#0284c7;letter-spacing:0; }
        .err__title { margin:18px 0 10px;font-size:36px;letter-spacing:0; }
        .err__text { margin:0 0 22px;color:#64748b;font-size:16px;line-height:1.7;max-width:560px; }
        .err__path { display:inline-flex;max-width:100%;padding:8px 12px;border:1px solid #dbeafe;border-radius:8px;background:#fff;color:#0369a1;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
        .err__actions { display:flex;gap:10px;flex-wrap:wrap;margin-top:22px; }
        .err__btn { display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 16px;border-radius:8px;text-decoration:none;font-weight:700;border:1px solid #dbeafe;background:#fff;color:#075985; }
        .err__btn--primary { background:#0284c7;color:#fff;border-color:#0284c7; }
        .err__panel { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;box-shadow:0 20px 60px rgba(15,23,42,.08); }
        .err__panel h2 { margin:0 0 12px;font-size:16px; }
        .err__link { display:block;padding:12px;border-radius:8px;color:#0f172a;text-decoration:none;border:1px solid transparent; }
        .err__link:hover { border-color:#bae6fd;background:#f0f9ff; }
        .err__link small { display:block;margin-top:3px;color:#64748b; }
        @media (max-width:800px){ .err__wrap{grid-template-columns:1fr}.err__code{font-size:86px}.err__title{font-size:28px} }
    </style>
</head>
<body>
<main class="err">
    <div class="err__wrap">
        <section>
            <div class="err__code">404</div>
            <h1 class="err__title">Bu sayfayi bulamadik</h1>
            <p class="err__text">Aradigin sayfa tasinmis, silinmis veya link hatali olabilir. Asagidaki kisayollardan devam edebilirsin.</p>
            <span class="err__path"><?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?></span>
            <div class="err__actions">
                <a class="err__btn err__btn--primary" href="/">Ana Sayfa</a>
                <a class="err__btn" href="/destek">Destek Al</a>
                <a class="err__btn" href="/bilgi-bankasi">Bilgi Bankasi</a>
            </div>
        </section>
        <aside class="err__panel">
            <h2>Hizli gecis</h2>
            <a class="err__link" href="/hosting">Hosting <small>Paketleri incele</small></a>
            <a class="err__link" href="/domain-transfer">Domain Transfer <small>Alan adini tasimaya basla</small></a>
            <a class="err__link" href="/site-builder">Site Builder <small>Demo olustur</small></a>
            <a class="err__link" href="/marketplace">Marketplace <small>Eklenti ve temalar</small></a>
        </aside>
    </div>
</main>
</body>
</html>
