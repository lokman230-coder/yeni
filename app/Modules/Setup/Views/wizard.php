<?php
/** @var \App\Core\View $view */
$view->extend('layouts.blank');
$view->section('content');
$step = $step ?? 1;
?>
<div style="min-height:100vh;background:linear-gradient(135deg,#0ea5e9 0%,#8b5cf6 100%);padding:40px 20px;font-family:system-ui,-apple-system,'Segoe UI',sans-serif">
    <div style="max-width:720px;margin:0 auto">

        <div style="text-align:center;color:#fff;margin-bottom:32px">
            <div style="font-size:48px">🚀</div>
            <h1 style="margin:8px 0 4px;font-size:28px">Ahost Bilişim Kurulumu</h1>
            <p style="opacity:.9;margin:0">İlk kurulum sihirbazı — 5 kolay adımda hazır.</p>
        </div>

        <!-- Adım göstergesi -->
        <div style="display:flex;gap:8px;margin-bottom:24px;background:rgba(255,255,255,.15);padding:12px;border-radius:12px;justify-content:center">
            <?php for ($i = 1; $i <= 5; $i++):
                $done = $i < $step;
                $current = $i === $step;
            ?>
                <div style="display:flex;align-items:center;gap:8px;color:#fff;<?= $current ? 'font-weight:700' : ($done ? 'opacity:.7' : 'opacity:.4') ?>">
                    <span style="width:28px;height:28px;border-radius:50%;background:<?= $done ? '#10b981' : ($current ? '#fff' : 'rgba(255,255,255,.2)') ?>;color:<?= $current ? '#0ea5e9' : '#fff' ?>;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">
                        <?= $done ? '✓' : $i ?>
                    </span>
                    <span style="font-size:13px">
                        <?= match ($i) {
                            1 => 'Sistem',
                            2 => 'Veritabanı',
                            3 => 'Migration',
                            4 => 'Admin',
                            5 => 'Site',
                        } ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>

        <div style="background:#fff;border-radius:16px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.25)">

            <?php if (!empty($flash_error)): ?>
                <div style="padding:12px 16px;background:#fee2e2;color:#991b1b;border-radius:8px;margin-bottom:16px;font-size:14px"><?= e($flash_error) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash_success)): ?>
                <div style="padding:12px 16px;background:#d1fae5;color:#065f46;border-radius:8px;margin-bottom:16px;font-size:14px"><?= e($flash_success) ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <h2 style="margin-top:0;font-size:20px">🔍 Sistem Gereksinimleri</h2>
                <p style="color:#6b7280;font-size:14px;margin:4px 0 20px">Kurulum için gerekli PHP eklentileri ve yazma izinleri.</p>

                <table style="width:100%;border-collapse:collapse;font-size:14px">
                    <?php $allOk = true; foreach ($checks as $key => $c):
                        if ($c['required'] && !$c['ok']) $allOk = false;
                    ?>
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:10px 0"><?= e($c['label']) ?></td>
                            <td style="padding:10px 0;text-align:right;color:<?= $c['ok'] ? '#059669' : ($c['required'] ? '#dc2626' : '#d97706') ?>;font-weight:600">
                                <?= e($c['value']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <div style="display:flex;justify-content:flex-end;margin-top:24px">
                    <?php if ($allOk): ?>
                        <a href="/kurulum/adim/2" style="padding:12px 24px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:8px;font-weight:600">Devam Et →</a>
                    <?php else: ?>
                        <div style="color:#dc2626;font-weight:600;font-size:14px">Devam edebilmek için eksik gereksinimleri giderin.</div>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <h2 style="margin-top:0;font-size:20px">🗄️ Veritabanı Bilgileri</h2>
                <p style="color:#6b7280;font-size:14px;margin:4px 0 20px">MariaDB / MySQL bilgilerinizi girin. Sistem bağlantıyı test edip kurulum config dosyasına yazacak.</p>

                <?php if (!empty($db_test)): ?>
                    <div style="padding:12px;background:<?= $db_test['ok'] ? '#d1fae5' : '#fee2e2' ?>;color:<?= $db_test['ok'] ? '#065f46' : '#991b1b' ?>;border-radius:8px;margin-bottom:16px;font-size:14px">
                        <?= e($db_test['msg']) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/kurulum/db">
                    <?= csrf() ?>
                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:12px">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Host</label>
                            <input type="text" name="db_host" value="<?= e($env['DB_HOST'] ?: '127.0.0.1') ?>" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Port</label>
                            <input type="number" name="db_port" value="<?= e($env['DB_PORT'] ?: '3306') ?>" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Veritabanı adı</label>
                        <input type="text" name="db_database" value="<?= e($env['DB_DATABASE']) ?>" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kullanıcı</label>
                            <input type="text" name="db_username" value="<?= e($env['DB_USERNAME']) ?>" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Şifre</label>
                            <input type="password" name="db_password" value="<?= e($env['DB_PASSWORD']) ?>" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                    </div>
                    <div style="border:1px solid #fecaca;background:#fff7f7;border-radius:10px;padding:14px;margin-bottom:20px">
                        <label style="display:flex;gap:10px;align-items:flex-start;font-size:14px;font-weight:700;color:#991b1b">
                            <input type="checkbox" name="fresh_install" value="1" style="margin-top:3px">
                            <span>Temiz / sıfır kurulum yap ve bu veritabanındaki tüm tabloları sil</span>
                        </label>
                        <p style="margin:8px 0 10px;color:#7f1d1d;font-size:13px;line-height:1.5">Bu seçenek işaretlenirse admin oluşturma adımında seçilen veritabanındaki bütün tablolar silinir, migration ve seed işlemleri en baştan çalışır. Canlı veri varsa geri alınamaz.</p>
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#7f1d1d">Onay için <strong>SIFIR KURULUM</strong> yazın</label>
                        <input type="text" name="fresh_confirm" placeholder="SIFIR KURULUM" style="width:100%;padding:10px;border:1px solid #fecaca;border-radius:8px;box-sizing:border-box">
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <a href="/kurulum/adim/1" style="color:#6b7280;text-decoration:none;padding:12px 0">← Geri</a>
                        <button type="submit" style="padding:12px 24px;background:#0ea5e9;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">Test Et & Devam →</button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <h2 style="margin-top:0;font-size:20px">📋 Veritabanı Tablolarını Kur</h2>
                <p style="color:#6b7280;font-size:14px;margin:4px 0 20px">Tüm migration'lar çalıştırılacak (52 dosya). Bu işlem birkaç saniye sürer.</p>

                <?php if (!empty($migrations)): ?>
                    <div style="padding:12px;background:<?= $migrations['ok'] ? '#d1fae5' : '#fee2e2' ?>;color:<?= $migrations['ok'] ? '#065f46' : '#991b1b' ?>;border-radius:8px;margin-bottom:12px;font-size:14px">
                        <?= $migrations['ok'] ? '✓ Tüm migration\'lar başarıyla çalıştırıldı.' : '✗ Migration hatası' ?>
                    </div>
                    <pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;font-size:12px;max-height:280px;overflow:auto;margin-bottom:16px"><?= e($migrations['log']) ?></pre>
                <?php endif; ?>

                <form method="post" action="/kurulum/migrate">
                    <?= csrf() ?>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <a href="/kurulum/adim/2" style="color:#6b7280;text-decoration:none">← Geri</a>
                        <div style="display:flex;gap:12px">
                            <button type="submit" style="padding:12px 20px;background:<?= !empty($migrations['ok']) ? '#f3f4f6' : '#0ea5e9' ?>;color:<?= !empty($migrations['ok']) ? '#374151' : '#fff' ?>;border:0;border-radius:8px;font-weight:600;cursor:pointer">
                                <?= !empty($migrations['ok']) ? '↻ Tekrar Çalıştır' : '▶ Migration Çalıştır' ?>
                            </button>
                            <?php if (!empty($migrations['ok'])): ?>
                                <a href="/kurulum/adim/4" style="padding:12px 20px;background:#059669;color:#fff;text-decoration:none;border-radius:8px;font-weight:600">Devam →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div style="display:flex;align-items:center;gap:12px;margin:24px 0">
                    <div style="flex:1;height:1px;background:#e5e7eb"></div>
                    <span style="color:#9ca3af;font-size:13px">veya</span>
                    <div style="flex:1;height:1px;background:#e5e7eb"></div>
                </div>

                <div style="padding:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px">
                    <h3 style="margin:0 0 6px;font-size:15px">📦 Hazır SQL Dosyası Yükle</h3>
                    <p style="color:#6b7280;font-size:13px;margin:0 0 12px">
                        Elinde zaten hazır bir veritabanı yedeği (.sql) varsa, migration çalıştırmak yerine
                        doğrudan onu yükleyebilirsin. .sql dosyasını .zip içine koyup da yükleyebilirsin.
                        Büyük dosyalarda sunucunun yükleme boyutu limitine (php.ini → upload_max_filesize) takılabilir.
                    </p>
                    <form method="post" action="/kurulum/sql-yukle" enctype="multipart/form-data" onsubmit="return confirm('Yüklenecek SQL dosyasındaki komutlar veritabanında çalıştırılacak. Emin misin?')">
                        <?= csrf() ?>
                        <div style="display:flex;gap:10px;align-items:center">
                            <input type="file" name="sql_file" accept=".sql,.zip" required style="flex:1;padding:8px;border:1px solid #e5e7eb;border-radius:8px;background:#fff">
                            <button type="submit" style="padding:10px 18px;background:#374151;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer;white-space:nowrap">⬆ Yükle ve Çalıştır</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($step === 4): ?>
                <h2 style="margin-top:0;font-size:20px">👤 Süper Admin Oluştur</h2>
                <p style="color:#6b7280;font-size:14px;margin:4px 0 20px">Yönetici paneline erişecek ilk kullanıcı. Şifrenizi kesinlikle güvenli bir yerde saklayın.</p>

                <form method="post" action="/kurulum/admin">
                    <?= csrf() ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Ad</label>
                            <input type="text" name="first_name" value="Süper" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Soyad</label>
                            <input type="text" name="last_name" value="Admin" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">E-posta *</label>
                        <input type="email" name="email" required placeholder="admin@ahost.web.tr" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                    </div>
                    <div style="margin-bottom:20px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Şifre * <span style="color:#6b7280;font-weight:400">(en az 8 karakter)</span></label>
                        <input type="password" name="password" required minlength="8" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <a href="/kurulum/adim/3" style="color:#6b7280;text-decoration:none;padding:12px 0">← Geri</a>
                        <button type="submit" style="padding:12px 24px;background:#0ea5e9;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">Admin Oluştur →</button>
                    </div>
                </form>

            <?php elseif ($step === 5): ?>
                <h2 style="margin-top:0;font-size:20px">🌐 Site Bilgileri</h2>
                <p style="color:#6b7280;font-size:14px;margin:4px 0 20px">Marka bilgileri ve (opsiyonel) SMTP ayarları.</p>

                <form method="post" action="/kurulum/site">
                    <?= csrf() ?>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Site Adı</label>
                        <input type="text" name="app_name" value="<?= e($env['APP_NAME'] ?: 'Ahost Bilişim') ?>" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                    </div>
                    <div style="margin-bottom:20px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Site URL (https zorunlu)</label>
                        <?php
                        $aoDetectedScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $aoDetectedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $aoDetectedBase = function_exists('app_base_path') ? app_base_path() : '';
                        $aoDetectedUrl = $aoDetectedScheme . '://' . $aoDetectedHost . $aoDetectedBase;
                        ?>
                        <input type="url" name="app_url" value="<?= e($env['APP_URL'] ?: $aoDetectedUrl) ?>" required style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                    </div>

                    <details style="margin-bottom:20px">
                        <summary style="cursor:pointer;font-weight:600;color:#0ea5e9;font-size:14px">✉️ SMTP Ayarları (opsiyonel — sonra da girebilirsiniz)</summary>
                        <div style="margin-top:12px;display:grid;grid-template-columns:2fr 1fr;gap:12px">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Host</label>
                                <input type="text" name="mail_host" value="<?= e($env['MAIL_HOST']) ?>" placeholder="smtp.gmail.com" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Port</label>
                                <input type="number" name="mail_port" placeholder="587" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                            </div>
                        </div>
                        <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kullanıcı</label>
                                <input type="text" name="mail_username" value="<?= e($env['MAIL_USERNAME']) ?>" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Şifre</label>
                                <input type="password" name="mail_password" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                            </div>
                        </div>
                        <div style="margin-top:12px">
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Gönderen E-posta</label>
                            <input type="email" name="mail_from_address" value="<?= e($env['MAIL_FROM_ADDRESS']) ?>" placeholder="noreply@ahost.web.tr" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box">
                        </div>
                    </details>

                    <div style="display:flex;justify-content:space-between">
                        <a href="/kurulum/adim/4" style="color:#6b7280;text-decoration:none;padding:12px 0">← Geri</a>
                        <button type="submit" style="padding:12px 24px;background:#059669;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">🎉 Kurulumu Tamamla</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div style="text-align:center;color:rgba(255,255,255,.7);font-size:12px;margin-top:24px">
            Ahost Bilişim · Modern PHP Hosting Yönetim Platformu · v1.0.0
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
