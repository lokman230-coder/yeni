<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $wrapper = fn($body) => '<div style="font-family:Inter,sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#f8fafc">
<div style="background:#fff;padding:32px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
<div style="text-align:center;margin-bottom:24px">
<img src="{{app_url}}/assets/img/logo-icon.png" alt="" width="48" height="48" style="border-radius:8px">
<h1 style="color:#0c4a6e;margin:12px 0 0">Ahost <span style="color:#06b6d4">One</span></h1>
</div>
' . $body . '
<div style="margin-top:24px;padding-top:24px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;text-align:center">
© ' . date('Y') . ' Ahost Bilişim · <a href="{{app_url}}" style="color:#0284c7">ahost.web.tr</a>
</div>
</div>
</div>';

        $items = [
            [
                'template_key' => 'customer_welcome',
                'subject'      => 'Ahost Bilişim\'a hoş geldiniz, {{name}}!',
                'body_html'    => $wrapper('
<h2>Aramıza hoş geldiniz 👋</h2>
<p>Merhaba <b>{{name}}</b>,</p>
<p>Ahost Bilişim ailesine katıldığınız için teşekkürler. Hesabınız aktif; hemen giriş yapıp hizmetlerimize göz atabilirsiniz.</p>
<p style="text-align:center;margin:24px 0"><a href="{{app_url}}/giris" style="background:#0284c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Panele Git</a></p>
<p>Herhangi bir sorunuz olursa <a href="{{app_url}}/destek">destek merkezimizden</a> ulaşabilirsiniz.</p>'),
                'variables' => ['name','app_url'],
            ],
            [
                'template_key' => 'order_received',
                'subject'      => 'Sipariş alındı: {{order_number}}',
                'body_html'    => $wrapper('
<h2>Siparişiniz alındı ✅</h2>
<p>Merhaba {{name}},</p>
<p>Sipariş numaranız: <b>{{order_number}}</b></p>
<p>Toplam: <b>{{total}}</b></p>
<p>Ödeme onaylandığında hizmetiniz otomatik olarak aktifleştirilecektir.</p>
<p style="text-align:center;margin:24px 0"><a href="{{app_url}}/panel" style="background:#0284c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Siparişimi Gör</a></p>'),
                'variables' => ['name','order_number','total','app_url'],
            ],
            [
                'template_key' => 'payment_success',
                'subject'      => 'Ödemeniz alındı: {{invoice_number}}',
                'body_html'    => $wrapper('
<h2>Ödemeniz onaylandı 🎉</h2>
<p>Merhaba {{name}},</p>
<p>Fatura <b>{{invoice_number}}</b> için ödemeniz başarıyla alındı.</p>
<p>Tutar: <b>{{amount}}</b></p>
<p>Hizmetiniz aktifleştirildi.</p>'),
                'variables' => ['name','invoice_number','amount','app_url'],
            ],
            [
                'template_key' => 'invoice_created',
                'subject'      => 'Yeni faturanız oluştu: {{invoice_number}}',
                'body_html'    => $wrapper('
<h2>Faturanız hazır 📄</h2>
<p>Merhaba {{name}},</p>
<p>Fatura numarası: <b>{{invoice_number}}</b></p>
<p>Tutar: <b>{{total}}</b> · Son ödeme tarihi: <b>{{due_date}}</b></p>
<p style="text-align:center;margin:24px 0"><a href="{{app_url}}/panel" style="background:#0284c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Faturayı Öde</a></p>'),
                'variables' => ['name','invoice_number','total','due_date','app_url'],
            ],
            [
                'template_key' => 'service_active',
                'subject'      => 'Hizmetiniz aktif: {{service_name}}',
                'body_html'    => $wrapper('
<h2>Hizmetiniz kullanıma hazır ⚡</h2>
<p>Merhaba {{name}},</p>
<p><b>{{service_name}}</b> hizmetiniz başarıyla aktifleştirildi.</p>
<p>Hesap bilgileriniz e-posta ile paylaşıldı. Güvenlik gerekçesiyle ilk girişte şifrenizi değiştirmenizi öneririz.</p>'),
                'variables' => ['name','service_name','app_url'],
            ],
            [
                'template_key' => 'domain_renewal_reminder',
                'subject'      => 'Domain yenileme: {{domain}}',
                'body_html'    => $wrapper('
<h2>Domain süreniz yaklaşıyor ⏰</h2>
<p>Merhaba {{name}},</p>
<p><b>{{domain}}</b> domaininizin süresi <b>{{expiry}}</b> tarihinde dolacak.</p>
<p>Domain kaybını önlemek için lütfen zamanında yenileyin.</p>
<p style="text-align:center;margin:24px 0"><a href="{{app_url}}/panel" style="background:#0284c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Şimdi Yenile</a></p>'),
                'variables' => ['name','domain','expiry','app_url'],
            ],
            [
                'template_key' => 'ticket_reply',
                'subject'      => 'Destek talebine yanıt geldi: {{ticket_number}}',
                'body_html'    => $wrapper('
<h2>Yeni yanıt 💬</h2>
<p>Merhaba {{name}},</p>
<p><b>{{ticket_number}}</b> — <b>{{subject}}</b> talebinize yeni yanıt geldi.</p>
<p style="text-align:center;margin:24px 0"><a href="{{app_url}}/panel/destek/{{ticket_id}}" style="background:#0284c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Yanıtı Gör</a></p>'),
                'variables' => ['name','ticket_number','ticket_id','subject','app_url'],
            ],
            [
                'template_key' => 'password_reset',
                'subject'      => 'Şifre sıfırlama isteği',
                'body_html'    => $wrapper('
<h2>Şifre Sıfırlama</h2>
<p>Merhaba,</p>
<p>Şifre sıfırlama isteğinde bulundunuz. Aşağıdaki bağlantı 30 dakika içinde geçerlidir:</p>
<p style="text-align:center;margin:24px 0"><a href="{{reset_url}}" style="background:#0284c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Şifremi Sıfırla</a></p>
<p style="color:#64748b;font-size:12px">Bu isteği siz yapmadıysanız görmezden gelebilirsiniz.</p>'),
                'variables' => ['reset_url'],
            ],
            [
                'template_key' => 'mfa_code',
                'subject'      => 'Doğrulama kodunuz: {{code}}',
                'body_html'    => $wrapper('
<h2>Doğrulama Kodu</h2>
<p>Kodunuz: <span style="display:inline-block;font-size:32px;font-weight:700;background:#f1f5f9;padding:12px 24px;border-radius:8px;letter-spacing:4px">{{code}}</span></p>
<p style="color:#64748b;font-size:12px">Bu kod 5 dakika içinde geçerlidir. Kimseyle paylaşmayın.</p>'),
                'variables' => ['code'],
            ],
        ];

        foreach ($items as $tpl) {
            $exists = Connection::selectOne("SELECT id FROM email_templates WHERE template_key = ?", [$tpl['template_key']]);
            $data = [
                'template_key' => $tpl['template_key'],
                'subject'      => $tpl['subject'],
                'body_html'    => $tpl['body_html'],
                'body_text'    => strip_tags(str_replace(['<br>', '</p>'], "\n", $tpl['body_html'])),
                'variables'    => json_encode($tpl['variables'], JSON_UNESCAPED_UNICODE),
                'is_active'    => 1,
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
            if ($exists) {
                Connection::update('email_templates', $data, 'id = ?', [$exists['id']]);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                Connection::insert('email_templates', $data);
            }
        }
    }
};
