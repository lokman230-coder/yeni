/**
 * Ahost One v25.0.0 RC20 - Live Builder Pro
 * Sürükle-bırak, boyutlandırma, canlı önizleme
 */
(function(){
  'use strict';
  
  const $ = (s, ctx = document) => ctx.querySelector(s);
  const $$ = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
  
  const LiveBuilder = {
    isEditing: false,
    selectedElement: null,
    dragData: null,
    resizeData: null,
    history: [],
    future: [],
    clipboard: null,
    gridSize: 8,
    
    // State
    state: {
      elements: [],
      selectedId: null,
      undoStack: [],
      redoStack: []
    },
    
    init() {
      this.loadState();
      this.bindEvents();
      this.render();
      console.log('[LiveBuilder] Initialized');
    },
    
    loadState() {
      const input = $('#live_builder_json') || $('#customer_builder_json');
      if (input && input.value) {
        try {
          this.state.elements = JSON.parse(input.value);
        } catch(e) {
          this.state.elements = this.getDefaultElements();
        }
      } else {
        this.state.elements = this.getDefaultElements();
      }
    },
    
    saveState() {
      const input = $('#live_builder_json') || $('#customer_builder_json');
      if (input) {
        input.value = JSON.stringify(this.state.elements);
      }
    },
    
    snapshot() {
      this.state.undoStack.push(JSON.stringify(this.state.elements));
      if (this.state.undoStack.length > 50) this.state.undoStack.shift();
      this.state.redoStack = [];
    },
    
    undo() {
      if (this.state.undoStack.length) {
        this.state.redoStack.push(JSON.stringify(this.state.elements));
        this.state.elements = JSON.parse(this.state.undoStack.pop());
        this.saveState();
      }
    },
    
    redo() {
      if (this.state.redoStack.length) {
        this.state.undoStack.push(JSON.stringify(this.state.elements));
        this.state.elements = JSON.parse(this.state.redoStack.pop());
        this.saveState();
      }
    },
    
    getDefaultElements() {
      return [
        {
          id: 'lb_hero',
          type: 'section',
          label: 'Hero Section',
          content: { bgColor: '#0f172a', padding: '60px' },
          children: [
            { id: 'lb_heading1', type: 'heading', label: 'Başlık', content: { text: 'Premium Hosting Çözümleri', level: 'h1' }, style: { fontSize: '48px', color: '#fff' }},
            { id: 'lb_text1', type: 'text', label: 'Metin', content: { text: 'Domain, hosting ve cloud çözümleri tek platformda.' }, style: { fontSize: '18px', color: '#94a3b8' }},
            { id: 'lb_btn1', type: 'button', label: 'Buton', content: { text: 'Hemen Başla', url: '#' }, style: { background: '#3b82f6', color: '#fff' }}
          ]
        },
        {
          id: 'lb_features',
          type: 'section',
          label: 'Özellikler',
          content: { bgColor: '#fff', padding: '40px' },
          children: [
            { id: 'lb_feat1', type: 'feature', label: 'Özellik', content: { icon: '⚡', title: 'Hızlı Kurulum', desc: 'Dakikalar içinde sitenizi yayına alın' }},
            { id: 'lb_feat2', type: 'feature', label: 'Özellik', content: { icon: '🔒', title: 'Güvenli Altyapı', desc: 'SSL sertifikası ve günlük yedekleme' }},
            { id: 'lb_feat3', type: 'feature', label: 'Özellik', content: { icon: '📞', title: '7/24 Destek', desc: 'Uzman ekibimiz her zaman yanınızda' }}
          ]
        },
        {
          id: 'lb_pricing',
          type: 'section',
          label: 'Fiyatlandırma',
          content: { bgColor: '#f8fafc', padding: '50px' },
          children: [
            { id: 'lb_price1', type: 'price_card', label: 'Fiyat', content: { title: 'Başlangıç', price: '₺149', period: '/ay', features: ['5 Domain', '50GB SSD', 'Ücretsiz SSL']}},
            { id: 'lb_price2', type: 'price_card', label: 'Fiyat', content: { title: 'Kurumsal', price: '₺399', period: '/ay', features: ['Sınırsız Domain', '200GB NVMe', 'Priorite Destek'], popular: true }}
          ]
        }
      ];
    },
    
    // Generate unique ID
    uid() {
      return 'lb_' + Math.random().toString(36).substr(2, 9);
    },
    
    // Render the builder
    render() {
      const canvas = $('#lbCanvas');
      if (!canvas) return;
      
      canvas.innerHTML = '';
      this.state.elements.forEach((section, idx) => {
        canvas.appendChild(this.renderSection(section, idx));
      });
      
      // Update JSON input
      this.saveState();
    },
    
    renderSection(section, idx) {
      const div = document.createElement('div');
      div.className = 'lb-section';
      div.dataset.id = section.id;
      div.style.backgroundColor = section.content?.bgColor || '#fff';
      div.style.padding = section.content?.padding || '40px';
      
      // Section controls
      const controls = document.createElement('div');
      controls.className = 'lb-section-controls';
      controls.innerHTML = `
        <span class="lb-section-label">${section.label}</span>
        <div class="lb-section-actions">
          <button type="button" class="lb-btn-icon" data-action="move-up" title="Yukarı Taşı">↑</button>
          <button type="button" class="lb-btn-icon" data-action="move-down" title="Aşağı Taşı">↓</button>
          <button type="button" class="lb-btn-icon" data-action="duplicate" title="Kopyala">⧉</button>
          <button type="button" class="lb-btn-icon lb-btn-danger" data-action="delete" title="Sil">×</button>
        </div>
      `;
      div.appendChild(controls);
      
      // Render children
      const grid = document.createElement('div');
      grid.className = 'lb-grid';
      
      // Determine columns based on children
      const cols = section.children?.length || 1;
      grid.style.gridTemplateColumns = `repeat(${Math.min(cols, 4)}, 1fr)`;
      
      (section.children || []).forEach(child => {
        grid.appendChild(this.renderElement(child));
      });
      
      div.appendChild(grid);
      return div;
    },
    
    renderElement(el) {
      const div = document.createElement('div');
      div.className = 'lb-element';
      div.dataset.id = el.id;
      div.dataset.type = el.type;
      
      if (this.state.selectedId === el.id) {
        div.classList.add('selected');
      }
      
      const style = el.style || {};
      ['width', 'height', 'minHeight', 'maxWidth', 'margin', 'alignSelf'].forEach(prop => {
        if (style[prop]) div.style[prop] = style[prop];
      });
      if (style.textAlign) div.style.textAlign = style.textAlign;
      div.dataset.label = el.label || el.type;
      
      let inner = '';
      switch(el.type) {
        case 'heading':
          const level = el.content?.level || 'h2';
          inner = `<${level} style="margin:0;${this.styleToString(style)}">${el.content?.text || ''}</${level}>`;
          break;
        case 'text':
          inner = `<p style="margin:0;${this.styleToString(style)}">${el.content?.text || ''}</p>`;
          break;
        case 'button':
          inner = `<a href="${el.content?.url || '#'}" class="lb-btn" style="${this.styleToString(style)}">${el.content?.text || 'Buton'}</a>`;
          break;
        case 'image':
          inner = `<img src="${el.content?.src || 'https://placehold.co/600x400'}" alt="" style="max-width:100%;border-radius:12px;${this.styleToString(style)}">`;
          break;
        case 'feature':
          inner = `
            <div class="lb-feature-card">
              <span class="lb-feature-icon">${el.content?.icon || '⭐'}</span>
              <h4 style="margin:12px 0 8px">${el.content?.title || ''}</h4>
              <p style="color:#64748b;margin:0">${el.content?.desc || ''}</p>
            </div>
          `;
          break;
        case 'price_card':
          inner = `
            <div class="lb-price-card ${el.content?.popular ? 'popular' : ''}">
              ${el.content?.popular ? '<span class="lb-popular-badge">Popüler</span>' : ''}
              <h3 style="margin:0 0 8px">${el.content?.title || ''}</h3>
              <div class="lb-price"><strong style="font-size:32px">${el.content?.price || ''}</strong><span>${el.content?.period || ''}</span></div>
              <ul>${(el.content?.features || []).map(f => `<li>✓ ${f}</li>`).join('')}</ul>
              <button class="lb-btn ${el.content?.popular ? '' : 'outline'}">Seç</button>
            </div>
          `;
          break;
        case 'radio_player':
          inner = `
            <div class="lb-radio-card">
              <div class="lb-radio-orb"><span></span></div>
              <div>
                <small>CANLI YAYIN</small>
                <h3>${el.content?.station || el.content?.title || 'Radyo Player'}</h3>
                <p>${el.content?.desc || 'Stream URL girildiğinde play tuşu yayını dener.'}</p>
                <button type="button" class="lb-btn" data-lb-radio-play data-stream-url="${el.content?.streamUrl || ''}">${el.content?.button || 'Dinle'}</button>
                <em data-lb-radio-status>${el.content?.streamUrl ? 'Yayın hazır' : 'Stream URL bekleniyor'}</em>
                <audio preload="none"></audio>
              </div>
            </div>
          `;
          break;
        case 'now_playing':
          inner = `<div class="lb-now-card"><small>ŞU AN ÇALIYOR</small><h3>${el.content?.title || 'Canlı Yayın'}</h3><p>${el.content?.desc || 'Program ve parça bilgisi burada görünür.'}</p></div>`;
          break;
        case 'song_request':
          inner = `<div class="lb-request-card"><h3>${el.content?.title || 'Şarkı İsteği'}</h3><input placeholder="Adınız"><input placeholder="Şarkı adı"><button type="button" class="lb-btn">${el.content?.button || 'İstek Gönder'}</button></div>`;
          break;
        case 'form':
          inner = `<div class="lb-request-card"><h3>${el.content?.title || 'Form'}</h3><input placeholder="Ad Soyad"><input placeholder="E-posta"><textarea placeholder="Mesajınız"></textarea><button type="button" class="lb-btn">${el.content?.button || 'Gönder'}</button></div>`;
          break;
        case 'map':
          inner = `<div class="lb-map-card"><h3>${el.content?.title || 'Konum'}</h3><p>${el.content?.desc || 'Harita ve yol tarifi alanı.'}</p><span>Harita Alanı</span></div>`;
          break;
        case 'notification':
          inner = `<div class="lb-notice-card"><strong>${el.content?.title || 'Bildirim'}</strong><p>${el.content?.desc || 'Kampanya, duyuru veya push bildirim alanı.'}</p></div>`;
          break;
        case 'cart':
          inner = `<div class="lb-cart-card"><h3>${el.content?.title || 'Sepet'}</h3><p>${el.content?.desc || 'Sepet ve ödeme akışı.'}</p><button type="button" class="lb-btn">Sepete Git</button></div>`;
          break;
        default:
          inner = `<div>${el.content?.text || el.type}</div>`;
      }
      
      div.innerHTML = inner;
      
      // Resize handles
      if (this.state.selectedId === el.id) {
        div.innerHTML += `
          <div class="lb-resize-handles">
            <div class="lb-resize-handle" data-pos="nw"></div>
            <div class="lb-resize-handle" data-pos="ne"></div>
            <div class="lb-resize-handle" data-pos="sw"></div>
            <div class="lb-resize-handle" data-pos="se"></div>
          </div>
        `;
      }
      
      return div;
    },
    
    styleToString(style) {
      return Object.entries(style).map(([k, v]) => `${k}:${v}`).join(';');
    },
    
    // Find element by ID
    findElement(id) {
      for (const section of this.state.elements) {
        if (section.id === id) return { section, element: null, parent: this.state.elements };
        if (section.children) {
          for (const child of section.children) {
            if (child.id === id) return { section, element: child, parent: section.children };
          }
        }
      }
      return null;
    },
    
    // Add new element
    addElement(type, sectionId) {
      this.snapshot();
      const section = this.state.elements.find(s => s.id === sectionId);
      if (!section) return;
      
      if (!section.children) section.children = [];
      
      let newEl;
      switch(type) {
        case 'heading':
          newEl = { id: this.uid(), type: 'heading', label: 'Başlık', content: { text: 'Yeni Başlık', level: 'h2' }, style: { fontSize: '32px', color: '#0f172a' }};
          break;
        case 'text':
          newEl = { id: this.uid(), type: 'text', label: 'Metin', content: { text: 'Yeni metin içeriği...' }, style: { fontSize: '16px', color: '#334155' }};
          break;
        case 'button':
          newEl = { id: this.uid(), type: 'button', label: 'Buton', content: { text: 'Buton', url: '#' }, style: { background: '#3b82f6', color: '#fff', padding: '12px 24px' }};
          break;
        case 'image':
          newEl = { id: this.uid(), type: 'image', label: 'Görsel', content: { src: 'https://placehold.co/600x400' }, style: {} };
          break;
        case 'feature':
          newEl = { id: this.uid(), type: 'feature', label: 'Özellik', content: { icon: '⭐', title: 'Yeni Özellik', desc: 'Özellik açıklaması' }};
          break;
        case 'price':
          newEl = { id: this.uid(), type: 'price_card', label: 'Fiyat', content: { title: 'Yeni Plan', price: '₺199', period: '/ay', features: ['Özellik 1', 'Özellik 2']}};
          break;
        case 'divider':
          newEl = { id: this.uid(), type: 'divider', label: 'Ayraç', content: {}, style: { borderTop: '1px solid #e2e8f0', margin: '20px 0' }};
          break;
        case 'spacer':
          newEl = { id: this.uid(), type: 'spacer', label: 'Boşluk', content: {}, style: { height: '40px' }};
          break;
        case 'radio_player':
          newEl = { id: this.uid(), type: 'radio_player', label: 'Radyo Player', content: { station: 'Canlı Radyo', streamUrl: '', desc: 'Stream URL alanını girip play ile yayını deneyin.', button: 'Dinle' }, style: { minHeight: '160px' }};
          break;
        case 'now_playing':
          newEl = { id: this.uid(), type: 'now_playing', label: 'Şu An Çalıyor', content: { title: 'Şu An Yayında', desc: 'Program / parça bilgisi' }, style: {} };
          break;
        case 'song_request':
          newEl = { id: this.uid(), type: 'song_request', label: 'Şarkı İsteği', content: { title: 'Şarkı İsteği', button: 'İstek Gönder' }, style: {} };
          break;
        case 'form':
          newEl = { id: this.uid(), type: 'form', label: 'Form', content: { title: 'İletişim Formu', button: 'Gönder' }, style: {} };
          break;
        case 'map':
          newEl = { id: this.uid(), type: 'map', label: 'Harita', content: { title: 'Konum', desc: 'Google Maps / yol tarifi alanı' }, style: {} };
          break;
        case 'notification':
          newEl = { id: this.uid(), type: 'notification', label: 'Bildirim', content: { title: 'Bildirim', desc: 'Kampanya veya push duyurusu' }, style: {} };
          break;
        case 'cart':
          newEl = { id: this.uid(), type: 'cart', label: 'Sepet', content: { title: 'Sepet', desc: 'Ürün ve ödeme akışı' }, style: {} };
          break;
        default:
          newEl = { id: this.uid(), type: 'text', label: 'Metin', content: { text: 'Yeni içerik' }, style: {} };
      }
      
      section.children.push(newEl);
      this.state.selectedId = newEl.id;
      this.render();
      this.openInspector(newEl.id);
    },
    
    // Add new section
    addSection() {
      this.snapshot();
      const newSection = {
        id: this.uid(),
        type: 'section',
        label: 'Yeni Bölüm',
        content: { bgColor: '#fff', padding: '40px' },
        children: []
      };
      this.state.elements.push(newSection);
      this.render();
    },

    targetSectionId() {
      if (this.state.selectedId) {
        const found = this.findElement(this.state.selectedId);
        if (found?.section?.id) return found.section.id;
      }
      if (!this.state.elements.length) this.addSection();
      return this.state.elements[this.state.elements.length - 1]?.id || null;
    },
    
    // Delete element or section
    deleteElement(id) {
      this.snapshot();
      const found = this.findElement(id);
      if (!found) return;
      
      if (found.element === null) {
        // Delete section
        this.state.elements = this.state.elements.filter(s => s.id !== id);
      } else {
        // Delete from parent
        found.parent = found.parent.filter(c => c.id !== id);
      }
      
      if (this.state.selectedId === id) {
        this.state.selectedId = null;
        this.closeInspector();
      }
      
      this.render();
    },
    
    // Duplicate
    duplicate(id) {
      this.snapshot();
      const found = this.findElement(id);
      if (!found) return;
      
      const clone = JSON.parse(JSON.stringify(found.element || found.section));
      clone.id = this.uid();
      
      if (found.element === null) {
        this.state.elements.push(clone);
      } else {
        const idx = found.parent.findIndex(c => c.id === id);
        found.parent.splice(idx + 1, 0, clone);
      }
      
      this.render();
    },
    
    // Move section
    moveSection(id, direction) {
      this.snapshot();
      const idx = this.state.elements.findIndex(s => s.id === id);
      if (idx === -1) return;
      
      const newIdx = direction === 'up' ? idx - 1 : idx + 1;
      if (newIdx < 0 || newIdx >= this.state.elements.length) return;
      
      const temp = this.state.elements[idx];
      this.state.elements[idx] = this.state.elements[newIdx];
      this.state.elements[newIdx] = temp;
      this.render();
    },
    
    // Open inspector panel
    openInspector(id) {
      const inspector = $('#lbInspector');
      if (!inspector) return;
      
      const found = this.findElement(id);
      if (!found) return;
      
      inspector.classList.add('open');
      inspector.innerHTML = this.buildInspectorHTML(found.element || found.section, found.element !== null);
    },
    
    closeInspector() {
      const inspector = $('#lbInspector');
      if (inspector) inspector.classList.remove('open');
    },
    
    buildInspectorHTML(el, isChild) {
      const type = el.type;
      let html = `<div class="lb-inspector-header">
        <span class="lb-inspector-type">${el.label || type}</span>
        <button type="button" class="lb-btn-icon" data-action="close-inspector">×</button>
      </div>`;
      
      html += '<div class="lb-inspector-body">';
      
      // Content fields based on type
      if (type === 'heading') {
        html += `
          <label>Düzey</label>
          <select class="lb-input" data-field="content.level">
            <option value="h1" ${el.content?.level === 'h1' ? 'selected' : ''}>H1 - Ana Başlık</option>
            <option value="h2" ${el.content?.level === 'h2' ? 'selected' : ''}>H2 - Alt Başlık</option>
            <option value="h3" ${el.content?.level === 'h3' ? 'selected' : ''}>H3 - Küçük Başlık</option>
          </select>
          <label>Metin</label>
          <textarea class="lb-input" data-field="content.text" rows="2">${el.content?.text || ''}</textarea>
        `;
      } else if (type === 'text') {
        html += `
          <label>Metin</label>
          <textarea class="lb-input" data-field="content.text" rows="4">${el.content?.text || ''}</textarea>
        `;
      } else if (type === 'button') {
        html += `
          <label>Buton Metni</label>
          <input class="lb-input" type="text" data-field="content.text" value="${el.content?.text || ''}">
          <label>Link URL</label>
          <input class="lb-input" type="text" data-field="content.url" value="${el.content?.url || '#'}">
        `;
      } else if (type === 'image') {
        html += `
          <label>Görsel URL</label>
          <input class="lb-input" type="text" data-field="content.src" value="${el.content?.src || ''}">
        `;
      } else if (type === 'feature') {
        html += `
          <label>İkon (Emoji)</label>
          <input class="lb-input" type="text" data-field="content.icon" value="${el.content?.icon || '⭐'}" maxlength="2">
          <label>Başlık</label>
          <input class="lb-input" type="text" data-field="content.title" value="${el.content?.title || ''}">
          <label>Açıklama</label>
          <textarea class="lb-input" data-field="content.desc" rows="2">${el.content?.desc || ''}</textarea>
        `;
      } else if (type === 'price_card') {
        html += `
          <label>Plan Adı</label>
          <input class="lb-input" type="text" data-field="content.title" value="${el.content?.title || ''}">
          <label>Fiyat</label>
          <input class="lb-input" type="text" data-field="content.price" value="${el.content?.price || ''}">
          <label>Dönem</label>
          <input class="lb-input" type="text" data-field="content.period" value="${el.content?.period || '/ay'}">
          <label>Popüler mi?</label>
          <label class="lb-checkbox"><input type="checkbox" data-field="content.popular" ${el.content?.popular ? 'checked' : ''}> Popüler</label>
        `;
      } else if (type === 'radio_player') {
        html += `
          <label>Radyo Adı</label>
          <input class="lb-input" type="text" data-field="content.station" value="${el.content?.station || ''}">
          <label>Stream URL</label>
          <input class="lb-input" type="url" data-field="content.streamUrl" value="${el.content?.streamUrl || ''}" placeholder="https://.../stream">
          <label>Açıklama</label>
          <textarea class="lb-input" data-field="content.desc" rows="2">${el.content?.desc || ''}</textarea>
          <label>Buton Yazısı</label>
          <input class="lb-input" type="text" data-field="content.button" value="${el.content?.button || 'Dinle'}">
        `;
      } else if (['now_playing','song_request','form','map','notification','cart'].includes(type)) {
        html += `
          <label>Başlık</label>
          <input class="lb-input" type="text" data-field="content.title" value="${el.content?.title || ''}">
          <label>Açıklama</label>
          <textarea class="lb-input" data-field="content.desc" rows="2">${el.content?.desc || ''}</textarea>
          <label>Buton Yazısı</label>
          <input class="lb-input" type="text" data-field="content.button" value="${el.content?.button || ''}">
        `;
      }
      
      // Style fields
      html += '<div class="lb-inspector-section"><span>Stil</span></div>';
      
      if (!el.style) el.style = {};
      if (el.style) {
        html += `
          <label>Font Boyutu</label>
          <input class="lb-input" type="text" data-field="style.fontSize" value="${el.style.fontSize || ''}" placeholder="16px">
          <label>Renk</label>
          <input class="lb-input" type="color" data-field="style.color" value="${el.style.color || '#000000'}">
          <label>Arka Plan</label>
          <input class="lb-input" type="color" data-field="style.background" value="${el.style.background || '#ffffff'}">
          <label>Padding</label>
          <input class="lb-input" type="text" data-field="style.padding" value="${el.style.padding || ''}" placeholder="12px">
          <label>Margin</label>
          <input class="lb-input" type="text" data-field="style.margin" value="${el.style.margin || ''}" placeholder="0 auto">
          <label>Hizalama</label>
          <select class="lb-input" data-field="style.textAlign">
            <option value="" ${!el.style.textAlign ? 'selected' : ''}>Varsayılan</option>
            <option value="left" ${el.style.textAlign === 'left' ? 'selected' : ''}>Sola Yasla</option>
            <option value="center" ${el.style.textAlign === 'center' ? 'selected' : ''}>Ortala</option>
            <option value="right" ${el.style.textAlign === 'right' ? 'selected' : ''}>Sağa Yasla</option>
          </select>
          <label>Genişlik</label>
          <input class="lb-input" type="text" data-field="style.width" value="${el.style.width || ''}" placeholder="320px veya 100%">
          <label>Yükseklik</label>
          <input class="lb-input" type="text" data-field="style.height" value="${el.style.height || ''}" placeholder="120px">
          <label>Minimum Yükseklik</label>
          <input class="lb-input" type="text" data-field="style.minHeight" value="${el.style.minHeight || ''}" placeholder="160px">
        `;
      }
      
      // Section specific
      if (!isChild && el.content) {
        html += `
          <div class="lb-inspector-section"><span>Bölüm</span></div>
          <label>Arka Plan</label>
          <input class="lb-input" type="color" data-field="content.bgColor" value="${el.content.bgColor || '#ffffff'}">
          <label>Padding</label>
          <input class="lb-input" type="text" data-field="content.padding" value="${el.content.padding || '40px'}">
        `;
      }
      
      html += '</div>';
      
      // Actions
      html += `
        <div class="lb-inspector-footer">
          <button type="button" class="lb-btn lb-btn-danger" data-action="delete-element" data-id="${el.id}">Sil</button>
          <button type="button" class="lb-btn" data-action="duplicate-element" data-id="${el.id}">Kopyala</button>
        </div>
      `;
      
      return html;
    },
    
    // Update field
    updateField(id, fieldPath, value) {
      const found = this.findElement(id);
      if (!found) return;
      
      const target = found.element || found.section;
      const parts = fieldPath.split('.');
      
      let obj = target;
      for (let i = 0; i < parts.length - 1; i++) {
        if (!obj[parts[i]]) obj[parts[i]] = {};
        obj = obj[parts[i]];
      }
      obj[parts[parts.length - 1]] = value;
      
      this.render();
      
      // Re-open inspector if still selected
      if (this.state.selectedId === id) {
        this.openInspector(id);
      }
    },
    
    // Event binding
    bindEvents() {
      document.addEventListener('click', e => {
        const radioBtn = e.target.closest('[data-lb-radio-play]');
        if (radioBtn) {
          e.preventDefault();
          const box = radioBtn.closest('.lb-radio-card');
          const audio = box?.querySelector('audio');
          const status = box?.querySelector('[data-lb-radio-status]');
          const streamUrl = (radioBtn.dataset.streamUrl || '').trim();
          if (!audio || !streamUrl) {
            if (status) status.textContent = 'Stream URL girilmedi.';
            return;
          }
          if (!audio.paused) {
            audio.pause();
            radioBtn.textContent = 'Dinle';
            if (status) status.textContent = 'Yayın duraklatıldı.';
            return;
          }
          if (location.protocol === 'https:' && /^http:\/\//i.test(streamUrl)) {
            if (status) status.textContent = 'HTTPS sayfada HTTP stream engellenebilir.';
            return;
          }
          if (audio.src !== streamUrl) {
            audio.src = streamUrl;
            audio.load();
          }
          if (status) status.textContent = 'Yayın bağlanıyor...';
          audio.play().then(() => {
            radioBtn.textContent = 'Duraklat';
            if (status) status.textContent = 'Yayın çalıyor.';
          }).catch(() => {
            radioBtn.textContent = 'Dinle';
            if (status) status.textContent = 'Yayın çalınamadı. URL, codec veya CORS kontrol edin.';
          });
          return;
        }

        // Widget palette click
        const widget = e.target.closest('[data-widget-type]');
        if (widget) {
          const sectionId = this.targetSectionId();
          if (sectionId) this.addElement(widget.dataset.widgetType, sectionId);
          return;
        }
        
        // Element selection
        const element = e.target.closest('.lb-element');
        if (element && !e.target.closest('.lb-resize-handle')) {
          this.selectElement(element.dataset.id);
          return;
        }
        
        // Section actions
        const action = e.target.closest('[data-action]');
        if (action) {
          const act = action.dataset.action;
          const section = action.closest('.lb-section');
          
          if (act === 'delete') {
            this.deleteElement(section.dataset.id);
          } else if (act === 'duplicate') {
            this.duplicate(section.dataset.id);
          } else if (act === 'move-up') {
            this.moveSection(section.dataset.id, 'up');
          } else if (act === 'move-down') {
            this.moveSection(section.dataset.id, 'down');
          } else if (act === 'close-inspector') {
            this.state.selectedId = null;
            this.closeInspector();
            this.render();
          } else if (act === 'delete-element') {
            this.deleteElement(action.dataset.id);
          } else if (act === 'duplicate-element') {
            this.duplicate(action.dataset.id);
          }
          return;
        }
        
        // Canvas click - deselect
        if (e.target.id === 'lbCanvas' || e.target.closest('#lbCanvas')) {
          this.state.selectedId = null;
          this.closeInspector();
          this.render();
        }
      });

      document.addEventListener('dragstart', e => {
        const widget = e.target.closest('[data-widget-type]');
        if (!widget) return;
        this.startDrag(widget.dataset.widgetType, widget);
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'copy';
          e.dataTransfer.setData('text/plain', widget.dataset.widgetType);
        }
      });

      document.addEventListener('dragover', e => {
        if (!this.dragData) return;
        const dropTarget = e.target.closest('.lb-section, #lbCanvas');
        if (!dropTarget) return;
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
      });

      document.addEventListener('drop', e => {
        const type = this.dragData?.type || e.dataTransfer?.getData('text/plain');
        if (!type) return;
        const section = e.target.closest('.lb-section');
        const canvas = e.target.closest('#lbCanvas');
        if (!section && !canvas) return;
        e.preventDefault();
        let sectionId = section?.dataset.id || this.targetSectionId();
        if (sectionId) this.addElement(type, sectionId);
        this.dragData = null;
      });

      document.addEventListener('mousedown', e => {
        const handle = e.target.closest('.lb-resize-handle');
        if (!handle) return;
        const element = handle.closest('.lb-element');
        if (!element) return;
        const found = this.findElement(element.dataset.id);
        if (!found?.element) return;
        e.preventDefault();
        const rect = element.getBoundingClientRect();
        this.resizeData = {
          id: element.dataset.id,
          startX: e.clientX,
          startY: e.clientY,
          width: rect.width,
          height: rect.height,
          pos: handle.dataset.pos || 'se'
        };
        document.body.classList.add('lb-is-resizing');
      });

      document.addEventListener('mousemove', e => {
        if (!this.resizeData) return;
        e.preventDefault();
        const found = this.findElement(this.resizeData.id);
        if (!found?.element) return;
        const dx = e.clientX - this.resizeData.startX;
        const dy = e.clientY - this.resizeData.startY;
        const pos = this.resizeData.pos;
        const width = Math.max(80, this.resizeData.width + (pos.includes('w') ? -dx : dx));
        const height = Math.max(44, this.resizeData.height + (pos.includes('n') ? -dy : dy));
        if (!found.element.style) found.element.style = {};
        found.element.style.width = Math.round(width) + 'px';
        found.element.style.height = Math.round(height) + 'px';
        this.saveState();
        const dom = document.querySelector('.lb-element[data-id="' + this.resizeData.id + '"]');
        if (dom) {
          dom.style.width = found.element.style.width;
          dom.style.height = found.element.style.height;
        }
      });

      document.addEventListener('mouseup', () => {
        if (!this.resizeData) return;
        this.snapshot();
        this.resizeData = null;
        document.body.classList.remove('lb-is-resizing');
        this.render();
        if (this.state.selectedId) this.openInspector(this.state.selectedId);
      });
      
      // Inspector field changes
      document.addEventListener('change', e => {
        const field = e.target.closest('[data-field]');
        if (field && this.state.selectedId) {
          let value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
          this.snapshot();
          this.updateField(this.state.selectedId, field.dataset.field, value);
        }
      });
      
      // Inspector input changes
      document.addEventListener('input', e => {
        const field = e.target.closest('[data-field]');
        if (field && field.tagName === 'TEXTAREA' && this.state.selectedId) {
          clearTimeout(this._inputTimer);
          this._inputTimer = setTimeout(() => {
            this.updateField(this.state.selectedId, field.dataset.field, field.value);
          }, 300);
        }
      });
      
      // Keyboard shortcuts
      document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
          e.preventDefault();
          if (e.shiftKey) this.redo(); else this.undo();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
          if (this.state.selectedId) {
            const found = this.findElement(this.state.selectedId);
            if (found) this.clipboard = JSON.parse(JSON.stringify(found.element || found.section));
          }
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
          if (this.clipboard && this.state.selectedId) {
            this.snapshot();
            const found = this.findElement(this.state.selectedId);
            if (found && found.parent) {
              const clone = JSON.parse(JSON.stringify(this.clipboard));
              clone.id = this.uid();
              found.parent.push(clone);
              this.render();
            }
          }
        }
        if (e.key === 'Delete' || e.key === 'Backspace') {
          if (this.state.selectedId && !e.target.matches('input, textarea')) {
            this.deleteElement(this.state.selectedId);
          }
        }
        if (e.key === 'Escape') {
          this.state.selectedId = null;
          this.closeInspector();
          this.render();
        }
      });
      
      // Toolbar buttons
      document.addEventListener('click', e => {
        if (e.target.closest('#lbAddSection')) {
          this.addSection();
        }
        if (e.target.closest('#lbUndo')) {
          this.undo();
        }
        if (e.target.closest('#lbRedo')) {
          this.redo();
        }
        if (e.target.closest('#lbSave')) {
          this.saveState();
          alert('Değişiklikler kaydedildi!');
        }
        
        // Widget add buttons
        const addBtn = e.target.closest('[data-add-widget]');
        if (addBtn) {
          const type = addBtn.dataset.widgetType || addBtn.dataset.addWidget;
          const sectionId = addBtn.dataset.sectionId;
          this.addElement(type, sectionId || this.targetSectionId());
        }
        
        // Section add buttons
        const sectionBtn = e.target.closest('[data-section-id]');
        if (sectionBtn && sectionBtn.matches('button')) {
          // This is handled by the individual add buttons
        }
      });
    },
    
    selectElement(id) {
      this.state.selectedId = id;
      this.render();
      this.openInspector(id);
    },
    
    startDrag(type, element) {
      this.dragData = { type, source: element };
    }
  };
  
  // Initialize on load
  document.addEventListener('DOMContentLoaded', () => LiveBuilder.init());
  
  // Expose globally
  window.LiveBuilder = LiveBuilder;
})();
