<?php $tag=$tag ?? 'article'; $class=trim('ao-content-card '.($class ?? '')); ?>
<<?= $tag ?> class="<?= e($class) ?>">
  <?php if(!empty($image)): ?><img src="<?= e($image) ?>" alt="<?= e($imageAlt ?? ($title ?? '')) ?>"><?php endif; ?>
  <?php if(!empty($badge)): ?><span class="ao-content-badge"><?= e($badge) ?></span><?php endif; ?>
  <?php if(!empty($meta)): ?><div class="ao-content-meta"><?= is_array($meta) ? e(implode(' • ', array_filter($meta))) : $meta ?></div><?php endif; ?>
  <?php if(!empty($title)): ?><h3><?= e($title) ?></h3><?php endif; ?>
  <?php if(!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
  <?= $content ?? '' ?>
  <?php if(!empty($href)): ?><div class="ao-content-actions"><a class="ao-content-btn secondary" href="<?= e($href) ?>"><?= e($linkText ?? 'Devamını Oku') ?></a></div><?php endif; ?>
</<?= $tag ?>>
