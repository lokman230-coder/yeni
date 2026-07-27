<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-12) var(--aho-space-8)">
    <div class="aho-container">
        <h1>Domain Sorgulama</h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
            300+ uzantı · WHOIS · DNS · SSL · Değerleme
        </p>
        <form class="aho-home-search" action="/domain" method="get" style="margin-top:var(--aho-space-6)">
            <input type="text" name="q" class="aho-home-search__input"
                   placeholder="ornekdomain.com" value="<?= e($q) ?>" required autofocus>
            <button type="submit" class="aho-btn aho-btn--accent aho-btn--lg">Sorgula</button>
        </form>
    </div>
</section>

<?php if ($result): ?>
<section class="aho-pages-body">
    <div class="aho-container" style="max-width:1000px">

        <!-- Ana sonuç -->
        <div class="aho-tool-domain-main <?= !empty($result['main']['available']) ? 'aho-tool-domain-main--available' : 'aho-tool-domain-main--taken' ?>">
            <div class="aho-tool-domain-main__head">
                <div>
                    <div class="aho-tool-domain-main__name"><?= e($result['query']) ?></div>
                    <div style="margin-top:var(--aho-space-2)">
                        <?php if (!empty($result['main']['available'])): ?>
                            <span class="aho-tool-status aho-tool-status--ok">✓ Müsait</span>
                        <?php else: ?>
                            <span class="aho-tool-status aho-tool-status--warn">✗ Kayıtlı</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($result['main']['available'])): ?>
                    <div>
                        <?php if (!empty($result['main']['price'])): ?>
                            <div class="aho-tool-domain-main__price">
                                <?= number_format((float) $result['main']['price'], 2, ',', '.') ?>
                                <?= e($result['main']['currency'] ?? 'TRY') ?>
                            </div>
                        <?php endif; ?>
                        <a href="/sepet" class="aho-btn aho-btn--primary aho-btn--lg">🛒 Sepete Ekle</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($result['main']['available']) && $result['whois']): ?>
            <?php $w = $result['whois']; ?>
            <div class="aho-tool-result-grid">

                <!-- WHOIS -->
                <div class="aho-tool-card">
                    <div class="aho-tool-card__title">🔍 WHOIS Bilgileri</div>
                    <table class="aho-tool-table">
                        <?php
                        $rows = [
                            ['Registrar',      $w['registrar'] ?? null],
                            ['Kayıt Sahibi',   $w['registrant'] ?? null],
                            ['Oluşturma',      $w['created'] ?? null],
                            ['Son Güncelleme', $w['updated'] ?? null],
                            ['Bitiş Tarihi',   $w['expires'] ?? null],
                        ];
                        foreach ($rows as $r):
                            $val = $r[1];
                        ?>
                            <tr>
                                <th><?= e($r[0]) ?></th>
                                <td>
                                    <?php if ($val !== null && $val !== ''): ?>
                                        <?= e((string) $val) ?>
                                    <?php else: ?>
                                        <em style="color:var(--aho-color-ink-400)">Veri bulunamadı</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th>Transfer Koruması</th>
                            <td>
                                <?php if ($w['transfer_lock'] === true): ?>
                                    <span class="aho-tool-status aho-tool-status--ok">✓ Var (Kilitli)</span>
                                <?php elseif ($w['transfer_lock'] === false): ?>
                                    <span class="aho-tool-status aho-tool-status--warn">✗ Yok</span>
                                <?php else: ?>
                                    <em style="color:var(--aho-color-ink-400)">Veri bulunamadı</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>WHOIS Gizliliği</th>
                            <td>
                                <?php if ($w['whois_privacy'] === true): ?>
                                    <span class="aho-tool-status aho-tool-status--ok">✓ Aktif</span>
                                <?php elseif ($w['whois_privacy'] === false): ?>
                                    <span class="aho-tool-status aho-tool-status--warn">✗ Yok</span>
                                <?php else: ?>
                                    <em style="color:var(--aho-color-ink-400)">Veri bulunamadı</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($w['nameservers'])): ?>
                            <tr><th>Nameserver'lar</th><td><?= e(implode(', ', $w['nameservers'])) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- DNS -->
                <?php if ($result['dns']): ?>
                    <div class="aho-tool-card">
                        <div class="aho-tool-card__title">🌐 DNS Kayıtları</div>
                        <?php foreach ($result['dns'] as $type => $records): if (empty($records)) continue; ?>
                            <div class="aho-tool-dns__group">
                                <div class="aho-tool-dns__type"><?= e($type) ?></div>
                                <?php foreach ($records as $rec): ?>
                                    <div class="aho-tool-dns__record">
                                        <?php if (isset($rec['priority'])): ?><span class="aho-tool-dns__pri"><?= (int)$rec['priority'] ?></span><?php endif; ?>
                                        <span class="aho-tool-dns__value"><?= e((string)($rec['value'] ?? '')) ?></span>
                                        <?php if (!empty($rec['ttl'])): ?><small>TTL: <?= (int)$rec['ttl'] ?></small><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- SSL -->
                <?php if ($result['ssl']): $s = $result['ssl']; ?>
                    <div class="aho-tool-card">
                        <div class="aho-tool-card__title">🔒 SSL Sertifikası</div>
                        <?php if (!empty($s['active'])): ?>
                            <table class="aho-tool-table">
                                <tr><th>Durum</th><td><span class="aho-tool-status aho-tool-status--ok">✓ Aktif</span></td></tr>
                                <tr><th>Issuer</th><td><?= e($s['issuer'] ?? '—') ?></td></tr>
                                <tr><th>Common Name</th><td><?= e($s['subject_cn'] ?? '—') ?></td></tr>
                                <tr><th>Başlangıç</th><td><?= e($s['valid_from'] ?? '—') ?></td></tr>
                                <tr><th>Bitiş</th><td><?= e($s['valid_to'] ?? '—') ?></td></tr>
                                <tr>
                                    <th>Kalan Gün</th>
                                    <td>
                                        <?= (int) $s['days_left'] ?>
                                        <?php if (!empty($s['expires_soon'])): ?>
                                            <span class="aho-tool-status aho-tool-status--warn" style="margin-left:8px">Yakında dolacak</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <div class="aho-alert aho-alert--warning">SSL sertifikası bulunamadı veya süresi dolmuş.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Değerleme -->
                <?php if ($result['valuation']): $v = $result['valuation']; ?>
                    <div class="aho-tool-card aho-tool-result-grid--full">
                        <div class="aho-tool-card__title">💎 Domain Değerleme</div>

                        <div style="text-align:center;padding:var(--aho-space-4) 0">
                            <div class="aho-tool-valuation__amount">$<?= number_format($v['estimated_value_usd'], 0, ',', '.') ?></div>
                            <div class="aho-tool-valuation__label">Tahmini piyasa değeri</div>
                            <div class="aho-tool-valuation__potential">
                                Ticari Potansiyel: <strong><?= e($v['commercial_potential']) ?></strong>
                            </div>
                        </div>

                        <div class="aho-tool-scores">
                            <?php foreach ([
                                'TLD'     => $v['scores']['tld'],
                                'Uzunluk' => $v['scores']['length'],
                                'Marka'   => $v['scores']['brand'],
                                'Yaş'     => $v['scores']['age'],
                                'SEO'     => $v['scores']['seo'],
                            ] as $label => $score): ?>
                                <div class="aho-tool-score">
                                    <div class="aho-tool-score__label"><?= $label ?></div>
                                    <div class="aho-tool-score__bar">
                                        <div class="aho-tool-score__fill" style="width:<?= (int)$score ?>%"></div>
                                    </div>
                                    <div class="aho-tool-score__value"><?= (int)$score ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="aho-tool-score aho-tool-score--overall">
                            <div class="aho-tool-score__label">Genel Skor</div>
                            <div class="aho-tool-score__bar">
                                <div class="aho-tool-score__fill" style="width:<?= (int)$v['scores']['overall'] ?>%"></div>
                            </div>
                            <div class="aho-tool-score__value"><?= (int)$v['scores']['overall'] ?>/100</div>
                        </div>

                        <?php if (!empty($v['risks'])): ?>
                            <div class="aho-tool-suggestions">
                                <strong style="font-size:var(--aho-text-sm)">Risk Notları:</strong>
                                <ul>
                                    <?php foreach ($v['risks'] as $risk): ?>
                                        <li>• <?= e($risk) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Öneriler -->
        <?php if (!empty($result['suggestions'])): ?>
            <div class="aho-tool-card">
                <div class="aho-tool-card__title">💡 Alternatif Uzantılar</div>
                <div class="aho-tool-suggestion-grid">
                    <?php foreach ($result['suggestions'] as $name => $s): ?>
                        <div class="aho-tool-suggestion <?= !empty($s['available']) ? 'aho-tool-suggestion--available' : '' ?>">
                            <span class="aho-tool-suggestion__name"><?= e($name) ?></span>
                            <?php if (!empty($s['available'])): ?>
                                <span class="aho-tool-suggestion__status">✓ Müsait</span>
                                <a href="/sepet" class="aho-btn aho-btn--outline aho-btn--sm">Sepete Ekle</a>
                            <?php else: ?>
                                <span class="aho-tool-suggestion__status aho-tool-suggestion__status--taken">Kayıtlı</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php $view->endSection(); ?>
