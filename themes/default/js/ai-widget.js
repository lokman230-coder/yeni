/**
 * Ahost Bilisim - AI Widget (public/customer/admin ortak)
 * data-aho-ai-context attribute uzerinden baglam alir.
 */
(function (App) {
    'use strict';

    const AiWidget = {
        state: {
            context: 'public',
            endpoint: '/ai/public',
            open: false,
            sending: false,
            storageKey: 'aho_ai_history_public',
        },

        init() {
            const root = document.querySelector('[data-aho-ai-widget]');
            if (!root) return;
            this.state.context = root.getAttribute('data-aho-ai-context') || 'public';
            this.state.endpoint = '/ai/' + this.state.context;
            this.state.storageKey = 'aho_ai_history_' + this.state.context;

            const toggle = root.querySelector('[data-aho-ai-toggle]');
            const closeBtn = root.querySelector('[data-aho-ai-close]');
            const clearBtn = root.querySelector('[data-aho-ai-clear]');
            const form = root.querySelector('[data-aho-ai-form]');
            const input = root.querySelector('[data-aho-ai-input]');
            const messages = root.querySelector('[data-aho-ai-messages]');

            this.restore(messages);

            toggle.addEventListener('click', () => this.toggle(root));
            closeBtn.addEventListener('click', () => this.close(root));
            if (clearBtn) {
                clearBtn.addEventListener('click', () => this.clear(messages));
            }

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const msg = input.value.trim();
                if (msg && !this.state.sending) {
                    input.value = '';
                    this.send(root, msg);
                }
            });

            root.querySelectorAll('[data-aho-ai-suggest]').forEach(chip => {
                chip.addEventListener('click', () => {
                    input.value = chip.textContent.trim();
                    form.requestSubmit();
                });
            });

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
            root.querySelector('[data-aho-ai-toggle]').innerHTML = 'x';
            this.state.open = true;
        },

        close(root) {
            root.classList.remove('is-open');
            const toggle = root.querySelector('[data-aho-ai-toggle]');
            toggle.classList.remove('is-active');
            toggle.innerHTML = 'AI';
            this.state.open = false;
        },

        async send(root, message) {
            const msgs = root.querySelector('[data-aho-ai-messages]');
            const sendBtn = root.querySelector('[data-aho-ai-send]');

            this.state.sending = true;
            sendBtn.disabled = true;

            this.addMessage(msgs, 'user', message);
            const typing = document.createElement('div');
            typing.className = 'aho-ai-msg aho-ai-msg--typing';
            typing.textContent = 'Dusunuyor';
            msgs.appendChild(typing);
            msgs.scrollTop = msgs.scrollHeight;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch(this.state.endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: message, _csrf: csrf }),
                });
                const data = await res.json();
                typing.remove();

                if (data.ok) {
                    if (data.action === 'builder:offer') {
                        this.addMessage(msgs, 'bot', data.reply);
                        this.addBuilderOffer(msgs);
                    } else {
                        this.addMessage(msgs, 'bot', data.reply);
                        const url = this.actionUrl(data.action);
                        if (url) this.addActionMessage(msgs, url);
                    }
                } else {
                    this.addMessage(msgs, 'bot', 'Bir sorun olustu: ' + (data.error || 'bilinmeyen'));
                }
            } catch (e) {
                typing.remove();
                this.addMessage(msgs, 'bot', 'Sunucuya ulasilamadi: ' + e.message);
            } finally {
                this.state.sending = false;
                sendBtn.disabled = false;
                msgs.scrollTop = msgs.scrollHeight;
            }
        },

        addMessage(container, role, text, persist = true) {
            const el = document.createElement('div');
            el.className = 'aho-ai-msg aho-ai-msg--' + role;
            el.textContent = text;
            container.appendChild(el);
            container.scrollTop = container.scrollHeight;
            if (persist) this.persist({ type: 'message', role, text });
        },

        addActionMessage(container, url, persist = true) {
            const el = document.createElement('div');
            el.className = 'aho-ai-msg aho-ai-msg--action';
            el.innerHTML = '&rarr; <a href="' + this.escape(url) + '">' + this.escape(url) + ' sayfasina git</a>';
            container.appendChild(el);
            container.scrollTop = container.scrollHeight;
            if (persist) this.persist({ type: 'action', url });
        },

        addBuilderOffer(container, persist = true) {
            const box = document.createElement('div');
            box.className = 'aho-ai-msg aho-ai-msg--action';
            box.innerHTML = `
                <span>AI</span>
                <div>
                    Nasil devam edelim?
                    <div class="aho-ai-msg-buttons">
                        <a href="/site-builder">AI ile Paket Al</a>
                        <a href="/site-builder">Demo Dene</a>
                    </div>
                </div>
            `;
            container.appendChild(box);
            container.scrollTop = container.scrollHeight;
            if (persist) this.persist({ type: 'builder_offer' });
        },

        actionUrl(action) {
            if (!action) return null;
            if (typeof action === 'string' && action.startsWith('navigate:')) {
                return action.substring(9);
            }
            if (typeof action === 'object' && action.action === 'navigate') {
                return action.url;
            }
            return null;
        },

        restore(container) {
            let history = [];
            try {
                history = JSON.parse(localStorage.getItem(this.state.storageKey) || '[]');
            } catch (e) {
                history = [];
            }
            if (!Array.isArray(history) || history.length === 0) return;

            const welcome = container.querySelector('[data-aho-ai-welcome]');
            container.innerHTML = '';
            if (welcome) container.appendChild(welcome);

            history.slice(-80).forEach(item => {
                if (item.type === 'message') this.addMessage(container, item.role, item.text, false);
                if (item.type === 'action') this.addActionMessage(container, item.url, false);
                if (item.type === 'builder_offer') this.addBuilderOffer(container, false);
            });
        },

        clear(container) {
            localStorage.removeItem(this.state.storageKey);
            const welcome = container.querySelector('[data-aho-ai-welcome]');
            container.innerHTML = '';
            if (welcome) container.appendChild(welcome);
        },

        persist(item) {
            let history = [];
            try {
                history = JSON.parse(localStorage.getItem(this.state.storageKey) || '[]');
            } catch (e) {
                history = [];
            }
            if (!Array.isArray(history)) history = [];
            history.push(item);
            localStorage.setItem(this.state.storageKey, JSON.stringify(history.slice(-80)));
        },

        escape(s) {
            return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        },
    };

    App.modules.AiWidget = AiWidget;
    document.addEventListener('DOMContentLoaded', () => AiWidget.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
