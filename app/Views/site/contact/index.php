<?php
$title = admin_setting('contact_page_title','İletişim');
$subtitle = admin_setting('contact_page_subtitle','Sorularınız, teklif talepleriniz ve destek öncesi görüşmeleriniz için bize ulaşın.');
$email = admin_setting('contact_email', admin_setting('company_email','iletisim@example.com'));
$phone = admin_setting('contact_phone', admin_setting('company_phone','+90'));
$whatsapp = admin_setting('contact_whatsapp','');
$address = admin_setting('contact_address', admin_setting('footer_contact_address',''));
$hours = admin_setting('contact_working_hours','Hafta içi 09:00 - 18:00');
$map = trim((string)admin_setting('contact_map_embed',''));
$formEnabled = admin_setting('contact_form_enabled','1') === '1';
$mapHtml = '';
if ($map !== '') {
    if (stripos($map, '<iframe') !== false) {
        $mapHtml = $map;
    } else {
        $mapSrc = filter_var($map, FILTER_VALIDATE_URL) ? $map : ('https://www.google.com/maps?q='.rawurlencode($map).'&output=embed');
        $mapHtml = '<iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="'.e($mapSrc).'" title="Google Haritalar"></iframe>';
    }
} elseif (trim((string)$address) !== '') {
    $mapSrc = 'https://www.google.com/maps?q='.rawurlencode((string)$address).'&output=embed';
    $mapHtml = '<iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="'.e($mapSrc).'" title="Google Haritalar"></iframe>';
}
?>
<?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('iletisim', ['title'=>$title]) : ''; ?>
<?php if ($managedHero): ?><?= $managedHero ?><?php else: ?>
<section class="hero-lite contact-hero"><div class="container"><span class="eyebrow">Ahost One</span><h1><?= e($title) ?></h1><p><?= e($subtitle) ?></p></div></section>
<?php endif; ?>
<section class="section contact-page">
  <div class="container grid-2">
    <div class="card contact-info-card">
      <h2>İletişim Bilgileri</h2>
      <p class="muted">Satış öncesi, domain, hosting, lisanslama ve teknik destek konularında bize ulaşabilirsiniz.</p>
      <div class="info-list">
        <p><strong>E-posta</strong><br><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
        <p><strong>Telefon</strong><br><a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a></p>
        <?php if($whatsapp): ?><p><strong>WhatsApp</strong><br><a href="https://wa.me/<?= e(preg_replace('/\D/','',$whatsapp)) ?>" target="_blank" rel="noopener"><?= e($whatsapp) ?></a></p><?php endif; ?>
        <p><strong>Adres</strong><br><?= nl2br(e($address ?: '-')) ?></p>
        <p><strong>Çalışma Saatleri</strong><br><?= e($hours) ?></p>
      </div>
      <div class="action-row"><a class="btn btn-primary" href="<?= url(admin_setting('contact_support_link','client/support')) ?>">Destek Talebi Aç</a><a class="btn btn-soft" href="<?= url('bilgi-bankasi') ?>">Bilgi Bankası</a></div>
    </div>
    <div class="card contact-form-card">
      <?php if($formEnabled): ?>
      <h2>Bize Yazın</h2>
      <form method="post" action="<?= url('contact/send') ?>" class="site-form">
        <?= csrf_field() ?>
        <label>Ad Soyad<input name="name" required></label>
        <label>E-posta<input type="email" name="email" required></label>
        <label>Konu<input name="subject" required></label>
        <label>Mesaj<textarea name="message" rows="5" required></textarea></label>
        <button class="btn btn-primary" type="submit">Mesajı Gönder</button>
      </form>
      <?php else: ?>
        <h2>İletişim Formu Kapalı</h2><p class="muted">Form geçici olarak kapalı. E-posta veya telefon üzerinden ulaşabilirsiniz.</p>
      <?php endif; ?>
    </div>
  </div>
  <?php if($mapHtml): ?>
    <div class="container">
      <div class="card contact-map contact-map-card">
        <div class="contact-map-head">
          <div>
            <h2>Adres ve Harita</h2>
            <p class="muted"><?= e($address ?: 'Konumumuzu Google Haritalar üzerinden inceleyebilirsiniz.') ?></p>
          </div>
          <?php if($address): ?><a class="btn btn-soft" href="https://www.google.com/maps/search/?api=1&query=<?= e(rawurlencode((string)$address)) ?>" target="_blank" rel="noopener">Haritada Aç</a><?php endif; ?>
        </div>
        <div class="contact-map-frame"><?= $mapHtml ?></div>
      </div>
    </div>
  <?php endif; ?>
</section>
