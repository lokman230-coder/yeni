/**
 * Ahost Bilişim — AI Widget (public/customer/admin ortak)
 * data-aho-ai-context attribute üzerinden bağlam alır.
 */
(function (App) {
    'use strict';

    const AiWidget = {
        state: { context: 'public', endpoint: '/ai/public', open: false, sending: false },

        init() {
            const root = document.querySelector('[data-aho-ai-widget]');
            if (!root) return;
            this.state.context = root.getAttribute('data-aho-ai-context') || 'public';
            this.state.endpoint = '/ai/' + this.state.context;

            const toggle = root.querySelector('[data-aho-ai-toggle]');
            const closeBtn = root.querySelector('[data-aho-ai-close]');
            const form  = root.querySelector('[data-aho-ai-form]');
            const input = root.querySelector('[data-aho-ai-input]');

            toggle.addEventListener('click', () => this.toggle(root));
            closeBtn.addEventListener('click', () => this.close(root));

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const msg = input.value.trim();
                if (msg && !this.state.sending) {
                    input.value = '';
                    this.send(root, msg);
                }
            });

            // Öneri chip'lerine click
            root.querySelectorAll('[data-aho-ai-suggest]').forEach(chip => {
                chip.addEventListener('click', () => {
                    const msg = chip.textContent.trim();
                    input.value = msg;
                    form.requestSubmit();
                });
            });

            // Klavye kısayolu: Ctrl+K veya Cmd+K
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.toggle(root);
                    if (this.state.open) setTimeout(() => input.focus(), 100);
                }
            });
        },

        toggle(root) {
            this.state.open ? this.close(root) : this.open(root);
        },

        open(root) {
            root.classList.add('is-open');
            root.querySelector('[data-aho-ai-toggle]').classList.add('is-active');
            root.querySelector('[data-aho-ai-toggle]').innerHTML = '✕';
            this.state.open = true;
        },

        close(root) {
            root.classList.remove('is-open');
            const toggle = root.querySelector('[data-aho-ai-toggle]');
            toggle.classList.remove('is-active');
            toggle.innerHTML = '🤖';
            this.state.open = false;
        },

        async send(root, message) {
            const msgs = root.querySelector('[data-aho-ai-messages]');
            const sendBtn = root.querySelector('[data-aho-ai-send]');

            this.state.sending = true;
            sendBtn.disabled = true;

            // Kullanıcı mesajı
            this.addMessage(msgs, 'user', message);
            // Typing göstergesi
            const typing = document.createElement('div');
            typing.className = 'aho-ai-msg aho-ai-msg--typing';
            typing.textContent = 'Düşünüyor';
            msgs.appendChild(typing);
            msgs.scrollTop = msgs.scrollHeight;

            try {
                const res = await fetch(this.state.endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        _csrf: document.querySelector('meta[name="csrf-token"]').content
                    }),
                });
                const data = await res.json();
                typing.remove();

                if (data.ok) {
                    // Site oluştur özel: 2 buton
                    if (data.action === 'builder:offer') {
                        this.addMessage(msgs, 'bot', data.reply);
                        const box = document.createElement('div');
                        box.className = 'aho-ai-msg aho-ai-msg--action';
                        box.innerHTML = `
                            <span>🎨</span>
                            <div>
                                Nasıl devam edelim?
                                <div class="aho-ai-msg-buttons">
                                    <a href="/site-builder">✨ AI ile Paket Al</a>
                                    <a href="/site-builder">👉 Demo Dene</a>
                                </div>
                            </div>
                        `;
                        msgs.appendChild(box);
                    } else {
                        this.addMessage(msgs, 'bot', data.reply);
                        // Navigate action
                        const action = data.action;
                        if (action) {
                            let url = null;
                            if (typeof action === 'string' && action.startsWith('navigate:')) {
                                url = action.substring(9);
                            } else if (typeof action === 'object' && action.action === 'navigate') {
                                url = action.url;
                            }
                            if (url) this.addActionMessage(msgs, url);
                        }
                    }
                } else {
                    this.addMessage(msgs, 'bot', '⚠ Bir sorun oluştu: ' + (data.error || 'bilinmeyen'));
                }
            } catch (e) {
                typing.remove();
                this.addMessage(msgs, 'bot', '⚠ Sunucuya ulaşılamadı: ' + e.message);
            } finally {
                this.state.sending = false;
                sendBtn.disabled = false;
                msgs.scrollTop = msgs.scrollHeight;
            }
        },

        addMessage(container, role, text) {
            const el = document.createElement('div');
            el.className = 'aho-ai-msg aho-ai-msg--' + role;
            el.textContent = text;
            container.appendChild(el);
            container.scrollTop = container.scrollHeight;
        },

        addActionMessage(container, url) {
            const el = document.createElement('div');
            el.className = 'aho-ai-msg aho-ai-msg--action';
            el.innerHTML = `➡ <a href="${this.escape(url)}">${this.escape(url)} sayfasına git</a>`;
            container.appendChild(el);
            container.scrollTop = container.scrollHeight;
        },

        escape(s) {
            return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        },
    };

    App.modules.AiWidget = AiWidget;
    document.addEventListener('DOMContentLoaded', () => AiWidget.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
