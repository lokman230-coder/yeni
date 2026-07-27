<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $pages = [
            ['slug' => 'hakkimizda',          'title' => 'Hakkımızda',           'content' => '<p>Ahost Bilişim, hosting, domain ve dijital hizmet ihtiyaçlarınız için modern, güvenilir bir platformdur.</p>'],
            ['slug' => 'misyon',              'title' => 'Misyon',               'content' => '<p>Türkiye\'de her ölçekteki işletmeye kurumsal düzeyde, kolay erişilebilir dijital altyapı sunmak.</p>'],
            ['slug' => 'vizyon',              'title' => 'Vizyon',               'content' => '<p>Dijital dönüşümün Türkiye\'deki en güvenilir teknoloji ortağı olmak.</p>'],
            ['slug' => 'gizlilik-politikasi', 'title' => 'Gizlilik Politikası',  'content' => '<p>KVKK ve GDPR kapsamında kişisel verilerinizi koruyoruz. Verileriniz açık rızanız olmadan üçüncü kişilerle paylaşılmaz.</p>'],
            ['slug' => 'cerez-politikasi',    'title' => 'Çerez Politikası',     'content' => '<p>Sitemizde deneyiminizi iyileştirmek için çerezler kullanıyoruz. Zorunlu çerezler dışındaki çerezleri reddetme hakkınız vardır.</p>'],
            ['slug' => 'kullanim-sartlari',   'title' => 'Kullanım Şartları',    'content' => '<p>Ahost Bilişim hizmetlerini kullanarak bu şartları kabul etmiş sayılırsınız.</p>'],
            ['slug' => 'hizmet-politikasi',   'title' => 'Hizmet Politikası',    'content' => '<p>Hizmet düzeyi taahhütlerimiz (SLA) ve destek prosedürlerimiz bu politikada tanımlıdır.</p>'],
            ['slug' => 'iade-sartlari',       'title' => 'İade Şartları',        'content' => '<p>Hosting hizmetleri için 30 gün, domain kayıtları için iade politikası uygulanmamaktadır.</p>'],
        ];
        foreach ($pages as $p) {
            $exists = Connection::selectOne("SELECT id FROM cms_pages WHERE slug = ?", [$p['slug']]);
            if (!$exists) {
                Connection::insert('cms_pages', array_merge($p, [
                    'seo_title'       => $p['title'] . ' — Ahost Bilişim',
                    'seo_description' => strip_tags($p['content']),
                    'is_published'    => 1,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
};
