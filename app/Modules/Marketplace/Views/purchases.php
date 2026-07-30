<?php $view->extend('layouts.public'); $view->section('content'); ?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Satın Aldıklarım</h1><p>Marketplace dijital ürün indirmeleri.</p></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-card" style="padding:0;overflow:auto">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr><th style="padding:12px;text-align:left">Ürün</th><th style="padding:12px">Durum</th><th style="padding:12px">Tutar</th><th style="padding:12px"></th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px"><strong><?= e($p['title'] ?? '-') ?></strong><br><small><?= e($p['slug'] ?? '') ?></small></td>
                    <td style="padding:12px"><?= e($p['status']) ?></td>
                    <td style="padding:12px"><?= number_format((float)$p['amount'], 2) ?> <?= e($p['currency']) ?></td>
                    <td style="padding:12px">
                        <?php if (($p['status'] ?? '') === 'paid'): ?>
                            <form method="post" action="/marketplace/satin-aldiklarim/<?= (int)$p['id'] ?>/token" data-mp-token-form>
                                <?= csrf() ?>
                                <button class="aho-btn aho-btn--sm aho-btn--primary">İndirme Linki Oluştur</button>
                            </form>
                        <?php else: ?>
                            Ödeme bekleniyor
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$purchases): ?><tr><td colspan="4" style="padding:32px;text-align:center">Henüz satın alma yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></section>
<script>
document.querySelectorAll('[data-mp-token-form]').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const res = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
    const data = await res.json();
    if (data.ok && data.url) window.location.href = data.url;
    else alert(data.error || 'İndirme linki oluşturulamadı.');
  });
});
</script>
<?php $view->endSection(); ?>
