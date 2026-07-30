<section class="ao-content-cta <?= e($class ?? '') ?>">
  <?php if(!empty($title)): ?><h2><?= e($title) ?></h2><?php endif; ?>
  <?php if(!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
  <?= $content ?? '' ?>
  <?php if(!empty($actions) && is_array($actions)): ?><div class="ao-content-actions"><?php foreach($actions as $a): ?><a class="ao-content-btn <?= !empty($a['secondary'])?'secondary':'' ?>" href="<?= e($a['href'] ?? '#') ?>"><?= e($a['label'] ?? 'Detay') ?></a><?php endforeach; ?></div><?php endif; ?>
</section>
