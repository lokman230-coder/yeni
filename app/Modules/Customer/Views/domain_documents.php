<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <a href="/panel/domain/<?= (int)$domain['id'] ?>" style="color:#6b7280;font-size:13px;text-decoration:none">← <?= e($domain['domain_name']) ?></a>
                <h1 style="margin:8px 0 16px;font-size:24px">📄 Belgeler — <?= e($domain['domain_name']) ?></h1>

                <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <div class="aho-card" style="padding:20px;margin-bottom:16px;background:#fef3c7;border-left:4px solid #f59e0b">
                    <strong>ℹ️ Neden belge?</strong> <code>.<?= e($tld) ?></code> uzantısı için TR Nic ve/veya registrar kuralları gereği kimlik/vergi/marka belgesi zorunludur. Belgeler onaylandıktan sonra domain kaydı tamamlanır.
                </div>

                <?php foreach ($requiredDocs as $docType):
                    $existing = array_filter($documents, fn($d) => $d['document_type'] === $docType);
                    $doc = $existing ? reset($existing) : null;
                    $label = \App\Services\Domain\TldPricingService::documentLabel($docType);
                ?>
                    <div class="aho-card" style="padding:20px;margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                            <h3 style="margin:0;font-size:16px">📎 <?= e($label) ?></h3>
                            <?php if ($doc): ?>
                                <span style="padding:4px 10px;border-radius:10px;font-size:12px;color:#fff;background:<?= match($doc['status']) { 'approved' => '#059669', 'rejected' => '#dc2626', default => '#d97706' } ?>">
                                    <?= match($doc['status']) { 'approved' => '✓ Onaylandı', 'rejected' => '✗ Reddedildi', default => '⏳ İnceleniyor' } ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#dc2626;font-size:12px;font-weight:600">⚠ Bekleniyor</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($doc && $doc['status'] !== 'rejected'): ?>
                            <div style="font-size:13px;color:#6b7280">
                                <p><strong>Numara:</strong> <?= e($doc['document_number'] ?? '—') ?></p>
                                <?php if ($doc['file_name']): ?><p><strong>Dosya:</strong> <?= e($doc['file_name']) ?></p><?php endif; ?>
                                <p><strong>Yüklenme:</strong> <?= e(date('d.m.Y H:i', strtotime((string)$doc['created_at']))) ?></p>
                                <?php if ($doc['notes']): ?><p><strong>Not:</strong> <?= e($doc['notes']) ?></p><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($doc && $doc['status'] === 'rejected'): ?>
                                <div style="padding:10px;background:#fee2e2;border-radius:6px;color:#991b1b;font-size:13px;margin-bottom:12px">
                                    <strong>Reddedildi:</strong> <?= e($doc['notes'] ?? 'Belge geçersiz — tekrar yükleyin.') ?>
                                </div>
                            <?php endif; ?>
                            <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/belge-yukle" enctype="multipart/form-data">
                                <?= csrf() ?>
                                <input type="hidden" name="document_type" value="<?= e($docType) ?>">
                                <?php if (in_array($docType, ['tckn','tax_id'], true)): ?>
                                    <label>Numara *</label>
                                    <input type="text" name="document_number" required placeholder="<?= $docType === 'tckn' ? '11 haneli TCKN' : '10 haneli vergi no' ?>" style="width:100%;margin-bottom:12px">
                                <?php endif; ?>
                                <label>Belge (PDF/JPG/PNG — max 5MB)</label>
                                <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" style="width:100%;margin-bottom:12px">
                                <button type="submit" class="aho-btn aho-btn--primary">📤 Yükle</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
