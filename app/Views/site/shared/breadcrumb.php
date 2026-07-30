<?php
$items = $items ?? ($breadcrumb ?? []);
if (is_string($items)) {
  echo '<nav class="ao-breadcrumb">'.$items.'</nav>';
  return;
}
?>
<?php if(!empty($items)): ?>
<nav class="ao-breadcrumb" aria-label="Sayfa yolu">
  <?php foreach($items as $i=>$item): $label=$item['label'] ?? ($item[0] ?? ''); $href=$item['href'] ?? ($item[1] ?? null); ?>
    <?php if($i>0): ?><span>/</span><?php endif; ?>
    <?php if($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><strong><?= e($label) ?></strong><?php endif; ?>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
