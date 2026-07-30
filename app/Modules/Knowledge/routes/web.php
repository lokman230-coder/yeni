<?php

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;
use App\Core\View;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/bilgi-bankasi', function (Request $request) {
        $view = new View();
        $query = trim((string)$request->query('q', ''));
        $articles = knowledge_articles();
        if ($query !== '') {
            $needle = mb_strtolower($query);
            $articles = array_values(array_filter($articles, static function (array $article) use ($needle): bool {
                $haystack = mb_strtolower($article['title'] . ' ' . $article['summary'] . ' ' . implode(' ', $article['body']));
                return str_contains($haystack, $needle);
            }));
        }
        return Response::html($view->render('knowledge::index', [
            'title' => 'Bilgi Bankasi',
            'categories' => knowledge_categories(),
            'articles' => $articles,
            'query' => $query,
            'activeCategory' => null,
        ]));
    })->name('knowledge.index');

    $router->get('/bilgi-bankasi/kategori/{slug}', function (Request $request) {
        $slug = (string)$request->param('slug');
        $categories = knowledge_categories();
        if (!isset($categories[$slug])) {
            return Response::notFound();
        }
        $articles = array_values(array_filter(knowledge_articles(), static fn(array $article): bool => $article['category'] === $slug));
        return Response::html((new View())->render('knowledge::index', [
            'title' => $categories[$slug]['title'] . ' - Bilgi Bankasi',
            'categories' => $categories,
            'articles' => $articles,
            'query' => '',
            'activeCategory' => $slug,
        ]));
    })->name('knowledge.category');

    $router->get('/bilgi-bankasi/{slug}', function (Request $request) {
        $slug = (string)$request->param('slug');
        foreach (knowledge_articles() as $article) {
            if ($article['slug'] === $slug) {
                return Response::html((new View())->render('knowledge::show', [
                    'title' => $article['title'] . ' - Bilgi Bankasi',
                    'article' => $article,
                    'category' => knowledge_categories()[$article['category']] ?? null,
                ]));
            }
        }
        return Response::notFound();
    })->name('knowledge.show');
});

function knowledge_categories(): array
{
    return [
        'domain' => ['icon' => '🌐', 'title' => 'Domain', 'summary' => 'Domain kayit, transfer, DNS yonetimi'],
        'hosting' => ['icon' => '🖥', 'title' => 'Hosting', 'summary' => 'cPanel, e-posta, veritabani islemleri'],
        'billing' => ['icon' => '💰', 'title' => 'Faturalar', 'summary' => 'Odeme, iade, fatura duzenleme'],
        'security' => ['icon' => '🔒', 'title' => 'Guvenlik', 'summary' => 'SSL, sifre, iki adimli dogrulama'],
        'site-builder' => ['icon' => '🎨', 'title' => 'Site Builder', 'summary' => 'Sablon, blok, yayinlama'],
        'mobile-builder' => ['icon' => '📱', 'title' => 'Mobile Builder', 'summary' => 'APK, AAB, PWA ve kaynak kod'],
    ];
}

function knowledge_articles(): array
{
    return [
        ['category' => 'domain', 'slug' => 'domain-dns-nasil-degistirilir', 'title' => 'DNS kayitlari nasil degistirilir?', 'summary' => 'A, CNAME, MX ve TXT kayitlarini panelden guncelleme.', 'body' => ['Musteri panelinden Domainlerim bolumune girin.', 'Ilgili domainin detay sayfasinda DNS yonetimi alanini acin.', 'Kaydi ekledikten sonra yayilma suresi genellikle birkac dakika ile 24 saat arasindadir.']],
        ['category' => 'domain', 'slug' => 'domain-transfer-sureci', 'title' => 'Domain transfer sureci nasil isler?', 'summary' => 'Transfer kilidi, EPP kodu ve onay adimlari.', 'body' => ['Eski firmanizdan transfer kilidini kaldirin ve EPP kodunu alin.', 'Transfer siparisini olustururken kodu girin.', 'Onay e-postasini tamamladiktan sonra surec uzantiya gore 1-7 gun surebilir.']],
        ['category' => 'hosting', 'slug' => 'hosting-hesabi-bilgileri', 'title' => 'Hosting hesap bilgilerimi nerede gorurum?', 'summary' => 'Panelde domain, kullanici adi ve sifre goruntuleme.', 'body' => ['Musteri panelinde Hizmetlerim sayfasini acin.', 'Hosting hizmetinin detayina girin.', 'Sunucu, kullanici adi ve sifre alanlarini bu ekrandan yonetebilirsiniz.']],
        ['category' => 'hosting', 'slug' => 'cpanel-e-posta-hesabi-acma', 'title' => 'cPanel e-posta hesabi nasil acilir?', 'summary' => 'Alan adina bagli posta kutusu olusturma.', 'body' => ['cPanel giris bilgileriyle panelinize girin.', 'Email Accounts bolumunden yeni posta kutusu olusturun.', 'Kota ve sifreyi belirleyip kaydedin.']],
        ['category' => 'billing', 'slug' => 'fatura-odeme-yontemleri', 'title' => 'Faturami nasil oderim?', 'summary' => 'Kart, havale ve aktif odeme yontemleri.', 'body' => ['Musteri panelinden Faturalarim sayfasini acin.', 'Odenmemis faturada Ode butonuna tiklayin.', 'Aktif odeme yontemlerinden birini secip islemi tamamlayin.']],
        ['category' => 'security', 'slug' => 'ssl-sertifikasi-aktiflestirme', 'title' => 'SSL sertifikasi nasil aktif edilir?', 'summary' => 'Hosting uzerinde SSL kurulum ve kontrol adimlari.', 'body' => ['Domain DNS kayitlarinizin hosting sunucusuna yonlendiginden emin olun.', 'Panelde SSL bolumunu acin ve sertifikayi etkinlestirin.', 'Tarayicida https ile kontrol edin.']],
        ['category' => 'site-builder', 'slug' => 'site-builder-sablon-secme', 'title' => 'Site Builder sablonu nasil acilir?', 'summary' => 'Sablon kartina tiklayarak editoru baslatma.', 'body' => ['Site Builder sayfasinda sektor veya sablon kartini secin.', 'Sistem otomatik proje olusturup editoru acar.', 'Giris yapmadan demo hazirlayabilir, paket gerektiren ciktilarda satin alma sayfasina gecebilirsiniz.']],
        ['category' => 'mobile-builder', 'slug' => 'mobile-builder-apk-aab-alma', 'title' => 'APK veya AAB nasil alinir?', 'summary' => 'Mobil projenin paket ciktilarini satin alma ve indirme.', 'body' => ['Mobile Builder ile projenizi hazirlayin.', 'Export ekraninda APK, AAB, PWA veya kaynak kod secenegini secin.', 'Ucretli paketlerde odeme tamamlaninca build sureci baslar.']],
    ];
}
