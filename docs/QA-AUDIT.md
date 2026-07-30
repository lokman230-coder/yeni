# Production QA Audit

cPanel veya SSH terminalinde proje kökünde çalıştırın:

```bash
php scripts/qa-audit.php
```

Bu kontrol; public_html kök dosyalarını, gerekli klasörleri, PHP eklentilerini, storage izinlerini ve placeholder referanslarını kontrol eder.
