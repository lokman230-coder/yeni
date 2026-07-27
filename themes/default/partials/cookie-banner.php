<?php
if (isset($_COOKIE['aho_cookie_consent'])) {
    return;
}
?>
<div class="aho-cookie-banner" id="ahoCookieBanner" role="dialog" aria-labelledby="ahoCookieTitle">
    <div class="aho-cookie-banner__content">
        <div>
            <div class="aho-cookie-banner__title" id="ahoCookieTitle"><?= e(__('common.cookie.title')) ?></div>
            <div class="aho-cookie-banner__text"><?= e(__('common.cookie.text')) ?></div>
        </div>
        <div class="aho-cookie-banner__actions">
            <a href="/cerez-politikasi" class="aho-btn aho-btn--ghost aho-btn--sm"><?= e(__('common.cookie.policy')) ?></a>
            <button class="aho-btn aho-btn--outline aho-btn--sm" data-aho-cookie="reject"><?= e(__('common.cookie.reject')) ?></button>
            <button class="aho-btn aho-btn--primary aho-btn--sm" data-aho-cookie="accept"><?= e(__('common.cookie.accept')) ?></button>
        </div>
    </div>
</div>
