<?php
$radioAppName = trim((string)($_GET['app_name'] ?? 'Radyo Adı')) ?: 'Radyo Adı';
$radioSlogan = trim((string)($_GET['slogan'] ?? 'En iyi müzik, en iyi radyo')) ?: 'En iyi müzik, en iyi radyo';
$radioPhone = trim((string)($_GET['phone'] ?? '+90 555 000 00 00')) ?: '+90 555 000 00 00';
$radioPrimaryColor = trim((string)($_GET['primary_color'] ?? '#2563eb')) ?: '#2563eb';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $radioPrimaryColor)) { $radioPrimaryColor = '#2563eb'; }
$radioStreamUrl = trim((string)($_GET['stream_url'] ?? 'https://stream.radio.com/live')) ?: 'https://stream.radio.com/live';
$radioStreamType = trim((string)($_GET['stream_type'] ?? 'mp3')) ?: 'mp3';
$radioBitrate = trim((string)($_GET['bitrate'] ?? '128')) ?: '128';
$radioFacebook = trim((string)($_GET['facebook'] ?? 'https://facebook.com/radyo'));
$radioInstagram = trim((string)($_GET['instagram'] ?? 'https://instagram.com/radyo'));
$radioTwitter = trim((string)($_GET['twitter'] ?? 'https://x.com/radyo'));
$radioYoutube = trim((string)($_GET['youtube'] ?? 'https://youtube.com/radyo'));
$radioEmail = trim((string)($_GET['email'] ?? 'info@radyo.com'));
?>
<section class="ao-public-page builder-public-page ao-radio-builder-page">
    <div class="ao-public-shell builder-shell">
        <div class="builder-head ao-builder-hero-card">
            <span class="builder-badge"> Radyo Uygulaması</span>
            <h1>Radyo Uygulaması Oluştur</h1>
            <p>Canlı radyo dinleme, program rehberi, podcast, WhatsApp ve sosyal medya bağlantılarıyla profesyonel mobil radyo uygulamanızı tasarlayın.</p>
            <div class="builder-actions">
                <a class="site-btn" href="#radio-settings">Ayarları Düzenle</a>
                <a class="site-btn secondary" href="<?= url('mobilebuilder/preview-public?template=radio') ?>">Canlı Önizle</a>
                <a class="site-btn ghost" href="<?= url('urunler?group=mobilebuilder') ?>">Paketleri Gör</a>
            </div>
        </div>

        <div class="ao-builder-ai-assist">
            <div class="ao-ai-head">
                <div>
                    <span class="ao-ai-badge">AI Yardımı</span>
                    <h3>Radyo uygulamanızı AI ile taslaklayın</h3>
                    <p>Radyo IP adresi, WhatsApp, sosyal ağlar ve istek hattı gibi bilgileri hesabınızda AI sağlayıcısı seçerek taslağa dönüştürebilirsiniz.</p>
                </div>
                <div class="ao-ai-provider-row">
                    <span>Gemini</span><span>ChatGPT</span><span>OpenRouter</span><span>Groq</span>
                </div>
            </div>
            <div class="ao-ai-actions">
                <a class="primary" href="<?= url('client/mobile-builder#ai-yardimi') ?>">AI ile Radyo Uygulaması Oluştur</a>
                <a data-builder-package-alert data-builder-package-kind="mobile" data-package-title="APK çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-apk-output') ?>">APK Paketi</a>
                <a data-builder-package-alert data-builder-package-kind="mobile" data-package-title="AAB çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-aab-output') ?>">AAB Paketi</a>
                <a data-builder-package-alert data-builder-package-kind="mobile" data-package-title="Kaynak kod için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-source-code') ?>">Kaynak Kod</a>
            </div>
        </div>

        <div class="radio-app-builder ao-radio-workspace">
            <div class="radio-phone-frame ao-device-stage">
                <div class="ao-device-label">
                    <span></span>
                    <div>
                        <b>Canlı telefon önizlemesi</b>
                        <small>Uygulama ekranı, player ve alt menü</small>
                    </div>
                </div>

                <div class="radio-phone-mock ao-phone-mock">
                    <div class="radio-status-bar">
                        <span>9:41</span>
                        <span> 5G </span>
                    </div>
                    <div class="radio-main-screen" id="radioAppPreview">
                        <div class="radio-header">
                            <div class="radio-logo-small" id="headerLogoPreview"></div>
                            <div>
                                <h4 id="headerAppName"><?= e($radioAppName) ?></h4>
                                <small>Canlı yayın</small>
                            </div>
                            <a href="#" class="radio-round-action" id="whatsappHeaderBtn"></a>
                        </div>

                        <div class="radio-now-playing">
                            <div class="radio-station-art" id="stationArt"></div>
                            <div class="radio-station-info">
                                <span class="radio-live-badge"> CANLI</span>
                                <h3 id="stationName"><?= e($radioAppName) ?></h3>
                                <p id="currentShow">Şu an: <?= e($radioSlogan) ?></p>
                            </div>
                        </div>

                        <div class="radio-player">
                            <button type="button" class="radio-play-btn" id="playBtn"></button>
                            <div class="radio-volume">
                                <span></span>
                                <input type="range" min="0" max="100" value="75" class="radio-volume-slider" id="radioVolume" aria-label="Ses seviyesi">
                                <span></span>
                            </div>
                            <audio id="radioAudio" preload="none"></audio>
                        </div>
                        <small id="radioPlayerStatus" class="radio-player-status" style="display:block;margin-top:8px;color:#64748b;font-weight:560;">Yayın hazır. Play tuşu Stream URL alanını kullanır.</small>

                        <div class="radio-social-links" id="socialLinksPreview">
                            <a href="#" class="radio-social-btn">f</a>
                            <a href="#" class="radio-social-btn"></a>
                            <a href="#" class="radio-social-btn">ğ•</a>
                            <a href="#" class="radio-social-btn"></a>
                            <a href="#" class="radio-social-btn"></a>
                        </div>

                        <div class="radio-bottom-nav">
                            <button type="button" class="radio-nav-btn active"> <span>Ana Sayfa</span></button>
                            <button type="button" class="radio-nav-btn"><span>Program</span></button>
                            <button type="button" class="radio-nav-btn"><span>Podcast</span></button>
                            <button type="button" class="radio-nav-btn"><span>İletişim</span></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="radio-config-panel ao-builder-config" id="radio-settings">
                <form class="demo-form ao-builder-form" id="radioDemoForm" method="get" action="<?= url('mobilebuilder/preview-public') ?>">
                    <div class="ao-form-title">
                        <span></span>
                        <div>
                            <small>Radyo App Builder</small>
                            <h2>Radyo Ayarları</h2>
                        </div>
                    </div>

                    <div class="radio-form-section ao-form-section">
                        <h4> Temel Bilgiler</h4>
                        <div class="ao-form-grid two">
                            <label>Uygulama Adı
                                <input type="text" id="appName" name="app_name" placeholder="Radyo Adı" value="<?= e($radioAppName) ?>">
                            </label>
                            <label>Slogan
                                <input type="text" id="appSlogan" name="slogan" placeholder="En iyi müzik, en iyi radyo" value="<?= e($radioSlogan) ?>">
                            </label>
                            <label>Telefon Numarası
                                <input type="tel" id="phoneNumber" name="phone" placeholder="+90 555 000 00 00" value="<?= e($radioPhone) ?>">
                            </label>
                            <label>Ana Renk
                                <input type="color" id="primaryColor" name="primary_color" value="<?= e($radioPrimaryColor) ?>">
                            </label>
                        </div>
                    </div>

                    <div class="radio-form-section ao-form-section">
                        <h4> Radyo Stream</h4>
                        <div class="ao-form-grid three">
                            <label>Stream URL
                                <input type="url" id="streamUrl" name="stream_url" placeholder="https://stream.radio.com/live" value="<?= e($radioStreamUrl) ?>">
                            </label>
                            <label>Stream Tipi
                                <select id="streamType" name="stream_type">
                                    <option value="mp3" <?= $radioStreamType === 'mp3' ? 'selected' : '' ?>>MP3</option>
                                    <option value="aac" <?= $radioStreamType === 'aac' ? 'selected' : '' ?>>AAC</option>
                                    <option value="ogg" <?= $radioStreamType === 'ogg' ? 'selected' : '' ?>>OGG</option>
                                    <option value="icecast" <?= $radioStreamType === 'icecast' ? 'selected' : '' ?>>Icecast</option>
                                    <option value="shoutcast" <?= $radioStreamType === 'shoutcast' ? 'selected' : '' ?>>Shoutcast</option>
                                </select>
                            </label>
                            <label>Bitrate
                                <select id="bitrate" name="bitrate">
                                    <option value="64" <?= $radioBitrate === '64' ? 'selected' : '' ?>>64 kbps</option>
                                    <option value="128" <?= $radioBitrate === '128' ? 'selected' : '' ?>>128 kbps</option>
                                    <option value="192" <?= $radioBitrate === '192' ? 'selected' : '' ?>>192 kbps</option>
                                    <option value="256" <?= $radioBitrate === '256' ? 'selected' : '' ?>>256 kbps</option>
                                    <option value="320" <?= $radioBitrate === '320' ? 'selected' : '' ?>>320 kbps</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="radio-form-section ao-form-section">
                        <h4> Sosyal Medya</h4>
                        <div class="radio-social-inputs">
                            <label class="radio-social-row"><span>FB</span><input type="url" name="facebook" placeholder="Facebook URL" value="<?= e($radioFacebook) ?>"></label>
                            <label class="radio-social-row"><span>IG</span><input type="url" name="instagram" placeholder="Instagram URL" value="<?= e($radioInstagram) ?>"></label>
                            <label class="radio-social-row"><span>X</span><input type="url" name="twitter" placeholder="Twitter/X URL" value="<?= e($radioTwitter) ?>"></label>
                            <label class="radio-social-row"><span>YT</span><input type="url" name="youtube" placeholder="YouTube URL" value="<?= e($radioYoutube) ?>"></label>
                            <label class="radio-social-row"><span>WA</span><input type="tel" name="whatsapp" placeholder="WhatsApp numarası" value="<?= e($radioPhone) ?>"></label>
                            <label class="radio-social-row"><span>@</span><input type="email" name="email" placeholder="E-posta" value="<?= e($radioEmail) ?>"></label>
                        </div>
                    </div>

                    <div class="radio-form-section ao-form-section">
                        <h4> İletişim ve PWA</h4>
                        <div class="ao-form-grid two">
                            <label>Adres
                                <textarea id="address" name="address" rows="2" placeholder="Radyo adresi...">İstanbul, Türkiye</textarea>
                            </label>
                            <label>Hakkında
                                <textarea id="about" name="about" rows="2" placeholder="Radyo hakkında...">En iyi müzik, en iyi programlar ile yanınızdayız.</textarea>
                            </label>
                            <label>PWA Görünen Adı
                                <input type="text" id="pwaName" name="pwa_name" value="Radyo">
                            </label>
                            <div class="ao-check-list">
                                <label><input type="checkbox" name="pwa_installable" checked> Uygulama yüklenebilir olsun</label>
                                <label><input type="checkbox" name="pwa_notification" checked> Bildirimlere izin ver</label>
                                <label><input type="checkbox" id="enableSplash" name="enable_splash" checked> Splash ekranı aktif</label>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="template" value="radio">
                    <input type="hidden" name="demo_mode" value="radio_config">

                    <div class="builder-actions full">
                        <button type="submit" class="site-btn"> Radyo Uygulamasını Önizle</button>
                        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="mobile" data-package-title="APK çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-apk-output') ?>">APK Paketi</a>
                        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="mobile" data-package-title="AAB çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-aab-output') ?>">AAB Paketi</a>
                        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="mobile" data-package-title="Kaynak kod için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-source-code') ?>">Kaynak Kod</a>
                    </div>
                </form>

                <div class="radio-features-box ao-builder-feature-box">
                    <h4> Radyo Uygulaması Özellikleri</h4>
                    <div class="ao-feature-chip-grid">
                        <span>Canlı radyo dinleme</span>
                        <span>ICEcast/Shoutcast</span>
                        <span>Program rehberi</span>
                        <span>Podcast desteği</span>
                        <span>WhatsApp butonu</span>
                        <span>Sosyal medya</span>
                        <span>PWA desteği</span>
                        <span>Android APK çıktısı</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const playBtn = document.getElementById('playBtn');
    const audio = document.getElementById('radioAudio');
    const volume = document.getElementById('radioVolume');
    const statusEl = document.getElementById('radioPlayerStatus');
    const appNameInput = document.getElementById('appName');
    const appSloganInput = document.getElementById('appSlogan');
    const primaryColorInput = document.getElementById('primaryColor');
    const streamUrlInput = document.getElementById('streamUrl');

    function setStatus(message, state) {
        if (!statusEl) return;
        statusEl.textContent = message;
        statusEl.style.color = state === 'error' ? '#dc2626' : (state === 'playing' ? '#16a34a' : '#64748b');
    }

    function cleanStreamUrl() {
        return (streamUrlInput?.value || '').trim();
    }

    function updatePreview() {
        const appName = appNameInput?.value || 'Radyo Adı';
        const slogan = appSloganInput?.value || 'Program Adı';
        const color = primaryColorInput?.value || '#2563eb';
        ['headerAppName', 'stationName'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.textContent = appName;
        });
        const currentShow = document.getElementById('currentShow');
        if (currentShow) currentShow.textContent = 'Şu an: ' + slogan;
        document.documentElement.style.setProperty('--radio-accent', color);
    }

    async function toggleRadioPlayback() {
        if (!audio || !playBtn) return;
        if (!audio.paused) {
            audio.pause();
            playBtn.textContent = '';
            setStatus('Yayın duraklatıldı.', 'idle');
            return;
        }

        const streamUrl = cleanStreamUrl();
        if (!streamUrl) {
            setStatus('Stream URL boş. Önce geçerli bir yayın adresi girin.', 'error');
            return;
        }
        if (location.protocol === 'https:' && /^http:\/\//i.test(streamUrl)) {
            setStatus('HTTPS sayfada HTTP yayın adresi tarayıcı tarafından engellenebilir. HTTPS stream kullanın.', 'error');
            return;
        }

        try {
            if (audio.src !== streamUrl) {
                audio.src = streamUrl;
                audio.load();
            }
            audio.volume = Math.max(0, Math.min(1, Number(volume?.value || 75) / 100));
            setStatus('Yayın bağlanıyor...', 'idle');
            await audio.play();
            playBtn.textContent = '';
            setStatus('Yayın çalıyor: ' + streamUrl, 'playing');
        } catch (error) {
            playBtn.textContent = '';
            setStatus('Yayın çalınamadı. URL, CORS, codec veya HTTPS uyumluluğunu kontrol edin.', 'error');
        }
    }

    appNameInput?.addEventListener('input', updatePreview);
    appSloganInput?.addEventListener('input', updatePreview);
    primaryColorInput?.addEventListener('input', updatePreview);
    streamUrlInput?.addEventListener('input', function() {
        if (audio && !audio.paused) audio.pause();
        if (playBtn) playBtn.textContent = '';
        setStatus('Yayın adresi güncellendi. Play tuşuna basınca yeni URL denenir.', 'idle');
    });
    volume?.addEventListener('input', function() {
        if (audio) audio.volume = Math.max(0, Math.min(1, Number(this.value || 75) / 100));
    });
    audio?.addEventListener('ended', function() { playBtn.textContent = ''; setStatus('Yayın sona erdi.', 'idle'); });
    audio?.addEventListener('error', function() { playBtn.textContent = ''; setStatus('Yayın hatası. Stream URL veya codec desteklenmiyor olabilir.', 'error'); });
    playBtn?.addEventListener('click', toggleRadioPlayback);
    updatePreview();
});
</script>
