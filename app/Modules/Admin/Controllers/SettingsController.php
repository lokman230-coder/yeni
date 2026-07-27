<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Settings\SettingsManager;

/**
 * Admin > Ayarlar — schema-based ayar formu.
 *
 * Ödeme sağlayıcı, e-fatura, AI, SMTP gibi hassas alanlar için:
 *   - type='password' → encrypted olarak saklanır
 *   - type='select'   → dropdown
 *   - type='bool'     → checkbox
 * Değişiklikler settings tablosuna yazılır; driver'lar bunu okur.
 */
final class SettingsController
{
    private const GROUPS = [
        'general'   => ['label' => '🏢 Genel',       'icon' => '⚙️'],
        'company'   => ['label' => '🏛️ Firma',        'icon' => '🏛️'],
        'mail'      => ['label' => '✉️ SMTP',         'icon' => '✉️'],
        'payment'   => ['label' => '💳 Ödeme',        'icon' => '💳'],
        'einvoice'  => ['label' => '🧾 E-Fatura',     'icon' => '🧾'],
        'ai'        => ['label' => '🤖 AI',           'icon' => '🤖'],
        'sms'       => ['label' => '📱 SMS',          'icon' => '📱'],
        'backup'    => ['label' => '💾 Backup / S3',  'icon' => '💾'],
        'security'  => ['label' => '🔒 Güvenlik',    'icon' => '🔒'],
    ];

    /**
     * Her grup için: [key, label, type, placeholder, default, hint]
     * type: text | password | email | number | url | textarea | select | bool
     */
    private static function schema(): array
    {
        return [
            'general' => [
                ['key'=>'site.name',    'label'=>'Site Adı',    'type'=>'text', 'default'=>'Ahost Bilişim'],
                ['key'=>'site.tagline', 'label'=>'Slogan',      'type'=>'text', 'default'=>'Modern Hosting & Dijital Hizmet Platformu'],
                ['key'=>'site.url',     'label'=>'Site URL',    'type'=>'url', 'default'=>'https://ahost.web.tr'],
                ['key'=>'site.default_currency','label'=>'Varsayılan Para Birimi','type'=>'select','options'=>['TRY'=>'TRY','USD'=>'USD','EUR'=>'EUR']],
                ['key'=>'site.default_locale','label'=>'Varsayılan Dil','type'=>'select','options'=>['tr'=>'Türkçe','en'=>'English']],
            ],
            'company' => [
                ['key'=>'company.name',    'label'=>'Ticari Ünvan',  'type'=>'text'],
                ['key'=>'company.address', 'label'=>'Adres',         'type'=>'textarea'],
                ['key'=>'company.phone',   'label'=>'Telefon',       'type'=>'text'],
                ['key'=>'company.email',   'label'=>'İletişim Email','type'=>'email'],
                ['key'=>'company.tax_id',  'label'=>'VKN',           'type'=>'text'],
                ['key'=>'company.tax_office','label'=>'Vergi Dairesi','type'=>'text'],
            ],
            'mail' => [
                ['key'=>'mail.host',         'label'=>'SMTP Host',    'type'=>'text', 'placeholder'=>'smtp.gmail.com'],
                ['key'=>'mail.port',         'label'=>'Port',         'type'=>'number','default'=>'587'],
                ['key'=>'mail.username',     'label'=>'Kullanıcı Adı','type'=>'text'],
                ['key'=>'mail.password',     'label'=>'Şifre',        'type'=>'password'],
                ['key'=>'mail.encryption',   'label'=>'Şifreleme',    'type'=>'select','options'=>['tls'=>'TLS','ssl'=>'SSL','none'=>'Yok'],'default'=>'tls'],
                ['key'=>'mail.from_address', 'label'=>'Gönderen Email','type'=>'email','placeholder'=>'noreply@ahost.web.tr'],
                ['key'=>'mail.from_name',    'label'=>'Gönderen Ad',  'type'=>'text','default'=>'Ahost Bilişim'],
            ],
            'payment' => [
                ['section'=>'💳 PayTR'],
                ['key'=>'paytr.merchant_id',   'label'=>'Merchant ID',   'type'=>'text'],
                ['key'=>'paytr.merchant_key',  'label'=>'Merchant Key',  'type'=>'password'],
                ['key'=>'paytr.merchant_salt', 'label'=>'Merchant Salt', 'type'=>'password'],
                ['key'=>'paytr.test_mode',     'label'=>'Test Modu',     'type'=>'bool','default'=>'1'],

                ['section'=>'💠 iyzico'],
                ['key'=>'iyzico.api_key',    'label'=>'API Key',    'type'=>'password'],
                ['key'=>'iyzico.secret_key', 'label'=>'Secret Key', 'type'=>'password'],
                ['key'=>'iyzico.sandbox',    'label'=>'Sandbox (Test) Modu', 'type'=>'bool','default'=>'1'],

                ['section'=>'🟨 Papara'],
                ['key'=>'papara.api_key', 'label'=>'API Key', 'type'=>'password'],
                ['key'=>'papara.sandbox', 'label'=>'Sandbox (Test) Modu', 'type'=>'bool','default'=>'1'],

                ['section'=>'🛒 Shopier'],
                ['key'=>'shopier.api_key',    'label'=>'API Key',    'type'=>'password', 'hint' => 'Shopier panelinden alınır — Ayarlar > API'],
                ['key'=>'shopier.api_secret', 'label'=>'API Secret', 'type'=>'password'],
            ],
            'einvoice' => [
                ['key'=>'einvoice.provider','label'=>'Sağlayıcı','type'=>'select',
                 'options'=>[
                    'noop'     => 'Kullanma (Sadece PDF fatura)',
                    'uyumsoft' => 'Uyumsoft',
                    // İleride eklenecek: 'foriba', 'nilvera', 'izibiz', 'qnb', 'logo', 'mikro'
                 ],
                 'default'=>'noop',
                 'hint' => 'E-fatura sadece 3M TL+ ciro veya belirli mükellefler için zorunludur. Küçük/orta işletmeler "Kullanma" seçebilir.'],

                ['section'=>'📄 Uyumsoft (opsiyonel)'],
                ['key'=>'einvoice.uyumsoft_username',  'label'=>'Kullanıcı Adı','type'=>'text', 'hint' => 'Sadece yukarıda Uyumsoft seçtiyseniz doldurun'],
                ['key'=>'einvoice.uyumsoft_password',  'label'=>'Şifre',        'type'=>'password'],
                ['key'=>'einvoice.uyumsoft_test_mode', 'label'=>'Test Modu',    'type'=>'bool','default'=>'1'],
            ],
            'ai' => [
                ['key'=>'ai.provider','label'=>'Sağlayıcı','type'=>'select','options'=>['heuristic'=>'Heuristic (Ücretsiz Kural-Tabanlı)','openai'=>'OpenAI'],'default'=>'heuristic'],
                ['key'=>'ai.api_key', 'label'=>'API Key',   'type'=>'password','hint'=>'Sadece OpenAI için gerekli'],
                ['key'=>'ai.model',   'label'=>'Model',     'type'=>'text','default'=>'gpt-4o-mini','hint'=>'Örn: gpt-4o-mini, gpt-4o, gpt-3.5-turbo'],
            ],
            'sms' => [
                ['key'=>'sms.driver','label'=>'SMS Sağlayıcı','type'=>'select',
                    'options'=>['log'=>'Log (Test/Dev — Sadece log dosyasına yazar)','netgsm'=>'NetGSM','iletimerkezi'=>'İletiMerkezi','twilio'=>'Twilio'],
                    'default'=>'log',
                    'hint'=>'Production için gerçek bir sağlayıcı seçin.'],
                // NetGSM
                ['key'=>'sms.netgsm_user',     'label'=>'NetGSM Kullanıcı Adı', 'type'=>'text'],
                ['key'=>'sms.netgsm_password', 'label'=>'NetGSM API Şifresi',   'type'=>'password'],
                ['key'=>'sms.netgsm_header',   'label'=>'NetGSM Başlık (Gönderici)','type'=>'text','hint'=>'Onaylı 11 karakter alfanumerik başlık'],
                // İletiMerkezi
                ['key'=>'sms.iletimerkezi_user','label'=>'İletiMerkezi Kullanıcı Adı','type'=>'text'],
                ['key'=>'sms.iletimerkezi_password','label'=>'İletiMerkezi Şifre','type'=>'password'],
                ['key'=>'sms.iletimerkezi_header','label'=>'İletiMerkezi Başlık','type'=>'text'],
                // Twilio
                ['key'=>'sms.twilio_sid',  'label'=>'Twilio Account SID','type'=>'text'],
                ['key'=>'sms.twilio_token','label'=>'Twilio Auth Token', 'type'=>'password'],
                ['key'=>'sms.twilio_from', 'label'=>'Twilio From Number','type'=>'text','hint'=>'Örn: +14155238886'],
                // Genel
                ['key'=>'sms.otp_enabled', 'label'=>'SMS/OTP ile giriş aktif','type'=>'bool','default'=>'0','hint'=>'Aktifse müşteri giriş formunda "SMS ile giriş" seçeneği görünür.'],
            ],
            'backup' => [
                ['key'=>'backup.s3_bucket',    'label'=>'S3 Bucket',      'type'=>'text',     'hint'=>'AWS/Wasabi/B2/DigitalOcean Spaces bucket adı'],
                ['key'=>'backup.s3_region',    'label'=>'Region',         'type'=>'text',     'default'=>'eu-central-1'],
                ['key'=>'backup.s3_endpoint',  'label'=>'Endpoint (opsiyonel)', 'type'=>'text', 'hint'=>'AWS için boş; B2/Wasabi/Spaces için: s3.wasabisys.com vb.'],
                ['key'=>'backup.s3_access_key','label'=>'Access Key',     'type'=>'text'],
                ['key'=>'backup.s3_secret_key','label'=>'Secret Key',     'type'=>'password'],
                ['key'=>'backup.s3_enabled',   'label'=>'Off-site backup aktif', 'type'=>'bool', 'default'=>'0', 'hint'=>'Aktifse günlük yedek otomatik S3\'e yollanır.'],
            ],
            'security' => [
                ['key'=>'security.admin_2fa_required','label'=>'Admin için 2FA zorunlu','type'=>'bool','default'=>'0'],
                ['key'=>'security.rate_limit_login','label'=>'Login rate limit (denemé/dk)','type'=>'number','default'=>'5'],
                ['key'=>'security.session_lifetime_min','label'=>'Oturum süresi (dk)','type'=>'number','default'=>'120'],
                ['key'=>'security.password_min_length','label'=>'Min şifre uzunluğu','type'=>'number','default'=>'8'],
                ['key'=>'security.password_require_strong','label'=>'Karmaşık şifre zorunlu (büyük+küçük+rakam)','type'=>'bool','default'=>'1'],

                ['section'=>'🐛 Hata Takip (Sentry uyumlu)'],
                ['key'=>'security.sentry_dsn','label'=>'Sentry DSN','type'=>'password',
                 'hint' => 'Sentry, GlitchTip veya benzeri DSN URL. Ör: https://KEY@sentry.io/PROJECT_ID. Boş bırakılırsa dış rapor gönderilmez.'],
            ],
        ];
    }

    public function index(Request $request): Response
    {
        $group = (string) $request->query('group', 'general');
        if (!isset(self::GROUPS[$group])) $group = 'general';

        $schema = self::schema()[$group] ?? [];
        // Her alan için mevcut değeri yükle (encrypted olanlar için "has_value" gösterilir, ham şifre değil)
        $fields = [];
        foreach ($schema as $item) {
            if (isset($item['section'])) {
                $fields[] = ['section' => $item['section']];
                continue;
            }
            $isPassword = ($item['type'] ?? '') === 'password';
            $stored = SettingsManager::get($item['key'], null);
            $fields[] = array_merge($item, [
                'value'     => $isPassword ? '' : ($stored ?? ($item['default'] ?? '')),
                'has_value' => $isPassword && !empty($stored),
            ]);
        }

        $view = new View();
        return Response::html($view->render('admin::settings.index', [
            'title'   => 'Ayarlar — ' . self::GROUPS[$group]['label'],
            'groups'  => self::GROUPS,
            'active'  => $group,
            'fields'  => $fields,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function save(Request $request): Response
    {
        $group = (string) $request->input('group', 'general');
        if (!isset(self::GROUPS[$group])) {
            SessionManager::flash('error', 'Geçersiz grup.');
            return Response::redirect('/admin/ayarlar');
        }
        $schema = self::schema()[$group] ?? [];
        $updates = (array) $request->input('settings', []);
        $count = 0;

        foreach ($schema as $item) {
            if (isset($item['section'])) continue;
            $key = $item['key'];
            $type = $item['type'] ?? 'text';
            $isPassword = $type === 'password';
            $isBool = $type === 'bool';

            if ($isBool) {
                SettingsManager::set($key, isset($updates[$key]) ? '1' : '0', 'bool', $group);
                $count++;
                continue;
            }

            if (!array_key_exists($key, $updates)) continue;
            $val = (string) $updates[$key];

            // Password alan boşsa MEVCUT değeri koruyoruz (silmek için özel "temizle" butonu gerek)
            if ($isPassword && $val === '') continue;

            $storeType = match ($type) {
                'password' => 'encrypted',
                'number'   => 'int',
                default    => 'string',
            };
            SettingsManager::set($key, $val, $storeType, $group);
            $count++;
        }

        // Ayarlar değiştiği için activity log
        if (class_exists(\App\Services\Logger\ActivityLog::class)) {
            \App\Services\Logger\ActivityLog::log('updated', 'settings', null, "$group grubu güncellendi ($count alan)");
        }

        SessionManager::flash('success', "✓ $count ayar kaydedildi.");
        return Response::redirect('/admin/ayarlar?group=' . urlencode($group));
    }

    /** Encrypted alan için "temizle" butonu (şifreyi sıfırla) */
    public function clearField(Request $request): Response
    {
        $key = (string) $request->input('key', '');
        $group = (string) $request->input('group', 'general');
        if ($key !== '') {
            SettingsManager::forget($key);
            SessionManager::flash('success', "'$key' temizlendi.");
        }
        return Response::redirect('/admin/ayarlar?group=' . urlencode($group));
    }
}
