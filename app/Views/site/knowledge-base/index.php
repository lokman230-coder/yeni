<?php
require_once __DIR__ . '/../shared/content-renderer.php';
ao_v23_ensure_schema();
try{
  $articles=db()->query("SELECT * FROM knowledge_articles WHERE audience='customer' AND status IN ('published','draft') ORDER BY category,title LIMIT 200")->fetchAll();
}catch(Throwable $e){$articles=[];}
ob_start();
$grouped=[]; foreach($articles as $a){ $grouped[$a['category'] ?: 'Genel'][]=$a; }
?>
<section class="ao-content-panel">
  <form method="get" action="<?= url('bilgi-bankasi/ask') ?>">
    <div class="ao-content-meta"><strong>Akıllı Bilgi Bankası Araması</strong></div>
    <input class="ao-kb-search-input" name="q" placeholder="Sorunuzu yazın: Örn. Domain transferi için EPP kodu nedir?">
    <button class="ao-btn ao-kb-search-btn">Sor</button>
  </form>
</section>
<?php if($grouped): ?>
  <?php foreach($grouped as $cat=>$rows): ?>
  <section class="ao-content-panel">
    <div class="ao-content-meta"><strong><?= e($cat) ?></strong><span>•</span><span><?= count($rows) ?> makale</span></div>
    <?= ao_site_content_grid($rows, [
      'type'=>'knowledge-base',
      'badge_key'=>'category',
      'href'=>fn($i)=>url('bilgi-bankasi/'.$i['slug']),
      'link_text'=>'Oku',
      'empty_title'=>'Henüz makale yok'
    ]) ?>
  </section>
  <?php endforeach; ?>
<?php else: ?>
  <?= ao_site_content_grid([], ['empty_title'=>'Henüz makale yok','empty_text'=>'Bilgi bankası içerikleri hazırlandığında bu alanda listelenecek.']) ?>
<?php endif; ?>
<?php
$content=ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Ahost One Akademi',
  'kicker'=>'Bilgi Bankası',
  'summary'=>'Hosting, domain, mail, VPS ve sunucu işlemleri için sade rehberler ve pratik cevaplar.',
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Bilgi Bankası']]
]);
