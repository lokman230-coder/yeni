<?php $id = $id ?? 'unknown'; ?><!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bir Hata Oluştu — Ahost Bilişim</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/components/buttons.css">
    <style>
        .aho-error-page{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:var(--aho-space-8)}
        .aho-error-page__code{font-size:6rem;font-weight:700;line-height:1;color:var(--aho-color-danger)}
        .aho-error-page__title{font-size:var(--aho-text-2xl);margin:var(--aho-space-4) 0 var(--aho-space-2)}
        .aho-error-page__text{color:var(--aho-color-ink-500);margin-bottom:var(--aho-space-6)}
        .aho-error-page__ref{font-family:var(--aho-font-mono);font-size:var(--aho-text-sm);color:var(--aho-color-ink-400);margin-top:var(--aho-space-4)}
    </style>
</head>
<body>
<div class="aho-error-page">
    <div>
        <div class="aho-error-page__code">500</div>
        <h1 class="aho-error-page__title">Bir hata oluştu</h1>
        <p class="aho-error-page__text">İşleminiz sırasında beklenmedik bir sorun oluştu. Tekrar denemenizi rica ederiz.</p>
        <a href="/" class="aho-btn aho-btn--primary aho-btn--lg">Ana Sayfaya Dön</a>
        <div class="aho-error-page__ref">Referans: err_<?= e($id) ?></div>
    </div>
</div>
</body>
</html>
