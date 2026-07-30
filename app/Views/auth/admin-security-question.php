<?php
$id = (int)($_SESSION['admin_reset_id'] ?? 0);
$admin = null;
if ($id) { try { $s=db()->prepare('SELECT * FROM admins WHERE id=? LIMIT 1'); $s->execute([$id]); $admin=$s->fetch(); } catch(Throwable $e) {} }
if (!$admin) { flash('error','Şifre sıfırlama oturumu bulunamadı.'); redirect_to('admin/forgot-password'); }
$question = $admin['security_question'] ?? 'Güvenlik sorunuz nedir?';
?>
<h1>Admin Güvenlik Sorusu</h1>
<p><?= e($question) ?></p>
<form method="post" action="<?= url('admin/security-question') ?>">
  <?= csrf_field() ?>
  <label>Cevap</label>
  <input type="text" name="security_answer" required autocomplete="off">
  <button type="submit">Doğrula</button>
</form>
<div class="auth-links"><a href="<?= url('admin/forgot-password') ?>">Baştan Başla</a></div>
