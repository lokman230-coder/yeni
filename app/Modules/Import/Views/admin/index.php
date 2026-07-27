<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🔌 Veri Aktarımı</h1>
            <p>WHMCS, WISECP, Blesta gibi dış panellerden müşteri, sipariş, fatura, domain, hosting ve destek talebi aktarın.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <!-- Kaynak seçimi -->
    <div class="aho-card" style="padding:24px;margin-bottom:20px">
        <h3 style="margin-top:0">📥 Yeni Aktarım Başlat</h3>
        <p style="color:var(--aho-color-ink-600);font-size:14px">Kaynak panel seçin — sonra bağlantı bilgilerini girin, test edin, hangi verileri alacağınızı seçin.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:16px">
            <?php foreach ($sources as $s):
                $meta = match ($s['id']) {
                    'whmcs'  => ['icon'=>'🌐', 'desc'=>'En yaygın hosting yönetim paneli. WHMCS 7.x ve 8.x uyumlu.', 'color'=>'#0891b2'],
                    'wisecp' => ['icon'=>'🇹🇷', 'desc'=>'Türkiye\'nin en yaygın panellerinden. Direkt DB okur.',   'color'=>'#059669'],
                    'blesta' => ['icon'=>'💎', 'desc'=>'Blesta 5.x — dünya çapında güvenilir, açık kaynak.',     'color'=>'#8b5cf6'],
                    default  => ['icon'=>'📦', 'desc'=>'Panel driver',                                          'color'=>'#6b7280'],
                };
            ?>
                <a href="/admin/veri-aktarimi/baglan/<?= e($s['id']) ?>" style="display:block;padding:20px;background:#fff;border:2px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;transition:all .15s">
                    <div style="font-size:32px;line-height:1"><?= $meta['icon'] ?></div>
                    <div style="font-weight:700;font-size:16px;margin-top:8px;color:<?= $meta['color'] ?>"><?= e($s['label']) ?></div>
                    <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px;line-height:1.4"><?= e($meta['desc']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Son işler -->
    <div class="aho-card" style="padding:0;overflow:auto">
        <div style="padding:16px 24px;border-bottom:1px solid var(--aho-color-border)">
            <h3 style="margin:0">📋 Son Aktarım İşleri</h3>
        </div>
        <?php if (empty($jobs)): ?>
            <div style="padding:32px;text-align:center;color:var(--aho-color-ink-500)">Henüz aktarım işi yok.</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:10px 16px">#</th>
                    <th style="padding:10px 16px">Kaynak</th>
                    <th style="padding:10px 16px">Tip</th>
                    <th style="padding:10px 16px;text-align:center">Durum</th>
                    <th style="padding:10px 16px;text-align:right">İlerleme</th>
                    <th style="padding:10px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($jobs as $j):
                $pct = (int)$j['total'] > 0 ? min(100, (int)round(((int)$j['imported']+(int)$j['skipped']) / (int)$j['total'] * 100)) : 0;
                $badge = match ($j['status']) {
                    'pending'   => ['⏳ Bekliyor', '#d97706', '#fef3c7'],
                    'running'   => ['🔄 Çalışıyor','#0891b2', '#e0f2fe'],
                    'completed' => ['✅ Tamam',   '#059669', '#d1fae5'],
                    'failed'    => ['❌ Hata',    '#dc2626', '#fee2e2'],
                    default     => [$j['status'], '#6b7280', '#f3f4f6'],
                };
            ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:10px 16px;font-family:monospace"><?= (int)$j['id'] ?></td>
                    <td style="padding:10px 16px;font-weight:600"><?= e($j['source']) ?></td>
                    <td style="padding:10px 16px"><?= e($j['type']) ?></td>
                    <td style="padding:10px 16px;text-align:center">
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>"><?= e($badge[0]) ?></span>
                    </td>
                    <td style="padding:10px 16px;text-align:right;min-width:180px">
                        <div style="font-size:12px;color:var(--aho-color-ink-600)"><?= (int)$j['imported']+(int)$j['skipped'] ?> / <?= (int)$j['total'] ?></div>
                        <div style="background:#e5e7eb;border-radius:6px;height:6px;overflow:hidden;margin-top:2px">
                            <div style="width:<?= $pct ?>%;height:100%;background:<?= $j['status']==='completed'?'#059669':'#0891b2' ?>"></div>
                        </div>
                        <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:2px">
                            ✓ <?= (int)$j['imported'] ?> · ↷ <?= (int)$j['skipped'] ?><?= (int)$j['errors'] > 0 ? ' · ✗ '.(int)$j['errors'] : '' ?>
                        </div>
                    </td>
                    <td style="padding:10px 16px;text-align:right;white-space:nowrap">
                        <?php if ($j['status'] !== 'completed' && $j['status'] !== 'failed'): ?>
                            <form method="post" action="/admin/veri-aktarimi/is/<?= (int)$j['id'] ?>/calistir" style="display:inline">
                                <?= csrf() ?>
                                <button type="submit" style="padding:5px 10px;background:#0ea5e9;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:11px">▶ Çalıştır</button>
                            </form>
                        <?php endif; ?>
                        <a href="/admin/veri-aktarimi/is/<?= (int)$j['id'] ?>" style="padding:5px 10px;background:#f3f4f6;color:#374151;text-decoration:none;border-radius:6px;font-size:11px">Detay</a>
                        <form method="post" action="/admin/veri-aktarimi/is/<?= (int)$j['id'] ?>/sil" style="display:inline" onsubmit="return confirm('Bu iş kaydı silinsin mi? İthal edilen veriler korunur.')">
                            <?= csrf() ?>
                            <button type="submit" style="background:none;border:0;color:#dc2626;cursor:pointer;font-size:14px">🗑</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="aho-card" style="padding:16px;margin-top:16px;background:#eff6ff;border-left:4px solid #0ea5e9;font-size:13px;color:var(--aho-color-ink-700);line-height:1.6">
        💡 <strong>Nasıl çalışır?</strong>
        <ol style="margin:8px 0 0;padding-left:20px">
            <li>Yukarıdan kaynak panelini seç (WHMCS/WISECP/Blesta)</li>
            <li>MySQL DB bağlantı bilgilerini gir (read-only kullanıcı önerilir)</li>
            <li>"Test Et" ile bağlantıyı doğrula → kayıt sayıları gösterilir</li>
            <li>Hangi verileri almak istediğini seç (müşteri, sipariş, fatura, domain, hosting, ticket)</li>
            <li>"İşleri Oluştur" → her tip için ayrı iş oluşur</li>
            <li>"▶ Çalıştır" ile başlat, büyük tabanlarda "Devam Et" ile sürdür</li>
        </ol>
        <div style="margin-top:8px">🔒 <strong>Duplicate önleme:</strong> Aynı kayıt tekrar aktarılmaz. Email adresi + external ID eşleştirme ile takip edilir.</div>
    </div>
</div>
<?php $view->endSection(); ?>
