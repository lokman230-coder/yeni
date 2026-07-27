<footer class="aho-ftr" role="contentinfo">
    <div class="aho-container">
        <div class="aho-ftr__grid">
            <div class="aho-ftr__col aho-ftr__col--brand">
                <div class="aho-ftr__brand">
                    <img src="<?= asset('img/logo-icon.png') ?>" alt="" width="40" height="40">
                    <span class="aho-ftr__brand-text">Ahost <b>Bilişim</b></span>
                </div>
                <p class="aho-ftr__desc">
                    Modern, güvenilir hosting, domain ve dijital hizmet platformu. Kendi sitenizi ve mobil uygulamanızı dakikalar içinde inşa edin.
                </p>
                <div class="aho-ftr__social">
                    <a href="#" aria-label="Twitter">𝕏</a>
                    <a href="#" aria-label="Instagram">📷</a>
                    <a href="#" aria-label="LinkedIn">in</a>
                    <a href="#" aria-label="YouTube">▶</a>
                </div>
            </div>

            <div class="aho-ftr__col">
                <h4><?= e(__('common.footer.services')) ?></h4>
                <ul>
                    <li><a href="/hosting">Hosting</a></li>
                    <li><a href="/sunucular">VPS &amp; Sunucu</a></li>
                    <li><a href="/domain">Domain</a></li>
                    <li><a href="/domain-transfer">Domain Transfer</a></li>
                    <li><a href="/site-builder">Site Builder</a></li>
                    <li><a href="/mobile-builder">Mobile Builder</a></li>
                    <li><a href="/site-araclari">Site Araçları</a></li>
                    <li><a href="/marketplace">Marketplace</a></li>
                </ul>
            </div>

            <div class="aho-ftr__col">
                <h4><?= e(__('common.footer.help')) ?></h4>
                <ul>
                    <li><a href="/bilgi-bankasi">Bilgi Bankası</a></li>
                    <li><a href="/blog">Blog</a></li>
                    <li><a href="/duyurular">Duyurular</a></li>
                    <li><a href="/destek">Destek</a></li>
                    <li><a href="/iletisim">İletişim</a></li>
                </ul>
            </div>

            <div class="aho-ftr__col">
                <h4><?= e(__('common.footer.corporate')) ?></h4>
                <ul>
                    <li><a href="/hakkimizda">Hakkımızda</a></li>
                    <li><a href="/misyon">Misyon</a></li>
                    <li><a href="/vizyon">Vizyon</a></li>
                    <li><a href="/gizlilik-politikasi">Gizlilik Politikası</a></li>
                    <li><a href="/cerez-politikasi">Çerez Politikası</a></li>
                    <li><a href="/kullanim-sartlari">Kullanım Şartları</a></li>
                    <li><a href="/hizmet-politikasi">Hizmet Politikası</a></li>
                    <li><a href="/iade-sartlari">İade Şartları</a></li>
                    <li><a href="/referanslar">Referanslar</a></li>
                </ul>
            </div>

            <div class="aho-ftr__col">
                <h4><?= e(__('common.footer.contact')) ?></h4>
                <ul>
                    <li>📞 0850 000 00 00</li>
                    <li>✉️ destek@ahost.web.tr</li>
                    <li>📍 İstanbul, Türkiye</li>
                </ul>
                <h4 style="margin-top:1.5rem"><?= e(__('common.footer.payments')) ?></h4>
                <div class="aho-ftr__payments">
                    <span class="aho-ftr__pay">PayTR</span>
                    <span class="aho-ftr__pay">Visa</span>
                    <span class="aho-ftr__pay">Mastercard</span>
                    <span class="aho-ftr__pay">Havale/EFT</span>
                </div>
            </div>
        </div>

        <div class="aho-ftr__bottom">
            <p><?= str_replace(':year', (string)date('Y'), __('common.footer.copyright')) ?></p>
            <p class="aho-ftr__law">
                <?= e(__('common.footer.provider')) ?> &middot; <?= e(__('common.footer.law_5651')) ?>
            </p>
        </div>
    </div>
</footer>
