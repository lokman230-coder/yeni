<?php
$locale = \App\Core\SessionManager::get('locale', 'tr');
$currency = \App\Core\SessionManager::get('currency', 'TRY');

// Aktif kur listesi (admin panelden yönetilen)
$supportedCurrencies = \App\Services\Currency\CurrencyService::supported();
$currencyLabels = [
    'TRY' => 'Türk Lirası', 'USD' => 'US Dollar', 'EUR' => 'Euro',
    'GBP' => 'British Pound', 'JPY' => 'Japanese Yen', 'CHF' => 'Swiss Franc',
];

// Topbar'da gösterilecek referans kur: TRY dışı bir para için "1 X ≈ Y ₺"
$refCurrency = null;
foreach (['USD', 'EUR', 'GBP'] as $c) {
    if (in_array($c, $supportedCurrencies, true)) { $refCurrency = $c; break; }
}
$refRateShown = null;
$refRateRaw = null;
$refMargin = 0.0;
if ($refCurrency) {
    try {
        $row = \App\Core\Database\Connection::selectOne(
            "SELECT rate, margin_percent FROM currency_rates WHERE currency = ?",
            [$refCurrency]
        );
        if ($row) {
            $refRateRaw = (float) $row['rate'];
            $refMargin = (float) $row['margin_percent'];
            $refRateShown = $refRateRaw * (1 + $refMargin / 100);
        }
    } catch (\Throwable) {}
}
?>
<div class="aho-topbar">
    <div class="aho-container aho-topbar__inner">
        <div class="aho-topbar__left">
            <a href="/destek" class="aho-topbar__link">🎧 <?= e(__('common.nav.support')) ?></a>
            <a href="/bilgi-bankasi" class="aho-topbar__link aho-hidden-lg-hide"><?= e(__('common.nav.knowledge')) ?></a>
            <a href="/hakkimizda" class="aho-topbar__link aho-hidden-lg-hide"><?= e(__('common.nav.about')) ?></a>
            <span class="aho-topbar__link aho-hidden-lg-hide">📞 0850 000 00 00</span>
        </div>
        <div class="aho-topbar__right">
            <!-- Kur bilgisi (canlı: TCMB + marj) -->
            <?php if ($refCurrency && $refRateShown): ?>
                <span class="aho-topbar__rate aho-hidden-lg-hide"
                      title="TCMB kuru: <?= number_format($refRateRaw, 4, ',', '.') ?> ₺ + %<?= number_format($refMargin, 2, ',', '.') ?> marj = <?= number_format($refRateShown, 4, ',', '.') ?> ₺">
                    1 <?= e($refCurrency) ?> ≈ <?= number_format($refRateShown, 2, ',', '.') ?> ₺
                </span>
            <?php endif; ?>

            <!-- Para birimi (aktif olanlar) -->
            <div class="aho-topbar__dropdown">
                <button class="aho-topbar__link" data-aho-dropdown-trigger>
                    💰 <?= e($currency) ?> ▾
                </button>
                <div class="aho-topbar__menu" data-aho-dropdown-menu>
                    <?php foreach ($supportedCurrencies as $c):
                        $label = $currencyLabels[$c] ?? $c;
                    ?>
                        <a href="?cur=<?= e($c) ?>" <?= $c === $currency ? 'style="font-weight:600;color:var(--aho-color-primary-600)"' : '' ?>>
                            <?= e($c) ?> - <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dil -->
            <div class="aho-topbar__dropdown">
                <button class="aho-topbar__link" data-aho-dropdown-trigger>
                    🌐 <?= strtoupper((string)$locale) ?> ▾
                </button>
                <div class="aho-topbar__menu" data-aho-dropdown-menu>
                    <a href="?lang=tr">🇹🇷 Türkçe</a>
                    <a href="?lang=en">🇬🇧 English</a>
                </div>
            </div>

            <!-- Giriş -->
            <?php if (\App\Services\Auth\AuthService::isCustomer()): ?>
                <a href="/panel" class="aho-topbar__link"><?= e(__('common.topbar.panel')) ?></a>
            <?php else: ?>
                <a href="/giris" class="aho-topbar__link"><?= e(__('common.topbar.login')) ?></a>
                <a href="/kayit" class="aho-topbar__link aho-topbar__link--accent"><?= e(__('common.topbar.register')) ?></a>
            <?php endif; ?>

            <!-- Sepet -->
            <a href="/sepet" class="aho-topbar__link aho-topbar__cart" aria-label="<?= e(__('common.topbar.cart')) ?>">
                🛒 <span class="aho-topbar__cart-count">0</span>
            </a>

            <!-- Dark mode -->
            <button class="aho-topbar__theme" onclick="AhostOne.theme.toggle()" aria-label="Tema değiştir">🌓</button>
        </div>
    </div>
</div>
