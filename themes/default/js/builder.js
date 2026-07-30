/**
 * Ahost Bilişim — Builder Editor JS (Site + Mobile ortak)
 * Namespace: AhostOne.modules.Builder
 *
 * Blok listesi state olarak tutulur; her değişiklikte AJAX auto-save.
 * Cihaz görünümü, blok ekleme/silme/taşıma/kopyalama, property panel.
 */
(function (App) {
    'use strict';

    const Builder = {
        state: {
            projectId: null,
            pageId: null,
            kind: 'site',
            sector: null,
            tree: { version: 1, blocks: [] },
            selectedIndex: null,
            device: 'desktop',
            saveTimer: null,
            saveStatus: 'saved',
        },

        init(config) {
            Object.assign(this.state, config);
            this.bindDevices();
            this.bindBlockLibrary();
            this.bindCanvas();
            this.bindTopbar();
            this.bindDragDrop();
            this.render();
        },

        bindDevices() {
            document.querySelectorAll('[data-bldr-device]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const dev = btn.getAttribute('data-bldr-device');
                    this.state.device = dev;
                    document.querySelector('[data-bldr-canvas]')?.setAttribute('data-device', dev);
                    document.querySelectorAll('[data-bldr-device]').forEach(b => b.classList.toggle('is-active', b === btn));
                });
            });
        },

        bindBlockLibrary() {
            document.querySelectorAll('[data-bldr-add-block]').forEach(item => {
                item.addEventListener('click', () => {
                    const type = item.getAttribute('data-bldr-add-block');
                    this.addBlock(type);
                });
            });
            const search = document.querySelector('[data-bldr-block-search]');
            if (search) {
                search.addEventListener('input', () => {
                    const q = search.value.toLowerCase();
                    document.querySelectorAll('[data-bldr-add-block]').forEach(item => {
                        const label = (item.textContent || '').toLowerCase();
                        item.style.display = label.includes(q) ? '' : 'none';
                    });
                });
            }
        },

        bindDragDrop() {
            const container = document.querySelector('[data-bldr-block-list]');
            if (!container) return;
            // Event delegation — her render sonrası tekrar bağlanmadan çalışsın
            container.addEventListener('dragstart', (e) => {
                const block = e.target.closest('[data-bldr-block-index]');
                if (!block) return;
                block.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', block.getAttribute('data-bldr-block-index'));
            });
            container.addEventListener('dragend', (e) => {
                const block = e.target.closest('[data-bldr-block-index]');
                if (block) block.classList.remove('is-dragging');
                container.querySelectorAll('.aho-bldr-drop-line').forEach(l => l.remove());
            });
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const target = e.target.closest('[data-bldr-block-index]');
                container.querySelectorAll('.aho-bldr-drop-line').forEach(l => l.remove());
                if (!target) return;
                const rect = target.getBoundingClientRect();
                const line = document.createElement('div');
                line.className = 'aho-bldr-drop-line';
                line.style.cssText = 'height:3px;background:#0ea5e9;margin:2px 0;border-radius:2px';
                if (e.clientY < rect.top + rect.height / 2) {
                    target.parentNode.insertBefore(line, target);
                } else {
                    target.parentNode.insertBefore(line, target.nextSibling);
                }
            });
            container.addEventListener('drop', (e) => {
                e.preventDefault();
                const fromIdx = parseInt(e.dataTransfer.getData('text/plain'), 10);
                if (isNaN(fromIdx)) return;
                const target = e.target.closest('[data-bldr-block-index]');
                container.querySelectorAll('.aho-bldr-drop-line').forEach(l => l.remove());
                if (!target) return;
                let toIdx = parseInt(target.getAttribute('data-bldr-block-index'), 10);
                const rect = target.getBoundingClientRect();
                if (e.clientY >= rect.top + rect.height / 2) toIdx++;
                if (toIdx > fromIdx) toIdx--;
                if (fromIdx === toIdx) return;
                const arr = this.state.tree.blocks;
                const [moved] = arr.splice(fromIdx, 1);
                arr.splice(toIdx, 0, moved);
                this.state.selectedIndex = toIdx;
                this.render();
                this.scheduleAutoSave();
            });
        },

        bindCanvas() {
            const container = document.querySelector('[data-bldr-block-list]');
            if (!container) return;
            container.addEventListener('click', (e) => {
                const block = e.target.closest('[data-bldr-block-index]');
                if (!block) return;
                if (e.target.closest('[data-bldr-resize-handle]')) return;

                const idx = parseInt(block.getAttribute('data-bldr-block-index'), 10);

                if (e.target.closest('[data-bldr-block-action="delete"]')) {
                    e.stopPropagation();
                    if (!confirm('Bu bloğu silmek istediğinize emin misiniz?')) return;
                    this.removeBlock(idx);
                    return;
                }
                if (e.target.closest('[data-bldr-block-action="duplicate"]')) {
                    e.stopPropagation();
                    this.duplicateBlock(idx);
                    return;
                }
                if (e.target.closest('[data-bldr-block-action="up"]')) {
                    e.stopPropagation();
                    this.moveBlock(idx, -1);
                    return;
                }
                if (e.target.closest('[data-bldr-block-action="down"]')) {
                    e.stopPropagation();
                    this.moveBlock(idx, +1);
                    return;
                }
                this.selectBlock(idx);
            });

            container.addEventListener('pointerdown', (e) => {
                const handle = e.target.closest('[data-bldr-resize-handle]');
                if (!handle) return;
                e.preventDefault();
                e.stopPropagation();
                const blockEl = handle.closest('[data-bldr-block-index]');
                if (!blockEl) return;
                const idx = parseInt(blockEl.getAttribute('data-bldr-block-index'), 10);
                if (isNaN(idx) || !this.state.tree.blocks[idx]) return;
                this.selectBlock(idx);
                this.startResize(e, idx, blockEl);
            });
        },

        bindTopbar() {
            const preview = document.querySelector('[data-bldr-preview]');
            if (preview) preview.addEventListener('click', () => {
                window.open(`/panel/builder/${this.state.projectId}/preview`, '_blank');
            });
            const exportBtn = document.querySelector('[data-bldr-export]');
            if (exportBtn) exportBtn.addEventListener('click', () => {
                window.location.href = `/panel/builder/${this.state.projectId}/export`;
            });
        },

        addBlock(type) {
            this.state.tree.blocks = this.state.tree.blocks || [];
            this.state.tree.blocks.push({ type, props: this.defaultProps(type) });
            this.state.selectedIndex = this.state.tree.blocks.length - 1;
            this.render();
            this.scheduleAutoSave();
        },

        removeBlock(idx) {
            this.state.tree.blocks.splice(idx, 1);
            this.state.selectedIndex = null;
            this.render();
            this.scheduleAutoSave();
        },

        duplicateBlock(idx) {
            const clone = JSON.parse(JSON.stringify(this.state.tree.blocks[idx]));
            this.state.tree.blocks.splice(idx + 1, 0, clone);
            this.state.selectedIndex = idx + 1;
            this.render();
            this.scheduleAutoSave();
        },

        moveBlock(idx, delta) {
            const arr = this.state.tree.blocks;
            const to = idx + delta;
            if (to < 0 || to >= arr.length) return;
            [arr[idx], arr[to]] = [arr[to], arr[idx]];
            this.state.selectedIndex = to;
            this.render();
            this.scheduleAutoSave();
        },

        selectBlock(idx) {
            this.state.selectedIndex = idx;
            this.ensureBlockProps(idx);
            this.renderProps();
            document.querySelectorAll('[data-bldr-block-index]').forEach(el => {
                el.classList.toggle('is-selected', parseInt(el.getAttribute('data-bldr-block-index'), 10) === idx);
            });
        },

        ensureBlockProps(idx) {
            const block = this.state.tree.blocks?.[idx];
            if (!block) return;
            const fresh = this.defaultProps(block.type);
            block.props = { ...fresh, ...(block.props || {}) };
        },

        defaultProps(type) {
            // Her blok tipinde ortak style props: bg_color, text_color, bg_image, padding_y, font_size
            const commonStyle = {
                bg_color: '',
                text_color: '',
                bg_image: '',
                padding_y: 60,
                font_size: 16,
                align: 'left',
            };
            const defaults = {
                hero: { title: 'Yeni Başlık', subtitle: 'Alt başlık metni', cta_text: 'Devam', cta_link: '#', ...commonStyle, padding_y: 120 },
                features: { title: 'Özellikler', items: ['Özellik 1', 'Özellik 2', 'Özellik 3'], ...commonStyle },
                cta: { title: 'Harekete Geçin', button: 'Başla', ...commonStyle },
                button: { text: 'Buton', link: '#', align: 'left', ...commonStyle, padding_y: 20, width: 180, height: 48 },
                text: { content: 'Yeni metin bloğu', ...commonStyle },
                heading: { title: 'Yeni Başlık', ...commonStyle },
                about: { title: 'Hakkımızda', content: 'Hakkımızda metni', ...commonStyle },
                footer: { copyright: '© ' + new Date().getFullYear(), ...commonStyle, padding_y: 30 },
                gallery: { title: 'Galeri', images: [], ...commonStyle },
                pricing: { title: 'Fiyatlandırma', plans: ['Başlangıç: 39 TL', 'Business: 89 TL'], ...commonStyle },
                testimonials: { title: 'Müşteri Yorumları', items: ['Harika hizmet!'], ...commonStyle },
                contact: { title: 'İletişim', email: 'info@ornek.com', phone: '', ...commonStyle },
                faq: { title: 'Sık Sorulanlar', items: ['Soru?|Cevap.'], ...commonStyle },
                radio_player: { title: 'Canlı Yayın', station: 'Ahost Radio', stream_url: '', button: 'Dinle', ...commonStyle, padding_y: 28, width: 360, height: 132 },
                now_playing: { title: 'Şu An Çalan', artist: 'Sanatçı', track: 'Parça adı', ...commonStyle, padding_y: 20, width: 360, height: 106 },
                song_request: { title: 'Şarkı İsteği', placeholder: 'İstediğiniz şarkı', button: 'Gönder', ...commonStyle, padding_y: 22, width: 360, height: 128 },
                search_box: { placeholder: 'Ara...', button: 'Ara', ...commonStyle, padding_y: 20, width: 360, height: 88 },
                contact_form: { title: 'İletişim Formu', button: 'Gönder', ...commonStyle, padding_y: 24, width: 360, height: 190 },
                newsletter: { title: 'Bülten Kaydı', placeholder: 'E-posta adresiniz', button: 'Kaydol', ...commonStyle, padding_y: 22, width: 360, height: 126 },
            };
            return defaults[type] || { ...commonStyle };
        },

        /** Bir prop için akıllı input tipi seç */
        propInputType(key, val) {
            const k = key.toLowerCase();
            if (k.endsWith('_color') || k === 'color' || k === 'bg' || k === 'background') return 'color';
            if (k.endsWith('_image') || k === 'image' || k === 'image_url' || k === 'logo') return 'image';
            if (k.endsWith('_link') || k === 'link' || k === 'url' || k === 'href') return 'url';
            if (k === 'align') return 'align';
            if (k === 'padding_y' || k === 'padding_x' || k === 'font_size' || k.endsWith('_size') || k === 'height' || k === 'width') return 'number';
            if (Array.isArray(val)) return 'array';
            if (typeof val === 'string' && val.length > 60) return 'textarea';
            return 'text';
        },

        render() {
            const container = document.querySelector('[data-bldr-block-list]');
            if (!container) return;
            const blocks = this.state.tree.blocks || [];
            if (blocks.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center;padding:4rem 1rem;color:var(--aho-color-ink-400)">
                        <div style="font-size:3rem;margin-bottom:1rem">📦</div>
                        <div style="font-weight:600;margin-bottom:0.5rem">Sayfanız boş</div>
                        <div style="font-size:.875rem">Sol paneldeki bloklardan birine tıklayarak sayfaya ekleyin.</div>
                    </div>
                `;
                return;
            }
            container.innerHTML = blocks.map((b, i) => this.renderBlock(b, i)).join('');
            if (this.state.selectedIndex !== null) {
                const el = container.querySelector(`[data-bldr-block-index="${this.state.selectedIndex}"]`);
                if (el) el.classList.add('is-selected');
            }
            this.renderProps();
        },

        renderBlock(block, i) {
            const preview = this.enhancedBlockPreview(block);
            const p = block.props || {};
            const sizeStyle = [
                p.width ? `width:${Math.max(80, Number(p.width) || 0)}px` : '',
                p.height ? `min-height:${Math.max(44, Number(p.height) || 0)}px` : '',
                p.bg_color ? `--block-bg:${this.escape(p.bg_color)}` : '',
                p.text_color ? `--block-color:${this.escape(p.text_color)}` : '',
            ].filter(Boolean).join(';');
            return `
                <div class="aho-bldr-block is-align-${this.escape(p.align || 'left')}" data-bldr-block-index="${i}" draggable="true" title="Sürükleyerek sırayı değiştir" style="${sizeStyle}">
                    <div class="aho-bldr-block__actions">
                        <span class="aho-bldr-block__drag" title="Sürükle" style="cursor:grab;padding:0 6px">⋮⋮</span>
                        <button data-bldr-block-action="up" title="Yukarı">↑</button>
                        <button data-bldr-block-action="down" title="Aşağı">↓</button>
                        <button data-bldr-block-action="duplicate" title="Kopyala">⧉</button>
                        <button data-bldr-block-action="delete" class="is-danger" title="Sil">🗑</button>
                    </div>
                    <div class="aho-bldr-block__type">${this.escape(block.type)}</div>
                    <div class="aho-bldr-block__preview">${preview}</div>
                    <span class="aho-bldr-resize-handle" data-bldr-resize-handle title="Fare ile boyutlandır"></span>
                </div>
            `;
        },

        enhancedBlockPreview(block) {
            const p = block.props || {};
            const align = this.escape(p.align || 'left');
            switch (block.type) {
                case 'hero':
                    return `<div class="aho-bldr-preview-hero is-${align}"><strong>${this.escape(p.title || 'Başlık')}</strong><small>${this.escape(p.subtitle || '')}</small><span>${this.escape(p.cta_text || 'Devam')}</span></div>`;
                case 'features':
                    return `<div class="aho-bldr-preview-card is-${align}"><strong>${this.escape(p.title || 'Özellikler')}</strong><div class="aho-bldr-preview-pills">${(p.items || []).slice(0, 4).map(x => `<span>${this.escape(x)}</span>`).join('')}</div></div>`;
                case 'cta':
                    return `<div class="aho-bldr-preview-cta is-${align}"><strong>${this.escape(p.title || 'CTA')}</strong><button>${this.escape(p.button || 'Başla')}</button></div>`;
                case 'button':
                    return `<div class="aho-bldr-preview-button is-${align}"><button>${this.escape(p.text || p.button || 'Buton')}</button></div>`;
                case 'text':
                    return `<div class="aho-bldr-preview-text is-${align}">${this.escape((p.content || '').substring(0, 160) || 'Metin bloğu')}</div>`;
                case 'heading':
                    return `<div class="aho-bldr-preview-heading is-${align}">${this.escape(p.title || 'Başlık')}</div>`;
                case 'footer':
                    return `<div class="aho-bldr-preview-footer is-${align}">${this.escape(p.copyright || 'Footer')}</div>`;
                case 'radio_player':
                    return `<div class="aho-bldr-preview-radio is-${align}"><div><strong>${this.escape(p.title || 'Canlı Yayın')}</strong><small>${this.escape(p.station || 'Radyo')}</small><em>${p.stream_url ? this.escape(p.stream_url) : 'Radyo URL bekleniyor'}</em></div><button>▶ ${this.escape(p.button || 'Dinle')}</button></div>`;
                case 'now_playing':
                    return `<div class="aho-bldr-preview-now is-${align}"><span>Şu An Çalan</span><strong>${this.escape(p.track || 'Parça adı')}</strong><small>${this.escape(p.artist || 'Sanatçı')}</small></div>`;
                case 'song_request':
                    return `<div class="aho-bldr-preview-form is-${align}"><strong>${this.escape(p.title || 'Şarkı İsteği')}</strong><div><input placeholder="${this.escape(p.placeholder || 'Şarkı adı')}"><button>${this.escape(p.button || 'Gönder')}</button></div></div>`;
                case 'search_box':
                    return `<div class="aho-bldr-preview-form is-${align}"><div><input placeholder="${this.escape(p.placeholder || 'Ara...')}"><button>${this.escape(p.button || 'Ara')}</button></div></div>`;
                case 'contact_form':
                    return `<div class="aho-bldr-preview-contact is-${align}"><strong>${this.escape(p.title || 'İletişim Formu')}</strong><input placeholder="Ad Soyad"><input placeholder="E-posta"><textarea placeholder="Mesaj"></textarea><button>${this.escape(p.button || 'Gönder')}</button></div>`;
                case 'newsletter':
                    return `<div class="aho-bldr-preview-form is-${align}"><strong>${this.escape(p.title || 'Bülten Kaydı')}</strong><div><input placeholder="${this.escape(p.placeholder || 'E-posta')}"><button>${this.escape(p.button || 'Kaydol')}</button></div></div>`;
                default:
                    return `<div class="aho-bldr-preview-card is-${align}"><strong>${this.escape(block.type)}</strong><small>Blok önizlemesi</small></div>`;
            }
        },

        blockPreview(block) {
            const p = block.props || {};
            switch (block.type) {
                case 'hero':     return `<strong>${this.escape(p.title || 'Başlık')}</strong><br><small>${this.escape(p.subtitle || '')}</small>`;
                case 'features': return `${this.escape(p.title || 'Özellikler')} — ${(p.items || []).length} adet`;
                case 'cta':      return this.escape(p.title || 'CTA');
                case 'text':     return this.escape((p.content || '').substring(0, 100));
                case 'footer':   return `Footer — ${this.escape(p.copyright || '')}`;
                default:         return `<em>${this.escape(block.type)} bloğu</em>`;
            }
        },

        renderProps() {
            const panel = document.querySelector('[data-bldr-props]');
            if (!panel) return;
            const idx = this.state.selectedIndex;
            if (idx === null || !this.state.tree.blocks[idx]) {
                panel.innerHTML = `
                    <div class="aho-bldr-props__title">Özellikler</div>
                    <div class="aho-bldr-props__empty">Bir bloğa tıklayın</div>
                `;
                return;
            }
            const block = this.state.tree.blocks[idx];
            const p = block.props || {};
            let html = `<div class="aho-bldr-props__title">✏️ ${this.escape(block.type)} bloğu</div>`;

            // İçerik props (style hariç)
            const styleKeys = ['bg_color','text_color','bg_image','padding_y','padding_x','font_size','align','width','height'];
            const contentKeys = Object.keys(p).filter(k => !styleKeys.includes(k));
            const stylePropsHave = Object.keys(p).filter(k => styleKeys.includes(k));

            if (contentKeys.length) {
                html += `<div style="font-size:11px;text-transform:uppercase;font-weight:700;color:#6b7280;margin:12px 8px 6px;letter-spacing:.5px">📝 İçerik</div>`;
                for (const key of contentKeys) {
                    html += this.renderPropInput(key, p[key]);
                }
            }

            if (stylePropsHave.length) {
                html += `<div style="font-size:11px;text-transform:uppercase;font-weight:700;color:#6b7280;margin:16px 8px 6px;letter-spacing:.5px">🎨 Stil</div>`;
                for (const key of stylePropsHave) {
                    html += this.renderPropInput(key, p[key]);
                }
            }

            // "+ Stil özelliği ekle" butonu (eğer style eksikse)
            const missingStyle = styleKeys.filter(k => !(k in p));
            if (missingStyle.length) {
                html += `<div style="padding:12px 8px"><button type="button" data-bldr-add-style class="aho-btn aho-btn--sm aho-btn--outline" style="width:100%">+ Stil özellikleri ekle</button></div>`;
            }

            panel.innerHTML = html;

            // Property change listener
            panel.querySelectorAll('[data-bldr-prop]').forEach(inp => {
                inp.addEventListener('input', () => {
                    const key = inp.getAttribute('data-bldr-prop');
                    const type = inp.getAttribute('data-bldr-prop-type');
                    let val = inp.value;
                    if (type === 'array') val = inp.value.split('\n').filter(x => x.trim() !== '');
                    else if (type === 'number') val = Number(inp.value) || 0;
                    this.state.tree.blocks[idx].props[key] = val;
                    this.updateBlockPreview(idx);
                    // Style değiştiyse canvas'ta canlı önizleme
                if (styleKeys.includes(key)) this.applyBlockStyle(idx);
                if (key === 'align') this.render();
                this.scheduleAutoSave();
                });
            });

            panel.querySelectorAll('[data-bldr-align]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-bldr-align') || 'left';
                    this.state.tree.blocks[idx].props.align = val;
                    this.renderProps();
                    this.render();
                    this.scheduleAutoSave();
                });
            });

            // Image upload button
            panel.querySelectorAll('[data-bldr-upload]').forEach(btn => {
                btn.addEventListener('click', () => this.uploadImage(btn.getAttribute('data-bldr-upload'), idx));
            });

            // "Stil ekle" butonu
            const addStyleBtn = panel.querySelector('[data-bldr-add-style]');
            if (addStyleBtn) {
                addStyleBtn.addEventListener('click', () => {
                    missingStyle.forEach(k => {
                        this.state.tree.blocks[idx].props[k] = k === 'padding_y' ? 60 : (k === 'font_size' ? 16 : '');
                    });
                    this.renderProps();
                });
            }
        },

        renderPropInput(key, val) {
            const type = this.propInputType(key, val);
            const safeKey = this.escape(key);
            const label = this.escape(this.humanKey(key));
            const raw = this.escape(String(val ?? ''));

            if (type === 'color') {
                const cur = val || '#ffffff';
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">${label}</label>
                    <div style="display:flex;gap:6px;align-items:center">
                        <input type="color" data-bldr-prop="${safeKey}" value="${this.escape(cur)}" style="width:46px;height:36px;padding:2px;border:1px solid #d1d5db;border-radius:6px;cursor:pointer">
                        <input class="aho-bldr-props__input" style="flex:1" type="text" data-bldr-prop="${safeKey}" value="${raw}" placeholder="#ffffff veya boş">
                    </div>
                </div>`;
            }

            if (type === 'image') {
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">${label}</label>
                    <input class="aho-bldr-props__input" type="text" data-bldr-prop="${safeKey}" value="${raw}" placeholder="https://... veya boş">
                    <button type="button" data-bldr-upload="${safeKey}" class="aho-btn aho-btn--sm aho-btn--outline" style="margin-top:4px;width:100%">📤 Görsel Yükle</button>
                    ${raw ? `<img src="${raw}" style="margin-top:6px;max-width:100%;max-height:120px;border-radius:6px;border:1px solid #e5e7eb">` : ''}
                </div>`;
            }

            if (type === 'number') {
                const num = Number(val) || 0;
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">${label}: <strong>${num}</strong>${key.includes('padding') || key.includes('size') || key === 'height' || key === 'width' ? 'px' : ''}</label>
                    <input type="range" data-bldr-prop="${safeKey}" data-bldr-prop-type="number" min="0" max="${key.includes('padding') ? 200 : (key === 'font_size' ? 72 : 500)}" value="${num}" style="width:100%">
                </div>`;
            }

            if (type === 'url') {
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">🔗 ${label}</label>
                    <input class="aho-bldr-props__input" type="url" data-bldr-prop="${safeKey}" value="${raw}" placeholder="https://...">
                </div>`;
            }

            if (type === 'align') {
                const current = String(val || 'left');
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">${label}</label>
                    <div class="aho-bldr-align-control">
                        ${['left','center','right'].map(v => `<button type="button" data-bldr-align="${v}" class="${current === v ? 'is-active' : ''}">${v === 'left' ? 'Sol' : (v === 'center' ? 'Orta' : 'Sağ')}</button>`).join('')}
                    </div>
                    <input type="hidden" data-bldr-prop="${safeKey}" value="${this.escape(current)}">
                </div>`;
            }

            if (type === 'array') {
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">${label} <small>(satır satır)</small></label>
                    <textarea class="aho-bldr-props__textarea" rows="4" data-bldr-prop="${safeKey}" data-bldr-prop-type="array">${this.escape((val || []).join('\n'))}</textarea>
                </div>`;
            }

            if (type === 'textarea') {
                return `<div class="aho-bldr-props__group">
                    <label class="aho-bldr-props__label">${label}</label>
                    <textarea class="aho-bldr-props__textarea" rows="3" data-bldr-prop="${safeKey}">${raw}</textarea>
                </div>`;
            }

            return `<div class="aho-bldr-props__group">
                <label class="aho-bldr-props__label">${label}</label>
                <input class="aho-bldr-props__input" type="text" data-bldr-prop="${safeKey}" value="${raw}">
            </div>`;
        },

        humanKey(k) {
            const map = {
                title: 'Başlık', subtitle: 'Alt Başlık', content: 'İçerik', cta_text: 'Buton Yazısı',
                cta_link: 'Buton Linki', button: 'Buton', copyright: 'Telif Metni',
                bg_color: 'Arka Plan Rengi', text_color: 'Yazı Rengi', bg_image: 'Arka Plan Görseli',
                padding_y: 'Dikey Boşluk', padding_x: 'Yatay Boşluk', font_size: 'Yazı Boyutu',
                items: 'Öğeler', plans: 'Paketler', images: 'Görseller',
                email: 'E-posta', phone: 'Telefon', logo: 'Logo',
            };
            return map[k] || k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        },

        applyBlockStyle(idx) {
            // Canvas'ta gerçek zamanlı stil uygulama (opsiyonel — iframe/canvas rendering)
            const el = document.querySelector(`[data-bldr-block-index="${idx}"]`);
            if (!el) return;
            const p = this.state.tree.blocks[idx].props || {};
            if (p.bg_color) el.style.setProperty('--block-bg', p.bg_color);
            if (p.text_color) el.style.setProperty('--block-color', p.text_color);
        },

        startResize(e, idx, blockEl) {
            const startX = e.clientX;
            const startY = e.clientY;
            const startRect = blockEl.getBoundingClientRect();
            const block = this.state.tree.blocks[idx];
            block.props = block.props || {};
            const minW = 96;
            const minH = 44;
            blockEl.classList.add('is-resizing');
            const onMove = (ev) => {
                const nextW = Math.max(minW, Math.round(startRect.width + (ev.clientX - startX)));
                const nextH = Math.max(minH, Math.round(startRect.height + (ev.clientY - startY)));
                block.props.width = nextW;
                block.props.height = nextH;
                blockEl.style.width = nextW + 'px';
                blockEl.style.minHeight = nextH + 'px';
            };
            const onUp = () => {
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                blockEl.classList.remove('is-resizing');
                this.renderProps();
                this.scheduleAutoSave();
            };
            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp, { once: true });
        },

        async uploadImage(key, idx) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = async () => {
                const file = input.files[0];
                if (!file) return;
                const fd = new FormData();
                fd.append('_csrf', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('file', file);
                fd.append('project_id', this.state.projectId);
                try {
                    const res = await fetch('/panel/builder/upload', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.ok && data.url) {
                        this.state.tree.blocks[idx].props[key] = data.url;
                        this.renderProps();
                        this.scheduleAutoSave();
                    } else {
                        alert('Yükleme başarısız: ' + (data.error || 'bilinmiyor'));
                    }
                } catch (e) {
                    alert('Yükleme hatası: ' + e.message);
                }
            };
            input.click();
        },

        updateBlockPreview(idx) {
            const el = document.querySelector(`[data-bldr-block-index="${idx}"] .aho-bldr-block__preview`);
            if (el) el.innerHTML = this.enhancedBlockPreview(this.state.tree.blocks[idx]);
        },

        scheduleAutoSave() {
            clearTimeout(this.state.saveTimer);
            this.setSaveStatus('saving');
            this.state.saveTimer = setTimeout(() => this.save(), 800);
        },

        async save() {
            const url = `/panel/builder/${this.state.projectId}/pages/${this.state.pageId}`;
            try {
                const body = new URLSearchParams();
                body.append('_csrf', document.querySelector('meta[name="csrf-token"]').content);
                body.append('tree', JSON.stringify(this.state.tree));
                // Ancak backend JSON de kabul eder — fetch ile hem JSON hem form denemesi:
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ tree: this.state.tree }),
                });
                const data = await res.json();
                if (data.ok) this.setSaveStatus('saved');
                else this.setSaveStatus('error');
            } catch (e) {
                this.setSaveStatus('error');
            }
        },

        setSaveStatus(status) {
            this.state.saveStatus = status;
            const el = document.querySelector('[data-bldr-save-status]');
            if (!el) return;
            el.classList.remove('is-saved', 'is-saving', 'is-error');
            const map = {
                saved:  { cls: 'is-saved',  text: '✓ Kaydedildi' },
                saving: { cls: 'is-saving', text: '💾 Kaydediliyor…' },
                error:  { cls: 'is-error',  text: '⚠ Hata' },
            };
            const m = map[status] || map.saved;
            el.classList.add(m.cls);
            el.textContent = m.text;
        },

        escape(s) {
            return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        },
    };

    App.modules.Builder = Builder;
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
