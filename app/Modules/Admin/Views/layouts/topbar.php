<?php
$admin = \App\Services\Auth\AuthService::admin();
$name = $admin['full_name'] ?? 'Yönetici';
?>
<header class="aho-admin-topbar">
    <button class="aho-admin-topbar__menu-btn" onclick="document.getElementById('ahoAdminSidebar').classList.toggle('is-open')" aria-label="Menü">☰</button>

    <div class="aho-admin-topbar__search" style="position:relative">
        <input type="text" id="ahoAdminSearch" placeholder="🔍 Müşteri, sipariş, domain, ürün ara..." autocomplete="off" />
        <div id="ahoAdminSearchResults" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:1000;max-height:420px;overflow:auto;margin-top:6px"></div>
    </div>
    <script>
    (function(){
        const inp = document.getElementById('ahoAdminSearch');
        const box = document.getElementById('ahoAdminSearchResults');
        if (!inp || !box) return;
        let timer = null;
        inp.addEventListener('input', () => {
            clearTimeout(timer);
            const q = inp.value.trim();
            if (q.length < 2) { box.style.display = 'none'; box.innerHTML = ''; return; }
            timer = setTimeout(async () => {
                try {
                    const r = await fetch('/admin/api/arama?q=' + encodeURIComponent(q));
                    const d = await r.json();
                    if (!d.results || !d.results.length) {
                        box.innerHTML = '<div style="padding:16px;color:#6b7280;font-size:13px;text-align:center">Sonuç yok</div>';
                    } else {
                        box.innerHTML = d.results.map(x =>
                            '<a href="' + x.url + '" style="display:flex;gap:12px;padding:10px 14px;border-bottom:1px solid #f3f4f6;text-decoration:none;color:#111;align-items:center">' +
                            '<span style="font-size:20px">' + (x.icon || '📄') + '</span>' +
                            '<div style="flex:1;min-width:0">' +
                            '  <div style="font-weight:600;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (x.title || '') + '</div>' +
                            '  <div style="font-size:11px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (x.subtitle || '') + '</div>' +
                            '</div>' +
                            '<span style="font-size:10px;color:#9ca3af;text-transform:uppercase">' + (x.type || '') + '</span>' +
                            '</a>'
                        ).join('');
                    }
                    box.style.display = 'block';
                } catch (e) {
                    box.style.display = 'none';
                }
            }, 250);
        });
        document.addEventListener('click', (e) => {
            if (!inp.contains(e.target) && !box.contains(e.target)) { box.style.display = 'none'; }
        });
    })();
    </script>

    <div class="aho-admin-topbar__actions">
        <button class="aho-admin-topbar__icon-btn" onclick="AhostOne.theme.toggle()" aria-label="Tema">🌓</button>
        <button class="aho-admin-topbar__icon-btn" aria-label="Bildirimler">🔔</button>

        <div class="aho-topbar__dropdown">
            <button class="aho-admin-topbar__user" data-aho-dropdown-trigger>
                <span class="aho-admin-topbar__avatar"><?= e(mb_substr($name, 0, 1)) ?></span>
                <span class="aho-admin-topbar__user-name"><?= e($name) ?></span>
                <span>▾</span>
            </button>
            <div class="aho-topbar__menu" data-aho-dropdown-menu>
                <a href="/admin/ayarlar">⚙️ Ayarlar</a>
                <a href="/" target="_blank">🌐 Siteyi Görüntüle</a>
                <form action="/admin/cikis" method="post" style="display:block">
                    <?= csrf() ?>
                    <button type="submit" style="width:100%;text-align:left;padding:var(--aho-space-2) var(--aho-space-3);border-radius:var(--aho-radius-sm);color:var(--aho-color-danger);background:transparent">🚪 Çıkış Yap</button>
                </form>
            </div>
        </div>
    </div>
</header>
