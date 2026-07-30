# 02 · Dizin Yapısı

Aşağıdaki ağaç Faz 1 sonunda birebir oluşacaktır. Modüller Faz 3-5'te doldurulur.

```
ahost-one/
│
├── public/                              ← WEBROOT (Apache/Nginx buraya bakar)
│   ├── index.php                        ← Tek giriş noktası (front controller)
│   ├── .htaccess                        ← Rewrite + güvenlik başlıkları
│   ├── favicon.ico
│   ├── robots.txt
│   ├── sitemap.xml                      ← Dinamik (rewrite)
│   └── assets/                          ← Derlenmiş/statik varlıklar
│       ├── img/
│       ├── fonts/
│       └── vendor/                      ← 3rd party (lucide, chart.js vb.)
│
├── app/
│   │
│   ├── Core/                            ← Framework çekirdeği (kendi mini-framework'ümüz)
│   │   ├── Application.php              ← bootstrap orchestrator
│   │   ├── Container.php                ← basit PSR-11 uyumlu DI
│   │   ├── Router.php
│   │   ├── Route.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── View.php                     ← template engine (native PHP, auto-escape)
│   │   ├── Config.php
│   │   ├── Env.php
│   │   ├── ErrorHandler.php
│   │   └── ModuleLoader.php             ← modules'ı keşfeder ve yükler
│   │
│   ├── Support/                         ← Framework-agnostik yardımcılar
│   │   ├── Str.php
│   │   ├── Arr.php
│   │   ├── Money.php
│   │   ├── DateHelper.php
│   │   ├── Slug.php
│   │   ├── Uuid.php
│   │   ├── Validator.php
│   │   ├── Csv.php                      ← BTK export için
│   │   └── helpers.php                  ← globals: __(), e(), url(), asset(), config(), csrf()
│   │
│   ├── Services/                        ← Uygulama seviyesi servisler
│   │   ├── Auth/
│   │   │   ├── AuthService.php
│   │   │   ├── PasswordHasher.php
│   │   │   ├── SessionGuard.php
│   │   │   └── TwoFactorService.php
│   │   ├── Rbac/
│   │   │   ├── RbacService.php
│   │   │   └── PermissionRegistry.php
│   │   ├── Mail/
│   │   │   ├── MailerInterface.php
│   │   │   ├── SmtpMailer.php
│   │   │   └── MailQueue.php
│   │   ├── Sms/
│   │   │   ├── SmsGatewayInterface.php
│   │   │   └── NetGsmDriver.php         ← örnek TR sağlayıcı
│   │   ├── Currency/
│   │   │   ├── CurrencyService.php
│   │   │   ├── RateProvider.php         ← TCMB/exchangerate.host
│   │   │   └── MarginCalculator.php
│   │   ├── Tax/
│   │   │   └── TaxService.php
│   │   ├── Cache/
│   │   │   ├── CacheInterface.php
│   │   │   ├── FileCache.php
│   │   │   └── RedisCache.php
│   │   ├── Logger/
│   │   │   ├── Logger.php               ← PSR-3
│   │   │   └── AuditLogger.php
│   │   ├── Notification/
│   │   │   └── NotificationDispatcher.php
│   │   ├── Recaptcha/
│   │   │   └── RecaptchaVerifier.php
│   │   └── Ai/
│   │       ├── AiProviderInterface.php
│   │       ├── OpenAiDriver.php
│   │       └── ContextBuilder.php       ← public/customer/admin bağlam ayrımı
│   │
│   ├── Middleware/
│   │   ├── CsrfMiddleware.php
│   │   ├── AuthMiddleware.php
│   │   ├── AdminAuthMiddleware.php
│   │   ├── CustomerAuthMiddleware.php
│   │   ├── RbacMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   ├── LocaleMiddleware.php
│   │   ├── CurrencyMiddleware.php
│   │   ├── SecurityHeadersMiddleware.php
│   │   └── CookieConsentMiddleware.php
│   │
│   ├── Modules/                         ← TÜM İŞ MANTIĞI BURADA
│   │   ├── Header/                      ← şartname madde 6: header modülü ayrı
│   │   │   ├── module.php
│   │   │   ├── Views/{topbar,header,mobile-menu}.php
│   │   │   ├── assets/{css/header.css, js/header.js}
│   │   │   └── Services/MenuBuilder.php
│   │   │
│   │   ├── Footer/                      ← şartname madde 7
│   │   │   ├── module.php
│   │   │   ├── Views/footer.php
│   │   │   └── assets/{css/footer.css, js/footer.js}
│   │   │
│   │   ├── Home/                        ← public ana sayfa
│   │   ├── Pages/                       ← statik sayfalar (hakkımızda, kvkk, vs.)
│   │   ├── Blog/
│   │   ├── Announcements/
│   │   ├── Knowledge/                   ← bilgi bankası
│   │   ├── Contact/
│   │   ├── References/
│   │   │
│   │   ├── Product/                     ← ürün merkezi (şartname 9-13)
│   │   │   ├── module.php
│   │   │   ├── Controllers/{PublicProductController, AdminProductController}.php
│   │   │   ├── Services/{ProductService, PricingService, AddonService, CustomFieldService}.php
│   │   │   ├── Repositories/{ProductRepository, PriceRepository, AddonRepository}.php
│   │   │   ├── Models/{Product, ProductGroup, ProductPrice, ProductAddon, ProductCustomField}.php
│   │   │   ├── Migrations/
│   │   │   ├── Views/
│   │   │   └── assets/
│   │   │
│   │   ├── Cart/                        ← şartname 14
│   │   ├── Checkout/                    ← şartname 15
│   │   ├── Payment/
│   │   │   └── Drivers/{PayTrDriver, BankTransferDriver, BalanceDriver, ManualDriver}.php
│   │   ├── Invoice/                     ← şartname 29
│   │   ├── Coupon/
│   │   ├── Currency/                    ← admin kur merkezi UI (servis Services/Currency/)
│   │   │
│   │   ├── Domain/                      ← şartname 16
│   │   │   ├── Services/{WhoisService, DnsService, SslService, ValuationService}.php
│   │   │   └── Views/{search, whois-card, dns-card, ssl-card, valuation}.php
│   │   ├── Registrar/                   ← şartname 17
│   │   │   └── Drivers/{DomainNameApiDriver, ManualDriver}.php
│   │   │
│   │   ├── Hosting/                     ← şartname 18
│   │   │   └── Drivers/{CpanelDriver, DirectAdminDriver, PleskDriver, ManualDriver}.php
│   │   ├── Server/
│   │   │
│   │   ├── SiteTools/                   ← şartname 19
│   │   │   └── Tools/{Whois, DnsCheck, SslCheck, SeoAnalyze, SiteAnalyze,
│   │   │              SpeedTest, SecurityHeaders, IpLookup, Ping,
│   │   │              HttpHeader, RobotsCheck, SitemapCheck, MetaAnalyze,
│   │   │              LinkAnalyze, ImageAltAnalyze}.php
│   │   │
│   │   ├── Customer/                    ← müşteri paneli (şartname 21)
│   │   │   ├── Controllers/{DashboardController, ServiceController, DomainController,
│   │   │   │                InvoiceController, OrderController, TicketController,
│   │   │   │                ProfileController, SecurityController, BalanceController}.php
│   │   │   └── ...
│   │   │
│   │   ├── Admin/                       ← admin paneli (şartname 20)
│   │   │   ├── Controllers/{DashboardController, CustomerController, OrderController,
│   │   │   │                ProductController, DomainController, HostingController,
│   │   │   │                FinanceController, TicketController, PageController,
│   │   │   │                BlogController, AnnouncementController, MenuController,
│   │   │   │                SiteBuilderController, MobileBuilderController,
│   │   │   │                SiteToolsController, MarketplaceController,
│   │   │   │                SettingsController, ModuleController, AiController,
│   │   │   │                CookieAnalyticsController, QaScanController,
│   │   │   │                LogController, CacheController, HealthController}.php
│   │   │   ├── Services/QuickSearchService.php
│   │   │   └── Views/layouts/{admin-layout.php, admin-sidebar.php}
│   │   │
│   │   ├── Ticket/                      ← şartname 27
│   │   │
│   │   ├── SiteBuilder/                 ← şartname 23
│   │   │   ├── Editor/
│   │   │   ├── Blocks/
│   │   │   ├── Templates/{Hosting, Agency, Landing, Radio, Ecommerce,
│   │   │   │              Restaurant, Clinic, Education, Portfolio, Saas, Local}/
│   │   │   ├── Export/{ZipExporter, SourceCodeExporter}.php
│   │   │   └── Views/editor.php
│   │   │
│   │   ├── MobileBuilder/               ← şartname 24
│   │   │   ├── Editor/
│   │   │   ├── Templates/{Radio, Corporate, Restaurant, Ecommerce, News,
│   │   │   │              Education, Gym, Booking}/
│   │   │   └── Export/{ApkExporter, AabExporter, SourceExporter}.php
│   │   │
│   │   ├── Ai/                          ← şartname 22 (public/customer/admin 3 bağlam)
│   │   │   ├── Controllers/{PublicAiController, CustomerAiController, AdminAiController}.php
│   │   │   ├── Contexts/{PublicContext, CustomerContext, AdminContext}.php
│   │   │   └── Views/{floating-widget.php, chat-panel.php}
│   │   │
│   │   ├── Marketplace/                 ← şartname 26
│   │   │
│   │   ├── CookieAnalytics/             ← şartname 8
│   │   │   ├── Services/EventTracker.php
│   │   │   ├── Controllers/{TrackController, AdminAnalyticsController}.php
│   │   │   └── Views/{banner.php, admin-dashboard.php}
│   │   │
│   │   ├── Btk/                         ← şartname 30 — yer sağlayıcı CSV
│   │   │   └── Services/BtkExporter.php
│   │   │
│   │   ├── Install/                     ← şartname 37 — kurulum sihirbazı
│   │   │   └── (kurulumdan sonra kendini kilitler)
│   │   │
│   │   └── Health/                      ← QA Scan, Health Center, sistem durumu
│   │
│   ├── Integrations/                    ← Dış servis SDK sarmalayıcıları
│   │   ├── PayTr/
│   │   │   ├── PayTrClient.php
│   │   │   ├── Config.php
│   │   │   └── CallbackHandler.php
│   │   ├── DomainNameApi/
│   │   │   ├── Client.php
│   │   │   └── Mapper.php
│   │   ├── Cpanel/
│   │   ├── DirectAdmin/
│   │   ├── Plesk/
│   │   └── Recaptcha/
│   │
│   └── Console/                         ← Cron / CLI komutları (şartname 33)
│       ├── Kernel.php
│       ├── Commands/
│       │   ├── GenerateInvoicesCommand.php
│       │   ├── SendRenewalRemindersCommand.php
│       │   ├── CheckExpiredServicesCommand.php
│       │   ├── SuspendOverdueServicesCommand.php
│       │   ├── BackupCommand.php
│       │   ├── UpdateCurrencyRatesCommand.php
│       │   ├── ProcessMailQueueCommand.php
│       │   ├── ProcessSmsQueueCommand.php
│       │   ├── ApiHealthCheckCommand.php
│       │   └── CleanCacheCommand.php
│       └── console                      ← executable: php console <command>
│
├── themes/
│   └── default/
│       ├── layouts/
│       │   ├── public.php               ← header include + yield + footer include
│       │   ├── admin.php
│       │   └── customer.php
│       ├── partials/
│       │   ├── cookie-banner.php
│       │   ├── ai-widget.php
│       │   ├── flash-messages.php
│       │   └── 404.php
│       ├── css/
│       │   ├── theme.css                ← CSS custom properties (tokens)
│       │   ├── reset.css
│       │   └── components/{buttons.css, cards.css, forms.css, modal.css, table.css}
│       └── js/
│           └── theme.js
│
├── storage/                             ← .gitignore
│   ├── logs/{app/, audit/, api/, cron/}
│   ├── cache/
│   ├── sessions/
│   ├── uploads/{public/, private/}
│   ├── builder-projects/                ← Site Builder export'ları
│   └── backups/
│
├── database/
│   ├── migrations/                      ← çekirdek migration dosyaları
│   │   ├── 0001_create_settings_table.php
│   │   ├── 0002_create_admins_table.php
│   │   ├── 0003_create_admin_roles_table.php
│   │   ├── ...
│   │   └── (modül migration'ları modules/*/Migrations/ altında)
│   ├── seeds/
│   │   ├── DefaultSettingsSeeder.php
│   │   ├── DefaultProductGroupsSeeder.php
│   │   ├── SampleProductsSeeder.php
│   │   ├── DefaultCurrenciesSeeder.php
│   │   ├── LegalPagesSeeder.php
│   │   └── EmailTemplatesSeeder.php
│   └── schema.sql                       ← komple şema (referans)
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   ├── sms.php
│   ├── paytr.php
│   ├── registrars.php
│   ├── hosting.php
│   ├── ai.php
│   ├── cache.php
│   ├── session.php
│   ├── currency.php
│   └── modules.php                      ← hangi modüller aktif
│
├── lang/
│   ├── tr/
│   │   ├── common.php
│   │   ├── validation.php
│   │   └── auth.php
│   └── en/
│
├── install/                             ← şartname 37 — ilk kurulum
│   ├── index.php
│   ├── steps/{01-requirements.php, 02-database.php, 03-admin.php,
│   │          04-company.php, 05-defaults.php, 06-finish.php}
│   └── assets/
│
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── E2E/                             ← Playwright
│
├── docs/                                ← Bu dokümanlar
├── planning/                            ← Roadmap, acceptance matrix
│
├── .env.example
├── .gitignore
├── composer.json
├── package.json                         ← (opsiyonel, sadece Playwright/build script'leri)
├── phpunit.xml
├── playwright.config.js
└── README.md
```

---

## İsimlendirme Kuralları

| Öğe | Kural | Örnek |
|---|---|---|
| PHP Class | `PascalCase` | `ProductPricingService` |
| PHP method | `camelCase` | `calculateFinalPrice()` |
| PHP değişken | `camelCase` | `$activeProducts` |
| Dosya | Class ile birebir | `ProductPricingService.php` |
| Migration | `NNNN_snake_case.php` | `0042_create_domains_table.php` |
| Route path | `kebab-case` | `/site-builder/preview` |
| Tablo | `snake_case`, çoğul | `product_prices` |
| DB kolon | `snake_case` | `created_at` |
| CSS class | `.aho-<module>-<component>[-<state>]` | `.aho-cart-item--removed` |
| JS namespace | `AhostOne.<Module>` | `AhostOne.Cart.add(id)` |
| Data attr | `data-aho-<action>` | `data-aho-cart-add="42"` |
| Config anahtarı | dot notation | `paytr.merchant_id` |
| Env var | `SCREAMING_SNAKE` | `PAYTR_MERCHANT_ID` |
| Route adı | `snake.dot` | `admin.product.edit` |

---

## Modüllerin Kendi Dosyaları — Şartname Madde 2 Karşılığı

Şartname: *"Her modül kendi PHP, CSS ve JS dosyalarına sahip olacak."*

Uygulama:
- `app/Modules/Cart/Views/*.php` → sepetin HTML'i
- `app/Modules/Cart/assets/css/cart.css` → sepetin CSS'i
- `app/Modules/Cart/assets/js/cart.js` → sepetin JS'i
- Bu CSS/JS otomatik olarak `<link>` / `<script>` ile inject edilir *sadece* Cart route'u aktifken. Bkz. `05-MODULE-CONTRACT.md § Asset Injection`.
