<header class="aho-hdr" role="banner">
    <div class="aho-container aho-hdr__inner">
        <a href="/" class="aho-hdr__brand" aria-label="Ahost Bilişim Ana Sayfa">
            <img src="<?= asset('img/logo-icon.png') ?>" alt="" width="36" height="36">
            <span class="aho-hdr__brand-text">Ahost <b>Bilişim</b></span>
        </a>

        <nav class="aho-hdr__nav" aria-label="Ana menü">
            <ul class="aho-hdr__menu">
                <li><a href="/hosting"><?= e(__('common.nav.hosting')) ?></a></li>
                <li><a href="/sunucular"><?= e(__('common.nav.vps')) ?></a></li>
                <li><a href="/domain"><?= e(__('common.nav.domain')) ?></a></li>
                <li><a href="/site-builder"><?= e(__('common.nav.builder')) ?></a></li>
                <li><a href="/mobile-builder"><?= e(__('common.nav.mobile')) ?></a></li>
                <li><a href="/site-araclari"><?= e(__('common.nav.tools')) ?></a></li>
                <li><a href="/marketplace"><?= e(__('common.nav.market')) ?></a></li>
                <li><a href="/blog"><?= e(__('common.nav.blog')) ?></a></li>
                <li><a href="/iletisim"><?= e(__('common.nav.contact')) ?></a></li>
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
                <li><a href="/hosting"><?= e(__('common.nav.hosting')) ?></a></li>
                <li><a href="/sunucular"><?= e(__('common.nav.vps')) ?></a></li>
                <li><a href="/domain"><?= e(__('common.nav.domain')) ?></a></li>
                <li><a href="/site-builder"><?= e(__('common.nav.builder')) ?></a></li>
                <li><a href="/mobile-builder"><?= e(__('common.nav.mobile')) ?></a></li>
                <li><a href="/site-araclari"><?= e(__('common.nav.tools')) ?></a></li>
                <li><a href="/marketplace"><?= e(__('common.nav.market')) ?></a></li>
                <li><a href="/blog"><?= e(__('common.nav.blog')) ?></a></li>
                <li><a href="/iletisim"><?= e(__('common.nav.contact')) ?></a></li>
                <li><a href="/bilgi-bankasi"><?= e(__('common.nav.knowledge')) ?></a></li>
                <li><a href="/destek"><?= e(__('common.nav.support')) ?></a></li>
            </ul>
        </nav>
    </div>
</header>
