<header class="aho-hdr" role="banner">
    <div class="aho-container aho-hdr__inner">
        <a href="/" class="aho-hdr__brand" aria-label="Ahost Bilişim Ana Sayfa">
            <img src="<?= asset('img/logo-icon.png') ?>" alt="" width="36" height="36">
            <span class="aho-hdr__brand-text">Ahost <b>Bilişim</b></span>
        </a>

        <nav class="aho-hdr__nav" aria-label="Ana menü">
            <ul class="aho-hdr__menu">
                <?php $headerMenu = site_menu('header'); if (!$headerMenu): $headerMenu = [
                    ['label'=>__('common.nav.hosting'),'url'=>'/hosting'], ['label'=>__('common.nav.vps'),'url'=>'/sunucular'], ['label'=>__('common.nav.domain'),'url'=>'/domain'], ['label'=>__('common.nav.builder'),'url'=>'/site-builder'], ['label'=>__('common.nav.mobile'),'url'=>'/mobile-builder'], ['label'=>__('common.nav.tools'),'url'=>'/site-araclari'], ['label'=>__('common.nav.market'),'url'=>'/marketplace'], ['label'=>__('common.nav.blog'),'url'=>'/blog'], ['label'=>__('common.nav.contact'),'url'=>'/iletisim']
                ]; endif; foreach ($headerMenu as $item): ?><li><a href="<?= e($item['url'] ?? '#') ?>"><?= e($item['label'] ?? '') ?></a></li><?php endforeach; ?>
            </ul>
        </nav>

        <button class="aho-hdr__burger" data-aho-mobile-toggle aria-label="Menüyü aç" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobil menü -->
    <div class="aho-hdr__mobile" data-aho-mobile-menu>
        <nav aria-label="Mobil menü">
            <ul>
                <?php $mobileMenu = site_menu('mobile'); if (!$mobileMenu) $mobileMenu = site_menu('header'); foreach ($mobileMenu as $item): ?><li><a href="<?= e($item['url'] ?? '#') ?>"><?= e($item['label'] ?? '') ?></a></li><?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
