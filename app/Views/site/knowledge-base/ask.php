<?php
require_once __DIR__ . '/../shared/content-renderer.php';
$question = trim((string)($question ?? ($_GET['q'] ?? '')));
$result = $result ?? null;
$queueId = (int)($result['queue_id'] ?? 0);
ob_start();
?>
<section class="ao-content-panel">
  <form method="get" action="<?= url('bilgi-bankasi/ask') ?>">
    <label><strong>Sorunuzu yazın</strong>
      <input class="ao-kb-search-input compact" name="q" value="<?= e($question) ?>" placeholder="Örn: EPP kodu nedir?">
    </label>
    <button class="ao-btn ao-kb-search-btn">Sor</button>
  </form>
</section>
<?php if($result): ?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong><?= ($result['mode'] ?? '') === 'local' ? 'Bilgi Bankası Cevabı' : 'AI Yanıtı' ?></strong></div>
  <div class="ao-kb-answer"><?= e($result['answer'] ?? '') ?></div>
  <?php if($queueId): ?>
  <div class="ao-content-actions ao-kb-feedback" data-kb-feedback data-queue-id="<?= $queueId ?>">
    <span><strong>Bu yanıt yeterli mi?</strong></span>
    <button type="button" class="ao-content-btn" data-value="yes">Evet</button>
    <button type="button" class="ao-content-btn secondary" data-value="no">Hayır</button>
    <span class="ao-kb-feedback-status" data-feedback-status></span>
  </div>
  <?php endif; ?>
  <?php if(!empty($result['sources'])): ?>
    <hr><strong>Kaynaklar</strong>
    <ul>
      <?php foreach($result['sources'] as $src): ?>
        <li><a rel="nofollow" target="_blank" href="<?= e($src['url'] ?? '#') ?>"><?= e($src['title'] ?? ($src['url'] ?? 'Kaynak')) ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php endif; ?>
<script>
document.querySelectorAll('[data-kb-feedback]').forEach((box) => {
  box.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-value]');
    if (!button) return;
    const status = box.querySelector('[data-feedback-status]');
    status.textContent = 'Kaydediliyor...';
    const body = new URLSearchParams({ id: box.dataset.queueId || '', value: button.dataset.value || '' });
    try {
      const response = await fetch('<?= url('bilgi-bankasi/feedback') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });
      const result = await response.json();
      status.textContent = result.ok ? 'Teşekkürler, geri bildiriminiz kaydedildi.' : (result.message || 'Kaydedilemedi.');
    } catch (error) {
      status.textContent = 'Bağlantı hatası.';
    }
  });
});
</script>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Bilgi Bankası Asistanı',
  'kicker'=>'Akıllı Arama',
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Bilgi Bankası','href'=>url('bilgi-bankasi')],['label'=>'Soru Sor']]
]);
