<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');

$statusBadge = match ($service['status']) {
    'active'     => ['✅ Aktif',     '#059669', '#d1fae5'],
    'pending'    => ['⏳ Kuruluyor', '#d97706', '#fef3c7'],
    'suspended'  => ['⏸ Askıda',    '#dc2626', '#fee2e2'],
    'terminated' => ['❌ Kapatıldı',  '#6b7280', '#f3f4f6'],
    default      => [$service['status'], '#6b7280', '#f3f4f6'],
};

$panelUrl = null;
if ($service['server_hostname'] && $service['server_panel']) {
    $port = match ($service['server_panel']) {
        'cpanel' => 2083,
        'da'     => 2222,
        'plesk'  => 8443,
        default  => null,
    };
    if ($port) $panelUrl = 'https://' . $service['server_hostname'] . ':' . $port;
}
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <div style="margin-bottom:16px">
                    <a href="/panel/hizmetlerim" style="color:var(--aho-color-ink-600);text-decoration:none;font-size:13px">← Hizmetlerim</a>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:24px">
                    <div>
                        <h1 style="margin:0 0 6px;font-size:24px;display:flex;gap:12px;align-items:center">
                            🖥️ <?= e($service['domain']) ?>
                            <span style="padding:4px 12px;font-size:12px;border-radius:12px;color:<?= $statusBadge[1] ?>;background:<?= $statusBadge[2] ?>">
                                <?= e($statusBadge[0]) ?>
                            </span>
                        </h1>
                        <p style="color:var(--aho-color-ink-600);margin:0"><?= e($service['product_name'] ?? $service['package']) ?></p>
                    </div>
                    <?php if (($panelUrl && $service['status'] === 'active') || (!empty($service['username']) && $service['server_hostname'])): ?>
                        <div style="display:flex;gap:8px">
                            <?php if ($panelUrl && $service['status'] === 'active'): ?>
                                <a href="<?= e($panelUrl) ?>" target="_blank" class="aho-btn aho-btn--primary">
                                    🔗 Kontrol Paneli
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($service['username']) && $service['server_hostname']): ?>
                                <a href="ftp://<?= e(rawurlencode($service['username'])) ?>@<?= e($service['server_hostname']) ?>" class="aho-btn aho-btn--outline">
                                    📁 FTP ile Bağlan
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 🔐 Giriş Bilgileri Kartı (Rapor 6.2) -->
                <?php if (!empty($service['username']) || !empty($service['password_encrypted'])): ?>
                <div class="aho-card" style="padding:20px;margin-bottom:24px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-left:4px solid #0ea5e9">
                    <h3 style="margin:0 0 12px;font-size:15px;display:flex;align-items:center;gap:8px">
                        🔐 Kontrol Paneli Giriş Bilgileri
                    </h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <div style="font-size:11px;color:#6b7280">KULLANICI ADI</div>
                            <div style="display:flex;gap:6px;align-items:center;margin-top:4px">
                                <code style="background:#fff;padding:6px 10px;border-radius:4px;flex:1;font-family:monospace;font-size:14px" id="aho-svc-user"><?= e($service['username'] ?? '—') ?></code>
                                <button type="button" onclick="ahoCopy('aho-svc-user', this)" class="aho-btn aho-btn--sm aho-btn--outline" title="Kopyala">📋</button>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#6b7280">ŞİFRE</div>
                            <div style="display:flex;gap:6px;align-items:center;margin-top:4px">
                                <code style="background:#fff;padding:6px 10px;border-radius:4px;flex:1;font-family:monospace;font-size:14px" id="aho-svc-pwd">••••••••••</code>
                                <button type="button" onclick="ahoRevealPassword(<?= (int)$service['id'] ?>, this)" class="aho-btn aho-btn--sm aho-btn--outline" title="Göster">👁</button>
                                <button type="button" onclick="ahoCopy('aho-svc-pwd', this)" class="aho-btn aho-btn--sm aho-btn--outline" title="Kopyala">📋</button>
                            </div>
                        </div>
                    </div>
                    <p style="margin:12px 0 0;font-size:12px;color:#0c4a6e">
                        💡 Şifreyi ilk defa göreceksin — güvenli bir yere kaydet. Değiştirmek için destek talebi oluştur.
                    </p>
                    <p style="margin:6px 0 0;font-size:12px;color:#0c4a6e">
                        📁 Bu kullanıcı adı/şifre aynı zamanda FTP giriş bilgindir.
                    </p>
                </div>
                <script>
                async function ahoRevealPassword(serviceId, btn) {
                    btn.disabled = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]').content;
                        const res = await fetch('/panel/hizmet/' + serviceId + '/sifre-goster', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: '_csrf=' + encodeURIComponent(csrf),
                        });
                        const data = await res.json();
                        if (data.ok) {
                            document.getElementById('aho-svc-pwd').textContent = data.password;
                            btn.textContent = '🔒';
                            btn.onclick = () => { document.getElementById('aho-svc-pwd').textContent = '••••••••••'; btn.textContent = '👁'; btn.disabled = false; btn.onclick = () => ahoRevealPassword(serviceId, btn); };
                            btn.disabled = false;
                        } else {
                            alert(data.error || 'Şifre alınamadı');
                            btn.disabled = false;
                        }
                    } catch (e) {
                        alert('Hata: ' + e.message);
                        btn.disabled = false;
                    }
                }
                function ahoCopy(id, btn) {
                    const text = document.getElementById(id).textContent;
                    navigator.clipboard.writeText(text).then(() => {
                        const old = btn.textContent; btn.textContent = '✓';
                        setTimeout(() => btn.textContent = old, 1500);
                    });
                }
                </script>
                <?php endif; ?>

                <!-- Bilgi kartları -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:24px">
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">DOMAIN</div>
                        <div style="font-size:15px;font-weight:600;margin-top:4px;font-family:monospace"><?= e($service['domain']) ?></div>
                    </div>
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">KULLANICI ADI</div>
                        <div style="font-size:15px;font-weight:600;margin-top:4px;font-family:monospace"><?= e($service['username'] ?? '—') ?></div>
                    </div>
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">SUNUCU</div>
                        <div style="font-size:15px;font-weight:600;margin-top:4px;font-family:monospace"><?= e($service['server_hostname'] ?? '—') ?></div>
                        <?php if ($service['server_panel']): ?>
                            <div style="font-size:11px;color:var(--aho-color-ink-500);text-transform:uppercase;margin-top:2px"><?= e($service['server_panel']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">SONRAKI YENİLEME</div>
                        <div style="font-size:15px;font-weight:600;margin-top:4px">
                            <?= $service['next_due_date'] ? e(date('d.m.Y', strtotime((string)$service['next_due_date']))) : '—' ?>
                        </div>
                        <?php if ($service['next_due_date']):
                            $days = (int) ((strtotime((string)$service['next_due_date']) - time()) / 86400);
                            if ($days > 0):
                        ?>
                            <div style="font-size:11px;color:<?= $days < 30 ? '#d97706' : 'var(--aho-color-ink-500)' ?>;margin-top:2px"><?= $days ?> gün</div>
                        <?php endif; endif; ?>
                    </div>
                </div>

                <!-- Kullanım -->
                <?php if ($service['disk_usage_mb'] !== null || $service['bandwidth_usage_mb'] !== null): ?>
                <div class="aho-card" style="padding:20px;margin-bottom:16px">
                    <h3 style="margin-top:0;font-size:16px">📊 Kaynak Kullanımı</h3>
                    <?php
                    // Basit görsel bar — quota bilinmiyorsa 5GB varsayılan
                    $diskUsed = (int) ($service['disk_usage_mb'] ?? 0);
                    $bandUsed = (int) ($service['bandwidth_usage_mb'] ?? 0);
                    $diskQuota = 5120; // 5GB default (Faz 6f+ package tablosundan gelecek)
                    $bandQuota = 51200; // 50GB default
                    $diskPct = min(100, (int) round($diskUsed / max(1, $diskQuota) * 100));
                    $bandPct = min(100, (int) round($bandUsed / max(1, $bandQuota) * 100));
                    ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                        <?php if ($service['disk_usage_mb'] !== null): ?>
                            <div>
                                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--aho-color-ink-500);margin-bottom:6px">
                                    <span>💾 DİSK KULLANIMI</span><span><?= $diskPct ?>%</span>
                                </div>
                                <div style="background:#e5e7eb;border-radius:8px;height:10px;overflow:hidden">
                                    <div style="width:<?= $diskPct ?>%;height:100%;background:<?= $diskPct>90?'#dc2626':($diskPct>70?'#d97706':'#059669') ?>;transition:width .5s"></div>
                                </div>
                                <div style="font-size:14px;font-weight:600;margin-top:6px">
                                    <?= number_format($diskUsed, 0, ',', '.') ?> MB
                                    <span style="color:var(--aho-color-ink-500);font-weight:400;font-size:12px"> / <?= number_format($diskQuota, 0, ',', '.') ?> MB</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($service['bandwidth_usage_mb'] !== null): ?>
                            <div>
                                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--aho-color-ink-500);margin-bottom:6px">
                                    <span>🌐 TRAFİK KULLANIMI</span><span><?= $bandPct ?>%</span>
                                </div>
                                <div style="background:#e5e7eb;border-radius:8px;height:10px;overflow:hidden">
                                    <div style="width:<?= $bandPct ?>%;height:100%;background:<?= $bandPct>90?'#dc2626':($bandPct>70?'#d97706':'#0891b2') ?>;transition:width .5s"></div>
                                </div>
                                <div style="font-size:14px;font-weight:600;margin-top:6px">
                                    <?= number_format($bandUsed, 0, ',', '.') ?> MB
                                    <span style="color:var(--aho-color-ink-500);font-weight:400;font-size:12px"> / <?= number_format($bandQuota, 0, ',', '.') ?> MB</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($service['usage_updated_at']): ?>
                        <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:12px">
                            Son güncelleme: <?= e(date('d.m.Y H:i', strtotime((string)$service['usage_updated_at']))) ?>
                            · Kullanım verileri her 6 saatte bir otomatik senkron edilir
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 30 gün kullanım grafiği -->
                <?php if (!empty($snapshots) && count($snapshots) > 1): ?>
                <div class="aho-card" style="padding:20px;margin-bottom:16px">
                    <h3 style="margin-top:0;font-size:16px">📈 Son 30 Gün Trend</h3>
                    <canvas id="usageChart" style="max-height:280px"></canvas>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                    <script>
                    (function() {
                        const labels = <?= json_encode(array_map(fn($s) => date('d.m', strtotime((string)$s['snap_date'])), $snapshots)) ?>;
                        const disk = <?= json_encode(array_map(fn($s) => (int)($s['disk_mb'] ?? 0), $snapshots)) ?>;
                        const band = <?= json_encode(array_map(fn($s) => (int)($s['bandwidth_mb'] ?? 0), $snapshots)) ?>;
                        const ctx = document.getElementById('usageChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [
                                    { label: 'Disk (MB)', data: disk, borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.1)', fill: true, tension: 0.3 },
                                    { label: 'Trafik (MB)', data: band, borderColor: '#0891b2', backgroundColor: 'rgba(8,145,178,.1)', fill: true, tension: 0.3 }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: { legend: { position: 'bottom' } },
                                scales: { y: { beginAtZero: true } }
                            }
                        });
                    })();
                    </script>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Bilgi -->
                <?php if ($service['status'] === 'pending'): ?>
                    <div class="aho-alert" style="padding:16px;background:#fef3c7;border-left:4px solid #d97706;color:#92400e;margin-bottom:16px">
                        ⏳ <strong>Kurulum devam ediyor.</strong> Hesabınız hazırlanıyor. Genellikle birkaç dakika içinde aktif olur.
                        <?php if (!empty($service['notes'])): ?>
                            <div style="font-size:13px;margin-top:8px;opacity:.85"><?= e($service['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($service['status'] === 'suspended'): ?>
                    <div class="aho-alert" style="padding:16px;background:#fee2e2;border-left:4px solid #dc2626;color:#991b1b;margin-bottom:16px">
                        ⏸ <strong>Hizmet askıya alındı.</strong> <?= e($service['notes'] ?? 'Detay için destek ekibimize ulaşın.') ?>
                        <div style="margin-top:8px">
                            <a href="/destek" style="color:#991b1b;font-weight:600">→ Destek talebi oluştur</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Sipariş bilgisi -->
                <?php if ($order): ?>
                <div class="aho-card" style="padding:20px">
                    <h3 style="margin-top:0;font-size:16px">🧾 İlgili Sipariş</h3>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-family:monospace;font-weight:600"><?= e($order['order_number']) ?></div>
                            <div style="font-size:13px;color:var(--aho-color-ink-500)"><?= e(date('d.m.Y', strtotime((string)$order['created_at']))) ?> · <?= number_format((float)$order['total'], 2, ',', '.') ?> ₺</div>
                        </div>
                        <a href="/panel/siparislerim" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Detay →</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
