<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$tabs = [
    'api'      => '🔌 API',
    'audit'    => '📋 Denetim',
    'cron'     => '⏰ Cron',
    'mail'     => '✉️ Mail',
    'ai'       => '🤖 AI',
    'activity' => '👤 Admin Aktivite',
    'app'      => '📝 Uygulama (bugün)',
];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Log Merkezi</h1>
            <p>Sistem, API, cron, mail ve AI logları.</p>
        </div>
    </div>

    <div style="display:flex;gap:6px;margin-bottom:var(--aho-space-4);flex-wrap:wrap">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="/admin/loglar?type=<?= e($key) ?>"
               class="aho-btn aho-btn--sm <?= $key === $type ? 'aho-btn--primary' : 'aho-btn--outline' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="aho-card" style="padding:0;overflow:hidden">
        <?php if ($type === 'app'): ?>
            <?php if ($app_log === ''): ?>
                <div class="aho-empty-state" style="padding:var(--aho-space-8)">
                    <div class="aho-empty-state__icon" style="font-size:48px">✅</div>
                    <div class="aho-empty-state__title">Bugün log yok</div>
                    <div class="aho-empty-state__text">Sistemde hata veya uyarı kaydedilmedi.</div>
                </div>
            <?php else: ?>
                <pre class="aho-tool-code" style="max-height:600px;margin:0"><?= e($app_log) ?></pre>
            <?php endif; ?>
        <?php elseif (empty($data)): ?>
            <div class="aho-empty-state" style="padding:var(--aho-space-8)">
                <div class="aho-empty-state__icon" style="font-size:48px">📭</div>
                <div class="aho-empty-state__title">Bu logda kayıt yok</div>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="aho-admin-table">
                    <thead>
                        <tr>
                            <?php foreach (array_keys($data[0]) as $col): ?>
                                <th><?= e($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($row as $val): ?>
                                    <td style="font-family:monospace;font-size:var(--aho-text-xs);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                        title="<?= e((string)$val) ?>"><?= e(mb_substr((string)$val, 0, 100)) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
