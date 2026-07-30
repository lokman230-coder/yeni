<?php /* Ahost One ortak footer: site, müşteri, auth ve admin wrapper'lari burayi çağırır. */ ?>
<?php
$aoFooterNormalize = static function($value) {
  $value = (string)$value;
  return strtr($value, [
    'Sayılı' => 'Sayılı',
    'kapsamında' => 'kapsamında',
    'tarafından' => 'tarafından',
    'onaylı' => 'onaylı',
    'Yer Sağlayıcı' => 'Yer Sağlayıcı',
    'Sağlayıcı' => 'Sağlayıcı',
    'Paylasimli' => 'Paylasimli',
    'Yardim' => 'Yardim',
    'Hesabım' => 'Hesabım',
    'Bilgi Bankasi' => 'Bilgi Bankasi',
    'İletişim' => 'İletişim',
    'Hakkımızda' => 'Hakkımızda',
    'Gizlilik Politikası' => 'Gizlilik Politikası',
    'Telif hakkı' => 'Telif hakkı',
    'Bilişim' => 'Bilişim',
    'Tasarım' => 'Tasarım',
    'Tum Haklari Saklidir' => 'Tüm Hakları Saklıdır',
    'Kabul Ettiğimiz Ödemeler' => 'Kabul Ettiğimiz Ödemeler',
    'Ödeme' => 'Ödeme',
    'Ödemeler' => 'Ödemeler',
    'Bülten' => 'Bülten',
    'bültenimize' => 'bültenimize',
    'güncellemeleri' => 'güncellemeleri',
    'için' => 'için',
    'Gölbaşı' => 'Gölbaşı',
    'ŞANLIURFA' => 'ŞANLIURFA',
    'Sayılı' => 'Sayılı',
    'kapsamında' => 'kapsamında',
    'tarafından' => 'tarafından',
    'onaylı' => 'onaylı',
    'Sağlayıcı' => 'Sağlayıcı',
    'Hakkımızda' => 'Hakkımızda',
    'Bilgi Bankası' => 'Bilgi Bankasi',
    'İletişim' => 'İletişim',
    'Yardım' => 'Yardim',
    'Hesabım' => 'Hesabım',
    'Tasarım' => 'Tasarım',
    'Bilişim' => 'Bilişim',
    'Gölbaşı' => 'Gölbaşı',
  ]);
};
$aoFooterText = static function($key, $default = '') use ($aoFooterNormalize) {
  return trim($aoFooterNormalize(admin_setting($key, $default)));
};
$aoFooterMail = $aoFooterText('footer_contact_email', 'iletisim@ahost.web.tr');
$aoFooterPhone = $aoFooterText('footer_contact_phone', '+90(544) 471 65 41');
$aoFooterPhoneHref = preg_replace('/\D+/', '', $aoFooterPhone);
$aoFooterPayments = preg_split('/\s*(?:,|\R)\s*/', $aoFooterText('footer_payment_labels', 'BANKA, PAYTR, VISA, MC'), -1, PREG_SPLIT_NO_EMPTY);
$aoFooterProviderTitle = $aoFooterText('footer_provider_title', 'Ahost.Web.Tr;');
$aoFooterProviderText = $aoFooterText('footer_provider_text', "5651 Sayılı Kanun kapsamında BTK tarafından onaylı Yer Sağlayıcı'dir.");
$aoFooterProviderUrl = $aoFooterText('footer_provider_url', 'https://internet.btk.gov.tr/yer-saglayici-listesi?page=1&q=lokman%20demir');
$aoFooterProviderBody = trim(str_replace(["Yer Sağlayıcı'dir.", "Yer Sağlayıcı'dir"], '', $aoFooterProviderText));
$aoFooterPaymentMeta = static function($label) {
  $raw = trim((string)$label);
  $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $raw));
  $paytrLogo = function_exists('ao_theme_asset_url') ? ao_theme_asset_url('site', 'assets/images/paytr.webp') : '';
  $map = [
    'banka' => ['label' => 'Banka', 'icon' => 'fa-solid fa-building-columns', 'class' => 'bank', 'image' => ''],
    'bank' => ['label' => 'Banka', 'icon' => 'fa-solid fa-building-columns', 'class' => 'bank', 'image' => ''],
    'paytr' => ['label' => 'PayTR', 'icon' => '', 'class' => 'paytr', 'image' => $paytrLogo],
    'visa' => ['label' => 'VISA', 'icon' => '', 'class' => 'visa', 'image' => ''],
    'mc' => ['label' => 'Mastercard', 'icon' => '', 'class' => 'mastercard', 'image' => ''],
    'mastercard' => ['label' => 'Mastercard', 'icon' => '', 'class' => 'mastercard', 'image' => ''],
  ];
  return $map[$key] ?? ['label' => $raw, 'icon' => 'fa-solid fa-credit-card', 'class' => 'generic', 'image' => ''];
};
$aoFooterSocialLinks = [
  ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f', 'url' => $aoFooterText('social_facebook_url', '#')],
  ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'fa-brands fa-instagram', 'url' => $aoFooterText('social_instagram_url', '#')],
  ['key' => 'youtube', 'label' => 'YouTube', 'icon' => 'fa-brands fa-youtube', 'url' => $aoFooterText('social_youtube_url', '#')],
  ['key' => 'linkedin', 'label' => 'LinkedIn', 'icon' => 'fa-brands fa-linkedin-in', 'url' => $aoFooterText('social_linkedin_url', '#')],
];
$aoFooterMenu = function_exists('ao_get_menu_v222') ? ao_get_menu_v222('footer') : [];
$aoMobileMenu = function_exists('ao_get_menu_v222') ? ao_get_menu_v222('mobile') : [];
$aoSharedFooterShowSupportWidget = $aoSharedFooterShowSupportWidget ?? true;
$aoSharedFooterShowMobileNav = $aoSharedFooterShowMobileNav ?? true;
$aoSharedFooterShowBuilderFab = $aoSharedFooterShowBuilderFab ?? true;
// v26.2.5: Bu paylaşılan footer site/müşteri/admin tarafından ortak çağrılıyor, ama
// aşağıdaki büyük pazarlama footer'i (bülten aboneliği, sosyal medya, Hosting/Yardim/
// Kurumsal sütunları) admin panelinde görünmemeli - sadece halka açık site ve müşteri
// paneli için anlamlı. $aoHeadContext, admin/partials/header.php tarafından 'admin'
// olarak ayarlanıp aynı require zincirinde bu dosyaya kadar taşınıyor.
$aoIsAdminFooterContext = (($aoHeadContext ?? 'site') === 'admin');
?>
<?php if (!$aoIsAdminFooterContext): ?>
<footer class="site-footer ahost-site-footer">
  <div class="ahost-footer-inner">
    <div class="ahost-footer-newsletter">
      <form action="<?= url('newsletter/subscribe') ?>" method="post">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <label class="sr-only" for="ahost-footer-email">E-posta adresiniz</label>
        <input id="ahost-footer-email" type="email" name="email" placeholder="E-posta adresinizi girin">
        <button type="submit">Abone Ol</button>
      </form>
      <p><?= e($aoFooterText('footer_newsletter_text', 'Haber ve güncellemeleri almak için bültenimize abone olun')) ?></p>
    </div>
    <div class="ahost-footer-top">
      <?php if($aoFooterMenu): ?>
        <?php foreach(array_slice($aoFooterMenu,0,4) as $aoFm): ?>
          <nav class="ahost-footer-col" aria-label="<?= e($aoFm['label'] ?? 'Footer') ?>">
            <h4><?= e($aoFm['label'] ?? 'Footer') ?></h4>
            <?php $aoFmChildren = !empty($aoFm['children']) ? $aoFm['children'] : [$aoFm]; ?>
            <?php foreach($aoFmChildren as $aoFc): ?>
              <a href="<?= function_exists('ao_menu_url_v222') ? e(ao_menu_url_v222($aoFc['url'] ?? '')) : url($aoFc['url'] ?? '') ?>"><?= e($aoFc['label'] ?? '') ?></a>
            <?php endforeach; ?>
          </nav>
        <?php endforeach; ?>
      <?php else: ?>
      <nav class="ahost-footer-col" aria-label="Hosting">
        <h4><?= e($aoFooterText('footer_hosting_title', 'Hosting')) ?></h4>
        <a href="<?= url('hosting') ?>"><?= e($aoFooterText('footer_hosting_item_1', 'Paylaşımlı Hosting')) ?></a>
        <a href="<?= url('hosting') ?>"><?= e($aoFooterText('footer_hosting_item_2', 'E-Mail Hosting')) ?></a>
        <a href="<?= url('mobil-uygulama') ?>"><?= e($aoFooterText('footer_hosting_item_3', 'Android Uygulama')) ?></a>
        <a href="<?= url('domain') ?>"><?= e($aoFooterText('footer_hosting_item_4', 'Domain Sorgula')) ?></a>
      </nav>
      <nav class="ahost-footer-col" aria-label="Yardim">
        <h4><?= e($aoFooterText('footer_help_title', 'Yardim')) ?></h4>
        <a href="<?= url('client') ?>"><?= e($aoFooterText('footer_help_item_1', 'Hesabım')) ?></a>
        <a href="<?= url('bilgi-bankasi') ?>"><?= e($aoFooterText('footer_help_item_2', 'Bilgi Bankasi')) ?></a>
        <a href="<?= url('contact') ?>"><?= e($aoFooterText('footer_help_item_3', 'İletişim')) ?></a>
        <a href="<?= url('bilgi-bankasi') ?>"><?= e($aoFooterText('footer_help_item_4', 'Yardim')) ?></a>
      </nav>
      <nav class="ahost-footer-col" aria-label="Kurumsal">
        <h4><?= e($aoFooterText('footer_corporate_title', 'Kurumsal')) ?></h4>
        <a href="<?= url('hakkimizda') ?>"><?= e($aoFooterText('footer_corporate_item_1', 'Hakkımızda')) ?></a>
        <a href="<?= url('misyon') ?>"><?= e($aoFooterText('footer_corporate_item_2', 'Misyonumuz')) ?></a>
        <a href="<?= url('vizyon') ?>"><?= e($aoFooterText('footer_corporate_item_3', 'Vizyon')) ?></a>
        <a href="<?= url('gizlilik-politikasi') ?>"><?= e($aoFooterText('footer_corporate_item_4', 'Gizlilik Politikası')) ?></a>
      </nav>
      <?php endif; ?>
      <div class="ahost-footer-brand">
        <img src="<?= e(ao_brand_logo_url()) ?>" alt="Ahost">
        <p><?= e($aoFooterText('footer_copyright', 'Telif hakkı 2026 Ahost Bilişim Web Tasarım')) ?></p>
        <p><?= e($aoFooterText('footer_brand_text', 'Domain Android Hosting Hizmetleri. Tüm Hakları Saklıdır.')) ?></p>
        <div class="ahost-footer-social" aria-label="Sosyal medya">
          <?php foreach($aoFooterSocialLinks as $aoSocial): ?>
            <a class="ahost-footer-social-link ahost-footer-social-link--<?= e($aoSocial['key']) ?>" href="<?= e($aoSocial['url'] ?: '#') ?>" target="<?= $aoSocial['url'] && $aoSocial['url'] !== '#' ? '_blank' : '_self' ?>" rel="noopener" aria-label="<?= e($aoSocial['label']) ?>">
              <i class="<?= e($aoSocial['icon']) ?>" aria-hidden="true"></i>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>


    <div class="ahost-footer-bottom">
      <address>
        <strong><?= e($aoFooterText('footer_contact_person', 'Yetkili: Lokman Demir')) ?></strong>
        <span><?= nl2br(e($aoFooterText('footer_contact_address', 'Gölbaşı Mahallesi Garip Sokak No:39/1 Bozova/ŞANLIURFA'))) ?></span>
        <a href="tel:<?= e($aoFooterPhoneHref) ?>"><?= e($aoFooterPhone) ?></a>
        <a href="mailto:<?= e($aoFooterMail) ?>"><?= e($aoFooterMail) ?></a>
      </address>
      <div class="ahost-footer-payments">
        <span><?= e($aoFooterText('footer_payments_title', 'Kabul Ettiğimiz Ödemeler')) ?></span>
        <?php foreach($aoFooterPayments as $aoFooterPayment): $aoPay = $aoFooterPaymentMeta($aoFooterPayment); ?>
          <b class="ahost-footer-payment-logo ahost-footer-payment-logo--<?= e($aoPay['class']) ?>" title="<?= e($aoPay['label']) ?>">
            <?php if(!empty($aoPay['image'])): ?>
              <img src="<?= e($aoPay['image']) ?>" alt="<?= e($aoPay['label']) ?>">
            <?php elseif(!empty($aoPay['icon'])): ?>
              <i class="<?= e($aoPay['icon']) ?>" aria-hidden="true"></i>
              <span><?= e($aoPay['label']) ?></span>
            <?php else: ?>
              <span><?= e($aoPay['label']) ?></span>
            <?php endif; ?>
          </b>
        <?php endforeach; ?>
        <p class="ahost-footer-provider">
          <strong><?= e($aoFooterProviderTitle) ?></strong>
          <?= e($aoFooterProviderBody) ?>
          <a href="<?= e($aoFooterProviderUrl) ?>" target="_blank" rel="noopener">Yer Sağlayıcı'dir</a>
          <a class="ahost-footer-cookie-link" href="<?= url('cerez-politikasi') ?>">Çerez Politikası</a>
        </p>
      </div>
    </div>
  </div>
</footer>
<?php else: ?>
<footer class="ao-admin-simple-footer">
  © <?= date('Y') ?> Ahost One - Yönetim Paneli
</footer>
<?php endif; ?>
<?php
$aoCampaignPreview = isset($_GET['campaign_preview']) && function_exists('current_admin') && current_admin();
$aoCampaignPopup = (!$aoIsAdminFooterContext && function_exists('ao_active_campaign_popup')) ? ao_active_campaign_popup((bool)$aoCampaignPreview) : null;
if ($aoCampaignPopup) {
  $aoCampaignHref = trim((string)$aoCampaignPopup['url']);
  $aoCampaignHref = preg_match('~^https?://~i', $aoCampaignHref) ? $aoCampaignHref : ($aoCampaignHref !== '' ? url(ltrim($aoCampaignHref, '/')) : '');
  $aoCampaignImage = filter_var((string)$aoCampaignPopup['image'], FILTER_VALIDATE_URL) ? (string)$aoCampaignPopup['image'] : '';
}
?>
<?php if ($aoCampaignPopup): ?>

<div class="ao-campaign-modal" data-ao-campaign-popup data-campaign-id="<?= e($aoCampaignPopup['id']) ?>" data-cooldown="<?= (int)$aoCampaignPopup['cooldown'] ?>" data-preview="<?= $aoCampaignPopup['preview'] ? '1' : '0' ?>" hidden role="dialog" aria-modal="true" aria-labelledby="ao-campaign-title">
  <div class="ao-campaign-modal__card">
    <button class="ao-campaign-modal__close" type="button" data-ao-campaign-close aria-label="Kapat">&times;</button>
    <div class="ao-campaign-modal__content"><span class="ao-campaign-modal__eyebrow">Özel Kampanya</span><h2 id="ao-campaign-title"><?= e($aoCampaignPopup['title']) ?></h2><?php if($aoCampaignPopup['body'] !== ''): ?><p><?= nl2br(e($aoCampaignPopup['body'])) ?></p><?php endif; ?><?php if($aoCampaignHref !== ''): ?><a class="ao-campaign-modal__cta" href="<?= e($aoCampaignHref) ?>"><?= e($aoCampaignPopup['button']) ?></a><?php endif; ?></div>
    <div class="ao-campaign-modal__visual"><?php if($aoCampaignImage !== ''): ?><img src="<?= e($aoCampaignImage) ?>" alt="<?= e($aoCampaignPopup['title']) ?>"><?php endif; ?></div>
  </div>
</div>
<script defer src="<?= assetv('js/front/campaign-popup.js') ?>"></script>
<?php endif; ?>
<?php
$aoRouteForBuilder = trim((string)($_SERVER['AHOST_ROUTE_RESOLVED'] ?? ''), '/');
$aoBuilderCtx = function_exists('ao_builder_context_from_route') ? ao_builder_context_from_route($aoRouteForBuilder) : ['target'=>'site','template'=>($aoRouteForBuilder ?: 'home')];
$aoWidgetEnabled = (string)admin_setting('support_widget_enabled','1') === '1';
$aoWidgetAllowedByBuilder = !function_exists('ao_builder_pro_has_widget') || ao_builder_pro_has_widget('site', $aoBuilderCtx['template'] ?? 'home', 'support_widget', true);
if (function_exists('current_customer') && current_customer()) {
  $aoWidgetAllowedByBuilder = true;
}
$aoSupportPhone = preg_replace('/\D+/', '', (string)admin_setting('support_call_number',''));
$aoSupportWhats = preg_replace('/\D+/', '', (string)admin_setting('support_whatsapp_number',''));
$aoSupportAi = (string)admin_setting('support_widget_ai_enabled', admin_setting('support_ai_enabled','1')) === '1';
$aoSupportSearch = (string)admin_setting('support_widget_search_enabled','1') === '1';
$aoSupportLive = (string)admin_setting('support_widget_live_chat_enabled','1') === '1';
$aoSupportTicket = (string)admin_setting('support_widget_ticket_enabled','1') === '1';
$aoSupportWhatsOn = (string)admin_setting('support_widget_whatsapp_enabled','1') === '1';
$aoSupportPhoneOn = (string)admin_setting('support_widget_phone_enabled','1') === '1';
$aoGreeting = admin_setting('support_widget_greeting','Merhaba, size nasil yardimci olabiliriz?');
$aoSupportWidgetStyle = admin_setting('support_widget_style','iconbar') === 'classic' ? 'classic' : 'iconbar';
$aoSupportLabels = [
  'search'=>admin_setting('support_widget_search_label','Bilgi Bankasi'),
  'ai'=>admin_setting('support_widget_ai_label','AI Destek'),
  'live'=>admin_setting('support_widget_live_label','Canlı Sohbet'),
  'ticket'=>admin_setting('support_widget_ticket_label','Ticket Aç'),
  'center'=>admin_setting('support_widget_center_label','Destek Merkezi'),
];
$aoDefaultSupportIconClasses = [
  'search'=>'fa-solid fa-box',
  'ai'=>'fa-solid fa-server',
  'live'=>'fa-solid fa-award',
  'ticket'=>'fa-solid fa-laptop-code',
  'center'=>'fa-solid fa-comments',
  'phone'=>'fa-solid fa-phone-volume',
  'whatsapp'=>'fa-brands fa-whatsapp',
  'top'=>'fa-solid fa-arrow-up',
];
$aoSupportIcons = [
  'search'=>trim((string)admin_setting('support_widget_search_icon','')) ?: $aoDefaultSupportIconClasses['search'],
  'ai'=>trim((string)admin_setting('support_widget_ai_icon','')) ?: $aoDefaultSupportIconClasses['ai'],
  'live'=>trim((string)admin_setting('support_widget_live_icon','')) ?: $aoDefaultSupportIconClasses['live'],
  'ticket'=>trim((string)admin_setting('support_widget_ticket_icon','')) ?: $aoDefaultSupportIconClasses['ticket'],
  'center'=>trim((string)admin_setting('support_widget_center_icon','')) ?: $aoDefaultSupportIconClasses['center'],
  'phone'=>trim((string)admin_setting('support_widget_phone_icon','')) ?: $aoDefaultSupportIconClasses['phone'],
  'whatsapp'=>trim((string)admin_setting('support_widget_whatsapp_icon','')) ?: $aoDefaultSupportIconClasses['whatsapp'],
  'top'=>'fa-solid fa-arrow-up',
];
if (!function_exists('ao_support_icon_markup_v2616')) {
  function ao_support_icon_markup_v2616($key, $custom = '') {
    $custom = trim((string)$custom);
    $defaults = [
      'search' => 'fa-solid fa-box',
      'ai' => 'fa-solid fa-server',
      'live' => 'fa-solid fa-award',
      'ticket' => 'fa-solid fa-laptop-code',
      'center' => 'fa-solid fa-comments',
      'phone' => 'fa-solid fa-phone-volume',
      'whatsapp' => 'fa-brands fa-whatsapp',
      'top' => 'fa-solid fa-arrow-up',
    ];
    $brandNames = ['whatsapp','instagram','facebook','youtube','tiktok','telegram','discord','github','linkedin','x-twitter'];
    $legacyAliases = ['fab'=>'fa-brands','fas'=>'fa-solid','far'=>'fa-regular'];
    $class = $custom;
    foreach ($legacyAliases as $from => $to) {
      $class = preg_replace('/(^|\s)'.preg_quote($from, '/').'(\s|$)/i', '$1'.$to.'$2', $class);
    }
    if ($class === '' || str_contains($class, '?') || preg_match('/^[\p{So}\p{C}\*\s]+$/u', $class)) $class = $defaults[$key] ?? 'fa-solid fa-circle-question';
    if (!preg_match('/(^|\s)fa-(solid|regular|brands|light|thin|duotone)(\s|$)/i', $class)) {
      if (preg_match('/(^|\s)fa-[a-z0-9-]+(\s|$)/i', $class)) {
        $class = (preg_match('/fa-('.implode('|', array_map('preg_quote', $brandNames)).')(\s|$)/i', $class) ? 'fa-brands ' : 'fa-solid ').$class;
      } else {
        $iconName = preg_replace('/[^a-z0-9-]+/i', '-', strtolower($class));
        $class = (in_array($iconName, $brandNames, true) ? 'fa-brands ' : 'fa-solid ').'fa-'.$iconName;
      }
    }
    return '<i class="'.htmlspecialchars(trim($class), ENT_QUOTES, 'UTF-8').'" aria-hidden="true"></i>';
  }
}
$aoHoursEnabled = (string)admin_setting('support_hours_enabled','0') === '1';
$aoWhatsActive = true;
if ($aoHoursEnabled) { $now=date('H:i'); $aoWhatsActive = ($now >= admin_setting('support_hours_start','09:00') && $now <= admin_setting('support_hours_end','18:00')); }
$aoCust = function_exists('current_customer') ? current_customer() : null;
$aoSupportEdgeOffset = max(0, min(48, (int)admin_setting('support_widget_edge_offset','6')));
$aoSupportBottomOffset = max(0, min(220, (int)admin_setting('support_widget_bottom_offset','22')));
$aoSupportButtonSize = max(16, min(96, (int)admin_setting('support_widget_button_size','54')));
$aoSupportIconSize = max(12, min(48, (int)admin_setting('support_widget_icon_size','19')));
$aoSupportMenuItems = json_decode((string)admin_setting('support_widget_items_json',''), true);
if (!is_array($aoSupportMenuItems) || !$aoSupportMenuItems) {
  $aoSupportMenuItems = [];
  if($aoSupportSearch) $aoSupportMenuItems[] = ['enabled'=>'1','type'=>'modal_search','label'=>$aoSupportLabels['search'],'icon'=>$aoSupportIcons['search'],'url'=>'','color'=>''];
  if($aoSupportAi) $aoSupportMenuItems[] = ['enabled'=>'1','type'=>'modal_ai','label'=>$aoSupportLabels['ai'],'icon'=>$aoSupportIcons['ai'],'url'=>'','color'=>''];
  if($aoSupportLive) $aoSupportMenuItems[] = ['enabled'=>'1','type'=>'modal_live','label'=>$aoSupportLabels['live'],'icon'=>$aoSupportIcons['live'],'url'=>'','color'=>''];
  if($aoSupportTicket) $aoSupportMenuItems[] = ['enabled'=>'1','type'=>'url','label'=>$aoSupportLabels['ticket'],'icon'=>$aoSupportIcons['ticket'],'url'=>'client/support','color'=>''];
  $aoSupportMenuItems[] = ['enabled'=>'1','type'=>'modal_center','label'=>$aoSupportLabels['center'],'icon'=>$aoSupportIcons['center'],'url'=>'','color'=>''];
}
$aoSupportQuickLabels = ['phone'=>'Telefon','whatsapp'=>'WhatsApp','top'=>'Yukarı Çık'];
foreach ($aoSupportMenuItems as $aoSupportMenuItem) {
  if (empty($aoSupportMenuItem['enabled'])) continue;
  $aoQuickType = (string)($aoSupportMenuItem['type'] ?? '');
  $aoQuickLabel = trim((string)($aoSupportMenuItem['label'] ?? ''));
  if ($aoQuickLabel !== '' && isset($aoSupportQuickLabels[$aoQuickType])) {
    $aoSupportQuickLabels[$aoQuickType] = $aoQuickLabel;
  }
}

?>
<?php if($aoSharedFooterShowMobileNav): ?>
<?php
  $aoMobileNavRoute = trim((string)($_SERVER['AHOST_ROUTE_RESOLVED'] ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/')), '/');
  $aoMobileCustomer = function_exists('current_customer') ? current_customer() : null;
  $aoMobileSupportUrls = ['client/support','destek','support'];
  $aoMobileNavItems = [];
  foreach ((array)$aoMobileMenu as $aoMm) {
    $aoMmUrl = trim((string)($aoMm['url'] ?? ''), '/');
    $aoMmLabel = trim((string)($aoMm['label'] ?? ''));
    if (in_array($aoMmUrl, $aoMobileSupportUrls, true) || mb_stripos($aoMmLabel, 'destek') !== false) continue;
    if ($aoMmUrl === 'client/login' && $aoMobileCustomer) { $aoMmUrl = 'client'; $aoMmLabel = $aoMmLabel ?: 'Panelim'; }
    $aoMobileChildren = !empty($aoMm['children']) && is_array($aoMm['children']) ? $aoMm['children'] : [];
    if (in_array(mb_strtolower($aoMmLabel), ['ürünler','urunler'], true)) {
      $aoMmLabel = 'Kategori';
      $aoMmUrl = '#mobile-categories';
    }
    if ($aoMmLabel === 'Kategori' && !$aoMobileChildren) {
      $aoMobileChildren = [
        ['label'=>'Hosting Paketleri','url'=>'hosting'],
        ['label'=>'Domain İşlemleri','url'=>'domain'],
        ['label'=>'Web Tasarım','url'=>'web-tasarim'],
        ['label'=>'Mobil Uygulama','url'=>'mobil-uygulama'],
        ['label'=>'Site Builder','url'=>'sitebuilder'],
        ['label'=>'Mobile Builder','url'=>'mobilebuilder'],
        ['label'=>'Site Araçları','url'=>'site-araclari'],
        ['label'=>'Marketplace','url'=>'marketplace'],
      ];
    }
    $aoMobileNavItems[] = ['label'=>$aoMmLabel, 'url'=>$aoMmUrl, 'children'=>$aoMobileChildren, 'icon'=>(!empty($aoMm['icon']) ? $aoMm['icon'] : mb_substr($aoMmLabel,0,1))];
    if (count($aoMobileNavItems) >= 4) break;
  }
?>
<nav class="mobile-bottom-nav ao-mobile-nav-pro" aria-label="Mobil hızlı menü">
  <?php foreach($aoMobileNavItems as $aoMm):
    $aoMmHref = function_exists('ao_menu_url_v222') ? ao_menu_url_v222($aoMm['url']) : url($aoMm['url']);
    $aoMmPath = trim((string)parse_url($aoMmHref, PHP_URL_PATH), '/');
    $aoMmActive = ($aoMmPath !== '' && $aoMmPath === $aoMobileNavRoute) || ($aoMmPath === '' && $aoMobileNavRoute === '');
  ?>
    <?php if(!empty($aoMm['children'])): ?>
      <button type="button" class="mobile-nav-item<?= $aoMmActive ? ' active' : '' ?>" data-mobile-category-toggle aria-expanded="false" aria-controls="ao-mobile-category-panel"><b><?= e($aoMm['icon']) ?></b><span><?= e($aoMm['label']) ?></span></button>
    <?php else: ?>
      <a class="mobile-nav-item<?= $aoMmActive ? ' active' : '' ?>" href="<?= e($aoMmHref) ?>"><b><?= e($aoMm['icon']) ?></b><span><?= e($aoMm['label']) ?></span></a>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php if($aoWidgetEnabled && $aoWidgetAllowedByBuilder): ?>
    <button type="button" class="mobile-nav-item mobile-support-toggle" data-mobile-support><b>💬</b><span>Destek</span></button>
  <?php endif; ?>
</nav>
<?php
  $aoMobileCategoryItems = [];
  foreach($aoMobileNavItems as $aoMobilePanelItem){
    if(!empty($aoMobilePanelItem['children'])) { $aoMobileCategoryItems = $aoMobilePanelItem['children']; break; }
  }
?>
<?php if($aoMobileCategoryItems): ?>
<div class="ao-mobile-category-panel" id="ao-mobile-category-panel" data-mobile-category-panel hidden>
  <div class="ao-mobile-category-head"><strong>Kategoriler</strong><button type="button" data-mobile-category-close aria-label="Kapat">×</button></div>
  <div class="ao-mobile-category-grid">
    <?php foreach($aoMobileCategoryItems as $aoCat): ?>
      <a href="<?= function_exists('ao_menu_url_v222') ? e(ao_menu_url_v222($aoCat['url'] ?? '')) : url($aoCat['url'] ?? '') ?>"><?= e($aoCat['label'] ?? '') ?></a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php if($aoSharedFooterShowSupportWidget && $aoWidgetEnabled && $aoWidgetAllowedByBuilder): ?>
<link rel="stylesheet" href="<?= assetv('css/support-widget-pro.css') ?>">
<div class="ao-support-widget-pro <?= admin_setting('support_widget_position','right')==='left'?'left':'right' ?> <?= $aoSupportWidgetStyle==='iconbar'?'is-iconbar':'is-classic' ?>" aria-label="Hızlı destek" data-builder-block="support_widget">
  <?php if($aoSupportWidgetStyle==='iconbar'): ?>
  <div class="ao-support-iconbar ao-support-iconbar--detached">
    <?php foreach($aoSupportMenuItems as $aoSupportMenuItem): ?>
      <?php
        if (empty($aoSupportMenuItem['enabled'])) continue;
        $aoMiType = (string)($aoSupportMenuItem['type'] ?? 'url');
        $aoMiLabel = trim((string)($aoSupportMenuItem['label'] ?? '')) ?: 'Destek';
        $aoMiIcon = trim((string)($aoSupportMenuItem['icon'] ?? ''));
        $aoMiColor = trim((string)($aoSupportMenuItem['color'] ?? ''));
        $aoMiUrlRaw = trim((string)($aoSupportMenuItem['url'] ?? ''));
        $aoMiClass = 'ao-support-icon-item ao-support-item--'.preg_replace('/[^a-z0-9_-]/i', '-', $aoMiType);
        if ($aoMiUrlRaw === 'client/support' || mb_stripos($aoMiLabel, 'ticket') !== false) $aoMiClass .= ' ao-support-item--ticket';
        if (in_array($aoMiType, ['phone','whatsapp','top'], true) || (string)($aoSupportMenuItem['url'] ?? '') === '#top') continue;
        $aoModalMap = ['modal_search'=>'search','modal_ai'=>'ai','modal_live'=>'live','modal_center'=>''];
      ?>
      <?php if(isset($aoModalMap[$aoMiType])): ?>
        <button type="button" class="<?= e($aoMiClass) ?>" data-support-open="<?= e($aoModalMap[$aoMiType]) ?>" title="<?= e($aoMiLabel) ?>" aria-label="<?= e($aoMiLabel) ?>"><?= ao_support_icon_markup_v2616(str_replace('modal_','',$aoMiType), $aoMiIcon) ?></button>
      <?php elseif($aoMiType === 'phone' && $aoSupportPhone && $aoSupportPhoneOn): ?>
        <a class="<?= e($aoMiClass) ?>" href="tel:<?= e($aoSupportPhone) ?>" target="_blank" title="<?= e($aoMiLabel) ?>" aria-label="<?= e($aoMiLabel) ?>"><?= ao_support_icon_markup_v2616('phone', $aoMiIcon) ?></a>
      <?php elseif($aoMiType === 'whatsapp' && $aoSupportWhats && $aoSupportWhatsOn && $aoWhatsActive): ?>
        <a class="<?= e($aoMiClass) ?>" target="_blank" rel="noopener" href="https://wa.me/<?= e($aoSupportWhats) ?>" title="<?= e($aoMiLabel) ?>" aria-label="<?= e($aoMiLabel) ?>"><?= ao_support_icon_markup_v2616('whatsapp', $aoMiIcon) ?></a>
      <?php elseif($aoMiType === 'top' || ($aoSupportMenuItem['url'] ?? '') === '#top'): ?>
        <button type="button" class="<?= e($aoMiClass) ?>" data-support-scroll-top title="<?= e($aoMiLabel) ?>" aria-label="<?= e($aoMiLabel) ?>"><?= ao_support_icon_markup_v2616('top', $aoMiIcon) ?></button>
      <?php else: ?>
        <?php $aoMiUrl = trim((string)($aoSupportMenuItem['url'] ?? '#')); $aoMiHref = preg_match('~^(https?:|mailto:|tel:|#)~i', $aoMiUrl) ? $aoMiUrl : url($aoMiUrl ?: '#'); ?>
        <a class="<?= e($aoMiClass) ?>" href="<?= e($aoMiHref) ?>" title="<?= e($aoMiLabel) ?>" aria-label="<?= e($aoMiLabel) ?>"><?= ao_support_icon_markup_v2616('custom', $aoMiIcon) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <button type="button" class="ao-support-main" data-support-open><span>?</span><strong>Destek</strong></button>
  <?php endif; ?>
  <div class="ao-support-quick">
    <?php if($aoSupportPhone && $aoSupportPhoneOn): ?><a class="call" href="tel:<?= e($aoSupportPhone) ?>" target="_blank" title="<?= e($aoSupportQuickLabels['phone']) ?>" aria-label="<?= e($aoSupportQuickLabels['phone']) ?>"><?= ao_support_icon_markup_v2616('phone', $aoSupportIcons['phone']) ?><span><?= e($aoSupportPhone) ?></span></a><?php endif; ?>
    <?php if($aoSupportWhats && $aoSupportWhatsOn): ?><a class="wa <?= $aoWhatsActive?'':'disabled' ?>" <?= $aoWhatsActive?'target="_blank" rel="noopener" href="https://wa.me/'.e($aoSupportWhats).'"':'href="#" aria-disabled="true"' ?> title="<?= e($aoSupportQuickLabels['whatsapp']) ?>" aria-label="<?= e($aoSupportQuickLabels['whatsapp']) ?>"><?= ao_support_icon_markup_v2616('whatsapp', $aoSupportIcons['whatsapp']) ?><span><?= e($aoSupportWhats) ?></span></a><?php endif; ?>
    <button type="button" class="top" data-support-scroll-top title="<?= e($aoSupportQuickLabels['top']) ?>" aria-label="<?= e($aoSupportQuickLabels['top']) ?>"><?= ao_support_icon_markup_v2616('top', $aoSupportIcons['top']) ?></button>
  </div>
</div>

<div class="ao-support-modal ao-support-pro-modal" data-support-modal hidden>
  <div class="ao-support-box ao-support-pro-box">
    <button type="button" class="ao-support-close" data-support-close aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
    <div class="ao-support-pro-head"><div><strong>AI Destek Merkezi</strong><p><?= e($aoGreeting) ?></p></div><span>Canlı + AI destek</span></div>
    <div class="ao-support-tabs" role="tablist">
      <?php if($aoSupportSearch): ?><button type="button" class="active" data-support-tab="search">Ara</button><?php endif; ?>
      <?php if($aoSupportAi): ?><button type="button" data-support-tab="ai">AI Sor</button><?php endif; ?>
      <?php if($aoSupportLive): ?><button type="button" data-support-tab="live">Canlı Sohbet</button><?php endif; ?>
      <?php if($aoSupportTicket): ?><a href="<?= url('client/support') ?>">Ticket</a><?php endif; ?>
      <?php if($aoSupportWhats && $aoSupportWhatsOn && $aoWhatsActive): ?><a target="_blank" rel="noopener" href="https://wa.me/<?= e($aoSupportWhats) ?>">WhatsApp</a><?php endif; ?>
      <?php if($aoSupportPhone && $aoSupportPhoneOn): ?><a href="tel:<?= e($aoSupportPhone) ?>">Ara</a><?php endif; ?>
    </div>
    <?php if($aoSupportSearch): ?>
    <section class="ao-support-pane active" data-support-pane="search">
      <label>Bilgi Bankasında Ara<input data-support-search-input placeholder="Domain, hosting, SSL, Ödeme..."></label>
      <div class="ao-support-results" data-support-search-results><p>Bir kelime yazıp arama yapın.</p></div>
    </section>
    <?php endif; ?>
    <?php if($aoSupportAi): ?>
    <section class="ao-support-pane" data-support-pane="ai">
      <form data-support-ai-form><?= csrf_field() ?><label>Yapay Zekaya Sor<textarea name="q" placeholder="Sorunuzu yazın..."></textarea></label><button class="ao-support-submit" type="submit">Cevapla</button></form>
      <div class="ao-support-results" data-support-ai-result><p>AI destek bilgi bankası içeriklerinden cevap arar.</p></div>
    </section>
    <?php endif; ?>
    <?php if($aoSupportLive): ?>
    <section class="ao-support-pane" data-support-pane="live">
      <form method="post" action="<?= url('support/live-chat/start') ?>" class="ao-support-form ao-support-live-form">
        <?= csrf_field() ?>
        <div class="ao-support-depts wide">
          <?php foreach(['Teknik Destek','Satış Öncesi','Muhasebe','Domain','Hosting','Site Builder / Mobile Builder'] as $i=>$d): ?>
          <label><input type="radio" name="department" value="<?= e($d) ?>" <?= $i===0?'checked':'' ?>><?= e($d) ?></label>
          <?php endforeach; ?>
        </div>
        <label>Ad Soyad<input name="name" required value="<?= e(trim(($aoCust['first_name']??'').' '.($aoCust['last_name']??''))) ?>"></label>
        <label>E-posta<input type="email" name="email" required value="<?= e($aoCust['email']??'') ?>"></label>
        <label>Telefon<input name="phone" value="<?= e($aoCust['phone']??'') ?>"></label>
        <label>Konu<input name="subject" required value="Canlı Sohbet" placeholder="Kısa konu başlığı"></label>
        <label class="wide">Mesaj<textarea name="message" required placeholder="Sorununuzu detaylı yazın..."></textarea></label>
        <button class="ao-support-submit" type="submit">Canlı Sohbet Baslat</button>
      </form>
    </section>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php if($aoSharedFooterShowSupportWidget && $aoWidgetEnabled && $aoWidgetAllowedByBuilder): ?>
<div class="mobile-support-panel" data-mobile-support-panel hidden>
  <?php if($aoSupportSearch): ?><button type="button" data-support-open data-mobile-tab="search">Bilgi Bankasında Ara</button><?php endif; ?>
  <?php if($aoSupportAi): ?><button type="button" data-support-open data-mobile-tab="ai">AI Destek</button><?php endif; ?>
  <?php if($aoSupportLive): ?><button type="button" data-support-open data-mobile-tab="live">Canlı Sohbet</button><?php endif; ?>
  <?php if($aoSupportWhats && $aoWhatsActive): ?><a target="_blank" rel="noopener" href="https://wa.me/<?= e($aoSupportWhats) ?>">WhatsApp</a><?php elseif($aoSupportWhats): ?><span>WhatsApp mesai disinda</span><?php endif; ?>
  <?php if($aoSupportPhone): ?><a href="tel:<?= e($aoSupportPhone) ?>">Ara</a><?php endif; ?>
  <?php if($aoSupportTicket): ?><a href="<?= url('client/support') ?>">Ticket Aç</a><?php endif; ?>
</div>
<?php endif; ?>
<?php if(($aoSharedFooterShowSupportWidget && $aoWidgetEnabled && $aoWidgetAllowedByBuilder) || $aoSharedFooterShowMobileNav): ?>
<script defer src="<?= assetv('js/front/support-widget-pro.js') ?>"></script>
<?php endif; ?>
<?php if($aoSharedFooterShowBuilderFab && function_exists('current_admin') && current_admin()): ?>
<link rel="stylesheet" href="<?= assetv('css/builder-launcher.css') ?>">
<div class="ao-floating-edit ao-builder-fab ao-builder-launcher ao-builder-launcher--site">
  <details>
    <summary>
      <span class="ao-builder-launcher__icon">✎</span>
      <span class="ao-builder-launcher__text"><b>Sayfayı Düzenle</b><small>Canlı Builder</small></span>
    </summary>
    <div class="ao-floating-edit-menu ao-builder-launcher__panel">
      <div class="ao-builder-launcher__head">
        <small><?= e(strtoupper(($aoBuilderCtx['target'] ?? 'site').' / '.($aoBuilderCtx['template'] ?? 'home'))) ?></small>
        <strong>Görsel düzenleme araçları</strong>
      </div>
      <a href="<?= url('admin/builder-pro?target=site&template='.urlencode($aoBuilderCtx['template'] ?? 'home').'&mode=inline') ?>" class="ao-inline-edit-start"><b>Canlı Düzenle</b><span>Sayfadaki bloklara tıklayıp doğrudan düzenleyin.</span></a>
      <a href="<?= url('admin/builder-pro?target=site&template='.urlencode($aoBuilderCtx['template'] ?? 'home')) ?>"><b>Blok Builder</b><span>Hero, ürün, SSS, footer ve içerik bloklarını yönetin.</span></a>
      <a href="<?= url('admin/builder-pro?target=site&template='.urlencode($aoBuilderCtx['template'] ?? 'home').'&device=mobile') ?>"><b>Mobil Görünüm</b><span>Telefon görünümündeki düzeni kontrol edin.</span></a>
      <a href="<?= url('admin/support/widget-settings') ?>"><b>Sağ İkonlar</b><span>Destek, WhatsApp, telefon ve AI ikonlarını ayarlayın.</span></a>
    </div>
  </details>
</div>
<script>
window.AHOST_INLINE_BUILDER = {
  baseUrl: <?= json_encode(rtrim(url(''), '/'), JSON_UNESCAPED_SLASHES) ?>,
  csrf: <?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>,
  target: <?= json_encode($aoBuilderCtx['target'] ?? 'site', JSON_UNESCAPED_SLASHES) ?>,
  template: <?= json_encode($aoBuilderCtx['template'] ?? 'home', JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script defer src="<?= assetv('js/inline-builder.js') ?>"></script>
<?php endif; ?>
<?php if(!$aoIsAdminFooterContext): ?>
<link rel="stylesheet" href="<?= assetv('css/cookie-consent.css') ?>">
<div class="ao-cookie-consent" data-cookie-consent hidden>
  <div>
    <strong>Çerezleri ve site kullanım analizini yönetiyoruz</strong>
    <p>Deneyimi iyileştirmek, en çok bakılan ürünleri ve ihtiyaçları anlamak için anonim kullanım verileri toplarız. Zorunlu çerezler oturum ve güvenlik için kullanılır.</p>
  </div>
  <div class="ao-cookie-consent__actions">
    <a href="<?= url('cerez-politikasi') ?>">Politika</a>
    <button type="button" data-cookie-reject>Reddet</button>
    <button type="button" data-cookie-accept>Kabul et</button>
  </div>
</div>
<script defer src="<?= assetv('js/front/cookie-consent.js') ?>"></script>
<?php endif; ?>
<?php
$aoAiLauncherContext = $aoIsAdminFooterContext ? 'admin' : ((function_exists('current_customer') && current_customer()) ? 'customer' : 'site');
$aoAiLauncherIsAdminPreview = !$aoIsAdminFooterContext && function_exists('current_admin') && current_admin();
$aoAiLauncherEndpoint = match ($aoAiLauncherContext) {
  'admin' => url('admin/assistant/run-json'),
  'customer' => url('client/assistant/run'),
  default => url('assistant/run'),
};
$aoAiLauncherTitle = $aoAiLauncherContext === 'admin' ? 'Admin AI Yardımcı' : ($aoAiLauncherContext === 'customer' ? 'Müşteri AI Yardımcı' : 'AI Yardımcı');
$aoAiLauncherPlaceholder = $aoAiLauncherContext === 'admin'
  ? 'Örn: Ürün fiyatlandırmasını aç, site builder paketlerini göster, SEO ayarına git...'
  : 'Örn: Hosting paketi seçmeme yardım et, domain sorgula, site builder ile site oluştur...';
?>
<link rel="stylesheet" href="<?= assetv('css/ai-launcher.css') ?>">
<div class="ao-ai-launcher ao-ai-launcher--<?= e($aoAiLauncherContext) ?> <?= $aoAiLauncherIsAdminPreview ? 'ao-ai-launcher--site-admin-preview' : '' ?>" data-ai-launcher data-endpoint="<?= e($aoAiLauncherEndpoint) ?>" data-csrf="<?= e(csrf_token()) ?>">
  <button type="button" class="ao-ai-launcher__button" data-ai-launcher-open aria-expanded="false" aria-controls="ao-ai-launcher-panel">
    <span>AI</span>
    <b><?= e($aoAiLauncherContext === 'admin' ? 'Yardımcı' : 'Yardım') ?></b>
  </button>
  <section class="ao-ai-launcher__panel" id="ao-ai-launcher-panel" data-ai-launcher-panel hidden>
    <div class="ao-ai-launcher__head">
      <div>
        <small><?= e($aoAiLauncherContext === 'admin' ? 'Yönetim asistanı' : 'Hızlı destek asistanı') ?></small>
        <strong><?= e($aoAiLauncherTitle) ?></strong>
      </div>
      <button type="button" data-ai-launcher-close aria-label="Kapat">&times;</button>
    </div>
    <form data-ai-launcher-form>
      <label>
        <span>Ne yapmak istiyorsunuz?</span>
        <textarea name="prompt" rows="4" required placeholder="<?= e($aoAiLauncherPlaceholder) ?>"></textarea>
      </label>
      <button type="submit">Sor</button>
    </form>
    <div class="ao-ai-launcher__quick">
      <?php if($aoAiLauncherContext === 'admin'): ?>
        <button type="button" data-ai-prompt="Ürün paketlerini ve fiyatlandırmayı aç">Ürünler</button>
        <button type="button" data-ai-prompt="Site Builder AI paketlerini göster">Builder</button>
        <button type="button" data-ai-prompt="SEO ve site araçları ayarlarına git">SEO</button>
      <?php else: ?>
        <button type="button" data-ai-prompt="Hosting paketi seçmeme yardım et">Hosting</button>
        <button type="button" data-ai-prompt="Domain sorgulamak istiyorum">Domain</button>
        <button type="button" data-ai-prompt="AI ile site oluşturmak istiyorum">Site Builder</button>
      <?php endif; ?>
    </div>
    <div class="ao-ai-launcher__result" data-ai-launcher-result><p>Kısa sorunuzu yazın; ilgili sayfa ve işlem önerilerini hazırlayayım.</p></div>
  </section>
</div>
<script defer src="<?= assetv('js/ai-launcher.js') ?>"></script>










