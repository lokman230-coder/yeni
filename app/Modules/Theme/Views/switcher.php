<?php
$themes = \App\Services\Theme\ThemeManager::all();
$active = \App\Services\Theme\ThemeManager::active();
?>
<div class="aho-theme-switch" id="ahoThemeSwitch">
    <button type="button" class="aho-theme-switch__toggle" aria-label="Tema seç" title="Tema seç">
        🎨
    </button>
    <div class="aho-theme-switch__panel" role="dialog" aria-label="Tema seçici">
        <div class="aho-theme-switch__header">
            <strong>Tema</strong>
            <button type="button" class="aho-theme-switch__close" aria-label="Kapat">×</button>
        </div>
        <div class="aho-theme-switch__list">
            <?php foreach ($themes as $slug => $t): ?>
                <form method="post" action="/tema/degistir" class="aho-theme-swatch <?= $slug === $active ? 'is-active' : '' ?>">
                    <?= csrf() ?>
                    <input type="hidden" name="theme" value="<?= e($slug) ?>">
                    <button type="submit" class="aho-theme-swatch__btn" data-theme-preview="<?= e($slug) ?>">
                        <span class="aho-theme-swatch__colors">
                            <span style="background: <?= e($t['colors']['primary'] ?? '#0284c7') ?>"></span>
                            <span style="background: <?= e($t['colors']['accent']  ?? '#06b6d4') ?>"></span>
                            <span style="background: <?= e($t['colors']['background'] ?? '#ffffff') ?>; border:1px solid rgba(0,0,0,.1)"></span>
                        </span>
                        <span class="aho-theme-swatch__info">
                            <span class="aho-theme-swatch__name">
                                <?= e($t['name']) ?>
                                <?php if (!empty($t['is_premium'])): ?>
                                    <span class="aho-theme-swatch__pro">PRO</span>
                                <?php endif; ?>
                                <?php if ($slug === $active): ?>
                                    <span class="aho-theme-swatch__active">✓ AKTİF</span>
                                <?php endif; ?>
                            </span>
                            <span class="aho-theme-swatch__desc"><?= e($t['description']) ?></span>
                        </span>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
        <div class="aho-theme-switch__footer">
            <div class="aho-theme-switch__mode">
                <span>Görünüm:</span>
                <button type="button" onclick="AhostOne.theme.set('light')">☀️ Açık</button>
                <button type="button" onclick="AhostOne.theme.set('dark')">🌙 Koyu</button>
            </div>
        </div>
    </div>
</div>
