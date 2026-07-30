<?php
$path = $path ?? (parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$message = $message ?? 'Not Found';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Admin Sayfa Bulunamadi</title>
    <link rel="stylesheet" href="/themes/default/css/site/theme.css">
    <link rel="stylesheet" href="/themes/default/css/admin/theme.css">
    <link rel="stylesheet" href="/themes/default/css/admin/sidebar.css">
    <link rel="stylesheet" href="/themes/default/css/admin/topbar.css">
    <style>
        body { margin:0;background:#f8fafc;font-family:Inter,Arial,sans-serif;color:#0f172a; }
        .admin404 { min-height:100vh;display:grid;grid-template-columns:280px 1fr; }
        .admin404__side { background:#0f172a;color:#cbd5e1;padding:22px; }
        .admin404__brand { display:flex;align-items:center;gap:10px;color:#fff;text-decoration:none;font-weight:800;font-size:18px; }
        .admin404__nav { margin-top:28px;display:grid;gap:8px; }
        .admin404__nav a { color:#cbd5e1;text-decoration:none;padding:10px 12px;border-radius:8px; }
        .admin404__nav a:hover { background:rgba(255,255,255,.08);color:#fff; }
        .admin404__main { display:grid;place-items:center;padding:32px; }
        .admin404__card { width:min(780px,100%);background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:30px;box-shadow:0 20px 60px rgba(15,23,42,.08); }
        .admin404__eyebrow { color:#0284c7;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.08em; }
        .admin404__code { margin:10px 0 0;font-size:88px;line-height:1;font-weight:900;color:#0f172a;letter-spacing:0; }
        .admin404__title { margin:12px 0 8px;font-size:28px;letter-spacing:0; }
        .admin404__text { margin:0;color:#64748b;line-height:1.7; }
        .admin404__path { display:inline-flex;margin-top:18px;max-width:100%;padding:8px 12px;border-radius:8px;background:#f1f5f9;color:#334155;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
        .admin404__actions { margin-top:24px;display:flex;gap:10px;flex-wrap:wrap; }
        .admin404__btn { min-height:42px;padding:0 16px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;text-decoration:none;font-weight:800;cursor:pointer; }
        .admin404__btn--primary { background:#0284c7;border-color:#0284c7;color:#fff; }
        @media (max-width:900px){ .admin404{grid-template-columns:1fr}.admin404__side{display:none}.admin404__code{font-size:68px} }
    </style>
</head>
<body>
<div class="admin404">
    <aside class="admin404__side">
        <a class="admin404__brand" href="/admin">Ahost <span>Admin</span></a>
        <nav class="admin404__nav">
            <a href="/admin">Kontrol Paneli</a>
            <a href="/admin/urun-merkezi">Urun Merkezi</a>
            <a href="/admin/musteriler">Musteriler</a>
            <a href="/admin/ayarlar">Ayarlar</a>
        </nav>
    </aside>
    <main class="admin404__main">
        <section class="admin404__card">
            <div class="admin404__eyebrow">Admin panel</div>
            <div class="admin404__code">404</div>
            <h1 class="admin404__title">Bu admin sayfasi bulunamadi</h1>
            <p class="admin404__text">Link degismis, kayit silinmis veya yetkili oldugun alan disinda bir URL acilmis olabilir. Site ana sayfasina yonlendirmek yerine admin akisi icinde tutuyoruz.</p>
            <span class="admin404__path"><?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?></span>
            <div class="admin404__actions">
                <button class="admin404__btn admin404__btn--primary" type="button" onclick="if (history.length > 1) history.back(); else location.href='/admin';">Onceki Sayfaya Don</button>
                <a class="admin404__btn" href="/admin">Admin Panele Don</a>
                <a class="admin404__btn" href="/admin/api/arama?q=<?= urlencode(basename($path)) ?>">Aramada Kontrol Et</a>
            </div>
        </section>
    </main>
</div>
</body>
</html>
