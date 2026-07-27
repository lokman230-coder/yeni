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

        bindCanvas() {
            const container = document.querySelector('[data-bldr-block-list]');
            if (!container) return;
            container.addEventListener('click', (e) => {
                const block = e.target.closest('[data-bldr-block-index]');
                if (!block) return;

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
            this.renderProps();
            document.querySelectorAll('[data-bldr-block-index]').forEach(el => {
                el.classList.toggle('is-selected', parseInt(el.getAttribute('data-bldr-block-index'), 10) === idx);
            });
        },

        defaultProps(type) {
            const defaults = {
                hero: { title: 'Yeni Başlık', subtitle: 'Alt başlık metni', cta_text: 'Devam', cta_link: '#' },
                features: { title: 'Özellikler', items: ['Özellik 1', 'Özellik 2', 'Özellik 3'] },
                cta: { title: 'Harekete Geçin', button: 'Başla' },
                text: { content: 'Yeni metin bloğu' },
                heading: { title: 'Yeni Başlık' },
                about: { title: 'Hakkımızda', content: 'Hakkımızda metni' },
                footer: { copyright: '© ' + new Date().getFullYear() },
            };
            return defaults[type] || {};
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
            const preview = this.blockPreview(block);
            return `
                <div class="aho-bldr-block" data-bldr-block-index="${i}">
                    <div class="aho-bldr-block__actions">
                        <button data-bldr-block-action="up" title="Yukarı">↑</button>
                        <button data-bldr-block-action="down" title="Aşağı">↓</button>
                        <button data-bldr-block-action="duplicate" title="Kopyala">⧉</button>
                        <button data-bldr-block-action="delete" class="is-danger" title="Sil">🗑</button>
                    </div>
                    <div class="aho-bldr-block__type">${this.escape(block.type)}</div>
                    <div class="aho-bldr-block__preview">${preview}</div>
                </div>
            `;
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
            let html = `<div class="aho-bldr-props__title">${this.escape(block.type)}</div>`;
            for (const key of Object.keys(p)) {
                const val = p[key];
                if (Array.isArray(val)) {
                    html += `<div class="aho-bldr-props__group"><label class="aho-bldr-props__label">${this.escape(key)} (satır satır)</label>
                        <textarea class="aho-bldr-props__textarea" rows="4" data-bldr-prop="${key}" data-bldr-prop-type="array">${this.escape(val.join('\n'))}</textarea></div>`;
                } else if (typeof val === 'string' && val.length > 60) {
                    html += `<div class="aho-bldr-props__group"><label class="aho-bldr-props__label">${this.escape(key)}</label>
                        <textarea class="aho-bldr-props__textarea" rows="3" data-bldr-prop="${key}">${this.escape(val)}</textarea></div>`;
                } else {
                    html += `<div class="aho-bldr-props__group"><label class="aho-bldr-props__label">${this.escape(key)}</label>
                        <input class="aho-bldr-props__input" type="text" data-bldr-prop="${key}" value="${this.escape(String(val ?? ''))}"></div>`;
                }
            }
            panel.innerHTML = html;
            panel.querySelectorAll('[data-bldr-prop]').forEach(inp => {
                inp.addEventListener('input', () => {
                    const key = inp.getAttribute('data-bldr-prop');
                    const isArray = inp.getAttribute('data-bldr-prop-type') === 'array';
                    this.state.tree.blocks[idx].props[key] = isArray
                        ? inp.value.split('\n').filter(x => x.trim() !== '')
                        : inp.value;
                    this.updateBlockPreview(idx);
                    this.scheduleAutoSave();
                });
            });
        },

        updateBlockPreview(idx) {
            const el = document.querySelector(`[data-bldr-block-index="${idx}"] .aho-bldr-block__preview`);
            if (el) el.innerHTML = this.blockPreview(this.state.tree.blocks[idx]);
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
