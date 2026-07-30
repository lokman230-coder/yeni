<?php
$submitted = false;
$quotationNumber = '';
$errors = [];

$serviceTypes = [
    'website' => ['label' => 'Web Sitesi', 'icon' => 'WEB'],
    'mobile_app' => ['label' => 'Mobil Uygulama', 'icon' => 'APP'],
    'web_app' => ['label' => 'Web Uygulaması', 'icon' => 'SaaS'],
    'custom_software' => ['label' => 'Özel Yazılım', 'icon' => 'DEV'],
    'other' => ['label' => 'Diğer', 'icon' => 'PRO'],
];

$urgencyLevels = [
    'low' => 'Düşük',
    'normal' => 'Normal',
    'high' => 'Yüksek',
    'urgent' => 'Acil',
];

$budgetOptions = [
    '' => 'Seçiniz',
    '10000-25000' => '10.000 TL - 25.000 TL',
    '25000-50000' => '25.000 TL - 50.000 TL',
    '50000-100000' => '50.000 TL - 100.000 TL',
    '100000-250000' => '100.000 TL - 250.000 TL',
    '250000-500000' => '250.000 TL - 500.000 TL',
    '500000+' => '500.000 TL+',
    'belirtilmedi' => 'Henüz belirtilmedi',
];

$timelineOptions = [
    '' => 'Seçiniz',
    '1-2-ay' => '1-2 Ay',
    '2-4-ay' => '2-4 Ay',
    '4-6-ay' => '4-6 Ay',
    '6-12-ay' => '6-12 Ay',
    '12+' => '12+ Ay',
    'belirtilmedi' => 'Henüz belirtilmedi',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $company = trim((string)($_POST['company'] ?? ''));
    $serviceType = (string)($_POST['service_type'] ?? 'website');
    $projectName = trim((string)($_POST['project_name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $budget = (string)($_POST['budget'] ?? '');
    $timeline = (string)($_POST['timeline'] ?? '');
    $urgency = (string)($_POST['urgency'] ?? 'normal');

    if ($name === '') $errors[] = 'Ad soyad gerekli.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta gerekli.';
    if ($projectName === '') $errors[] = 'Proje adı gerekli.';
    if (!isset($serviceTypes[$serviceType])) $serviceType = 'website';
    if (!isset($urgencyLevels[$urgency])) $urgency = 'normal';

    if (!$errors) {
        try {
            $db = function_exists('db') ? db() : get_db();
            $quotationNumber = 'TQ-' . date('Ymd') . '-' . strtoupper(substr(md5((string)microtime(true)), 0, 6));

            $budgetMin = null;
            $budgetMax = null;
            if ($budget !== '' && $budget !== 'belirtilmedi') {
                $budgetParts = explode('-', str_replace(['.', ' ', 'TL', '+'], '', $budget));
                $budgetMin = isset($budgetParts[0]) ? (float)$budgetParts[0] : null;
                $budgetMax = isset($budgetParts[1]) ? (float)$budgetParts[1] : $budgetMin;
            }

            $features = json_encode([
                'budget_range' => $budget,
                'timeline' => $timeline,
                'description' => $description,
            ], JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("
                INSERT INTO quotations (
                    quotation_number, customer_name, customer_email, customer_phone,
                    customer_company, service_type, project_name, project_description,
                    features, budget_min, budget_max, urgency, status, source,
                    referer_url, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'website', ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $quotationNumber,
                $name,
                $email,
                $phone,
                $company,
                $serviceType,
                $projectName,
                $description,
                $features,
                $budgetMin,
                $budgetMax,
                $urgency,
                $_SERVER['HTTP_REFERER'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);

            $submitted = true;
        } catch (Throwable $e) {
            $errors[] = 'Kayıt sırasında hata: ' . $e->getMessage();
        }
    }
}
?>

<section class="ao-site-content ao-quotation-page">
    <div class="ao-content-shell">
        <header class="quotation-header">
            <span class="ao-content-kicker">Teklif Merkezi</span>
            <h1>Projeniz için net ve uygulanabilir teklif alın.</h1>
            <p>Web sitesi, mobil uygulama, web uygulaması veya özel yazılım ihtiyacınızı paylaşın; ekip en kısa sürede kapsam, süre ve maliyet planıyla dönüş yapsın.</p>
        </header>

        <div class="quotation-form-card">
            <?php if ($submitted): ?>
                <div class="success-message">
                    <div class="success-icon">✓</div>
                    <h2>Talebiniz alındı</h2>
                    <p>Teklif talebiniz başarıyla iletildi. En kısa sürede sizinle iletişime geçeceğiz.</p>
                    <div class="quotation-number"><?= e($quotationNumber) ?></div>
                    <p>Bu numarayı görüşmelerde referans olarak kullanabilirsiniz.</p>
                    <a href="<?= e(url('')) ?>" class="submit-btn as-link">Ana Sayfaya Dön</a>
                </div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="error-box">
                        <strong>Lütfen şu alanları kontrol edin:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('teklif')) ?>" class="quotation-form">
                    <section class="form-section">
                        <h3>Hizmet türü</h3>
                        <div class="service-cards">
                            <?php foreach ($serviceTypes as $value => $meta): ?>
                                <label class="service-card">
                                    <input type="radio" name="service_type" value="<?= e($value) ?>" <?= ($_POST['service_type'] ?? 'website') === $value ? 'checked' : '' ?>>
                                    <span class="service-card-icon"><?= e($meta['icon']) ?></span>
                                    <span class="service-card-title"><?= e($meta['label']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="form-section">
                        <h3>Kişisel bilgiler</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Ad Soyad *</label>
                                <input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="Adınız Soyadınız">
                            </div>
                            <div class="form-group">
                                <label for="email">E-posta *</label>
                                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="ornek@email.com">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Telefon</label>
                                <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+90 555 000 00 00">
                            </div>
                            <div class="form-group">
                                <label for="company">Şirket Adı</label>
                                <input type="text" id="company" name="company" value="<?= e($_POST['company'] ?? '') ?>" placeholder="Şirket adı">
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h3>Proje detayları</h3>
                        <div class="form-group">
                            <label for="project_name">Proje Adı *</label>
                            <input type="text" id="project_name" name="project_name" value="<?= e($_POST['project_name'] ?? '') ?>" required placeholder="Örn: E-ticaret sitesi, mobil uygulama">
                        </div>
                        <div class="form-group">
                            <label for="description">Proje Açıklaması</label>
                            <textarea id="description" name="description" placeholder="Projeniz hakkında hedef, özellik ve özel gereksinimleri yazın."><?= e($_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="budget">Tahmini Bütçe</label>
                                <select id="budget" name="budget">
                                    <?php foreach ($budgetOptions as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= ($_POST['budget'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="timeline">Hedef Tamamlanma</label>
                                <select id="timeline" name="timeline">
                                    <?php foreach ($timelineOptions as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= ($_POST['timeline'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h3>Öncelik düzeyi</h3>
                        <div class="urgency-options">
                            <?php foreach ($urgencyLevels as $value => $label): ?>
                                <label class="urgency-option">
                                    <input type="radio" name="urgency" value="<?= e($value) ?>" <?= ($_POST['urgency'] ?? 'normal') === $value ? 'checked' : '' ?>>
                                    <span><?= e(strtoupper(substr($value, 0, 2))) ?></span>
                                    <strong><?= e($label) ?></strong>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="info-note">
                        <strong>Bilgi:</strong> Form gönderildikten sonra ekip 24 saat içinde sizinle iletişime geçer ve proje için net teklif kapsamı hazırlar.
                    </div>

                    <button type="submit" class="submit-btn">Teklif Talebini Gönder</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
