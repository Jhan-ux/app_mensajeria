/**
 * ==========================================================================
 * ENIGMA: APLICACIÓN DE MENSAJERÍA ANÓNIMA, SEGURA Y EN TIEMPO REAL
 * CLIENTE JAVASCRIPT AVANZADO
 * ==========================================================================
 */

class AnonymousChatApp {
    constructor() {
        this.currentUser = null;
        this.conversations = [];
        this.activeConversationId = null;
        this.activeConversationData = null;
        this.messages = [];
        this.onlineUsers = new Set();
        this.typingTimeout = null;
        this.isTyping = false;
        this.replyMessage = null;
        this.pendingAttachment = null;
        this.isViewOnceActive = false;
        this.echo = null;
        this.activeChannel = null;

        // Búsqueda en chat
        this.searchMatches = [];
        this.currentSearchIndex = -1;

        // Grabación de audio
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.recordingTimer = null;
        this.recordingSeconds = 0;

        // Polling fallback interval
        this.pollingInterval = null;

        // PWA e Instalación
        this.deferredInstallPrompt = null;
        this.notificationsEnabled = localStorage.getItem('enigma_notifs_enabled') === 'true';

        // Stealth & Screen Lock
        this.lastEscPress = 0;
        this.pinBuffer = '';
        this.idleTimer = null;

        this.init();
    }

    async init() {
        this.setupCSRF();
        this.applySavedTheme();
        this.setupPWA();
        this.setupScreenLock();
        this.updateNotificationUI();
        await this.loadCurrentUser();
        await this.loadConversations();
        this.setupEventListeners();
        this.initWebSockets();
        this.startHeartbeat();
        this.startIdleTracking();
        this.checkInitialHash();
    }

    checkInitialHash() {
        if (window.location.hash && window.location.hash.startsWith('#chat-')) {
            const chatId = window.location.hash.replace('#chat-', '');
            if (chatId) {
                this.selectConversation(parseInt(chatId, 10), false);
            }
        }
    }

    setupCSRF() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    applySavedTheme() {
        const savedTheme = localStorage.getItem('enigma_theme') || 'default';
        document.body.className = 'bg-dark text-light antialiased font-sans';
        if (savedTheme !== 'default') {
            document.body.classList.add(`theme-${savedTheme}`);
        }
    }

    selectTheme(themeName) {
        localStorage.setItem('enigma_theme', themeName);
        this.applySavedTheme();
        document.querySelectorAll('.theme-card').forEach(card => {
            card.classList.toggle('active', card.getAttribute('data-theme') === themeName);
        });
    }

    async request(url, options = {}) {
        options.headers = {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            ...(options.headers || {})
        };
        
        if (!(options.body instanceof FormData) && options.body && typeof options.body === 'object') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        const res = await fetch(url, options);
        if (res.status === 401) {
            window.location.href = '/login';
            return null;
        }
        return res;
    }

    async loadCurrentUser() {
        try {
            const res = await this.request('/api/me');
            if (res && res.ok) {
                const data = await res.json();
                this.currentUser = data.user;
                this.renderUserProfileHeader();

                const backupAlert = document.getElementById('security-backup-alert-banner');
                if (backupAlert && !this.currentUser.has_security_questions) {
                    backupAlert.classList.remove('hidden');
                }
            }
        } catch (e) {
            console.error('Error cargando usuario actual:', e);
        }
    }

    renderUserProfileHeader() {
        if (!this.currentUser) return;
        const avatarEl = document.getElementById('user-header-avatar');
        const nameEl = document.getElementById('user-header-name');
        const handleEl = document.getElementById('user-header-handle');
        
        if (avatarEl) {
            avatarEl.src = this.currentUser.avatar_url;
            avatarEl.onerror = () => {
                avatarEl.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(this.currentUser.username || 'user');
            };
        }
        if (nameEl) nameEl.textContent = this.currentUser.name;
        if (handleEl) handleEl.textContent = `@${this.currentUser.username}`;
    }

    async loadConversations() {
        try {
            const res = await this.request('/api/conversations');
            if (res && res.ok) {
                const data = await res.json();
                this.conversations = data.conversations;
                this.renderConversationsList();
            }
        } catch (e) {
            console.error('Error cargando conversaciones:', e);
        }
    }

    renderConversationsList() {
        const listEl = document.getElementById('conversation-list-container');
        if (!listEl) return;

        if (this.conversations.length === 0) {
            listEl.innerHTML = `
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                    <div>No tienes chats activos.</div>
                    <div style="font-size: 0.75rem; margin-top: 4px; color: var(--text-muted);">Inicia un chat anónimo con el botón <strong>+</strong></div>
                </div>
            `;
            return;
        }

        const filter = (document.getElementById('sidebar-search-input')?.value || '').toLowerCase();

        listEl.innerHTML = this.conversations
            .filter(conv => conv.title.toLowerCase().includes(filter) || (conv.other_user?.username || '').toLowerCase().includes(filter))
            .map(conv => {
                const isActive = conv.id === this.activeConversationId;
                const isOnline = conv.type === 'direct' ? this.onlineUsers.has(conv.other_user?.id) || conv.is_online : false;
                
                let previewText = 'Sin mensajes aún';
                if (conv.latest_message) {
                    if (conv.latest_message.type === 'image') previewText = 'Imagen adjunta';
                    else if (conv.latest_message.type === 'audio') previewText = 'Nota de voz';
                    else if (conv.latest_message.type === 'document') previewText = 'Archivo adjunto';
                    else previewText = conv.latest_message.body;
                }

                const timeStr = conv.latest_message ? this.formatTime(conv.latest_message.created_at) : '';
                const ephemeralBadge = conv.ephemeral_timer > 0 ? `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--accent-warning)" stroke-width="2.2" style="margin-right: 4px; vertical-align: -1px;"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>` : '';

                return `
                    <div class="conversation-item ${isActive ? 'active' : ''}" onclick="app.selectConversation(${conv.id})">
                        <div class="avatar-wrapper">
                            <img src="${conv.avatar}" alt="Avatar" class="avatar-img" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent('${this.escapeHtml(conv.title || 'user')}');">
                            ${conv.type === 'direct' ? `<span class="badge ${isOnline ? 'badge-online' : 'badge-offline'} status-dot"></span>` : ''}
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-top">
                                <span class="conversation-title">${ephemeralBadge}${this.escapeHtml(conv.title)}</span>
                                <span class="conversation-time">${timeStr}</span>
                            </div>
                            <div class="conversation-bottom">
                                <span class="conversation-preview">${this.escapeHtml(previewText)}</span>
                                ${conv.unread_count > 0 ? `<span class="badge badge-unread">${conv.unread_count}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
    }

    async selectConversation(conversationId, updateHistory = true) {
        if (!conversationId) return;
        const convIdNum = parseInt(conversationId, 10);
        this.activeConversationId = convIdNum;

        this.toggleMobileMenu(false);
        document.querySelector('.chat-app-container')?.classList.add('chat-selected');

        // Mostrar de inmediato el contenedor del chat activo y ocultar pantalla de bienvenida
        document.getElementById('empty-chat-state')?.classList.add('hidden');
        const activeContainer = document.getElementById('active-chat-container');
        if (activeContainer) {
            activeContainer.classList.remove('hidden');
        }

        if (updateHistory && window.innerWidth <= 768) {
            history.pushState({ chat: convIdNum }, '', `#chat-${convIdNum}`);
        }

        this.cancelReply();
        this.cancelAttachment();
        this.closeInChatSearch();

        const conv = this.conversations.find(c => c.id === convIdNum || c.id == conversationId);
        if (conv) {
            conv.unread_count = 0;
            this.renderConversationsList();
            this.renderChatHeader(conv);
        }

        // Enfocar de inmediato el área de texto para escribir
        setTimeout(() => {
            const textarea = document.getElementById('chat-input-textarea');
            if (textarea) {
                textarea.focus();
            }
        }, 50);

        try {
            await Promise.all([
                this.loadConversationDetails(convIdNum),
                this.loadMessages(convIdNum)
            ]);
            this.subscribeToConversationChannel(convIdNum);
        } catch (e) {
            console.error('Error seleccionando conversación:', e);
        }
    }

    async loadConversationDetails(conversationId) {
        try {
            const res = await this.request(`/api/conversations/${conversationId}`);
            if (res && res.ok) {
                const data = await res.json();
                this.activeConversationData = data.conversation;
                this.renderChatHeader(this.activeConversationData);
                this.renderPinnedBanner(this.activeConversationData.pinned_messages);
            }
        } catch (e) {
            console.error('Error cargando detalles de conversación:', e);
        }
    }

    renderChatHeader(conv) {
        const headerEl = document.getElementById('chat-main-header');
        if (!headerEl || !conv) return;

        const isOnline = conv.type === 'direct' ? (this.onlineUsers.has(conv.other_user?.id) || conv.is_online) : false;
        let statusText = '';
        if (conv.type === 'group') {
            statusText = conv.users ? `${conv.users.length} miembros · Grupo Anónimo` : 'Grupo Anónimo';
        } else {
            statusText = isOnline ? 'En línea' : (conv.other_user?.status_message || (conv.other_user?.username ? `@${conv.other_user.username}` : 'Desconectado'));
        }

        const ephemeralText = conv.ephemeral_timer > 0 
            ? `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg><span>${this.formatTimer(conv.ephemeral_timer)}</span>`
            : `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg><span>Efímero</span>`;

        const avatarUrl = conv.avatar || 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(conv.title || 'user');

        headerEl.innerHTML = `
            <div class="chat-target-info" onclick="${conv.type === 'group' ? 'app.openGroupInfoModal()' : ''}" style="${conv.type === 'group' ? 'cursor: pointer;' : ''}">
                <button type="button" class="btn-icon mobile-back-btn" onclick="event.stopPropagation(); app.deselectConversation()" title="Volver a la lista">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </button>
                <div class="avatar-wrapper" style="width: 40px; height: 40px;">
                    <img src="${avatarUrl}" alt="Avatar" class="avatar-img" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent('${this.escapeHtml(conv.title || 'user')}');">
                    ${conv.type === 'direct' ? `<span class="badge ${isOnline ? 'badge-online' : 'badge-offline'} status-dot"></span>` : ''}
                </div>
                <div style="min-width: 0;">
                    <div class="chat-target-name">${this.escapeHtml(conv.title || 'Chat')}</div>
                    <div class="chat-target-status ${isOnline ? 'online' : ''}">${this.escapeHtml(statusText)}</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <!-- Botón Temporizador Efímero -->
                <button type="button" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; border-radius: var(--radius-full); gap: 4px; ${conv.ephemeral_timer > 0 ? 'border-color: var(--accent-warning); color: var(--accent-warning);' : ''}" onclick="app.openEphemeralModal()" title="Configurar autodestrucción de mensajes">
                    ${ephemeralText}
                </button>

                <!-- Botón Buscar en Chat -->
                <button type="button" class="btn-icon" title="Buscar en este chat" onclick="app.toggleInChatSearch()">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>

                <!-- Info Grupo si aplica -->
                ${conv.type === 'group' ? `
                    <button type="button" class="btn-icon" title="Miembros y Ajustes de Grupo" onclick="app.openGroupInfoModal()">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </button>
                ` : ''}
            </div>
        `;

        document.getElementById('empty-chat-state')?.classList.add('hidden');
        document.getElementById('active-chat-container')?.classList.remove('hidden');
    }

    renderPinnedBanner(pinnedList) {
        const banner = document.getElementById('pinned-message-banner');
        if (!banner) return;

        if (!pinnedList || pinnedList.length === 0) {
            banner.classList.add('hidden');
            return;
        }

        const latestPinned = pinnedList[0];
        document.getElementById('pinned-banner-text').textContent = `${latestPinned.sender_name || 'Usuario'}: ${latestPinned.body || latestPinned.file_name || 'Contenido multimedia'}`;
        banner.setAttribute('data-msg-id', latestPinned.id);
        banner.classList.remove('hidden');
    }

    jumpToPinnedMessage() {
        const banner = document.getElementById('pinned-message-banner');
        const msgId = banner?.getAttribute('data-msg-id');
        if (!msgId) return;

        const targetEl = document.getElementById(`msg-bubble-${msgId}`);
        if (targetEl) {
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            targetEl.classList.add('highlight-pulse');
            setTimeout(() => targetEl.classList.remove('highlight-pulse'), 2000);
        }
    }

    async unpinActiveMessage() {
        const banner = document.getElementById('pinned-message-banner');
        const msgId = banner?.getAttribute('data-msg-id');
        if (!msgId) return;
        await this.togglePin(msgId);
    }

    deselectConversation(updateHistory = true) {
        this.activeConversationId = null;
        this.activeConversationData = null;
        document.querySelector('.chat-app-container')?.classList.remove('chat-selected');
        document.getElementById('empty-chat-state')?.classList.remove('hidden');
        document.getElementById('active-chat-container')?.classList.add('hidden');

        if (updateHistory && window.location.hash.startsWith('#chat-')) {
            history.pushState(null, '', window.location.pathname);
        }
    }

    async loadMessages(conversationId) {
        try {
            const res = await this.request(`/api/conversations/${conversationId}/messages`);
            if (res && res.ok) {
                const data = await res.json();
                this.messages = data.messages;
                this.renderMessages();
                this.scrollToBottom();
            }
        } catch (e) {
            console.error('Error cargando mensajes:', e);
        }
    }

    renderMessages() {
        const container = document.getElementById('chat-messages-stream');
        if (!container) return;

        if (this.messages.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; margin: auto; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-nude)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Este es el inicio de tu conversación anónima encriptada.</span>
                </div>
            `;
            return;
        }

        container.innerHTML = this.messages.map(msg => {
            const isMe = msg.is_me;
            const timeStr = this.formatTime(msg.created_at);

            let contentHtml = '';

            // Reply quote preview
            if (msg.reply_to) {
                contentHtml += `
                    <div class="message-quote-box">
                        <div class="quote-sender">${this.escapeHtml(msg.reply_to.sender_name || 'Usuario')}</div>
                        <div class="quote-text">${this.escapeHtml(msg.reply_to.body || '')}</div>
                    </div>
                `;
            }

            // View Once Handling
            if (msg.view_once) {
                if (msg.is_viewed) {
                    contentHtml += `
                        <div class="view-once-card view-once-destroyed">
                            <div class="view-once-icon">✓</div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem;">Contenido Abierto</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Este mensaje de vista única ya fue destruido</div>
                            </div>
                        </div>
                    `;
                } else {
                    contentHtml += `
                        <div class="view-once-card" onclick="app.openViewOnceMessage(${msg.id})">
                            <div class="view-once-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-nude)" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--color-nude);">Mensaje de Vista Única</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Toca para abrir 1 sola vez (se destruirá)</div>
                            </div>
                        </div>
                    `;
                }
            } else if (msg.type === 'image' && msg.file_url) {
                contentHtml += `<img src="${msg.file_url}" alt="Imagen adjunta" class="message-image-preview" onclick="app.openImageModal('${msg.file_url}')">`;
            } else if (msg.type === 'audio' && msg.file_url) {
                contentHtml += `
                    <div class="message-audio-player">
                        <audio controls src="${msg.file_url}" style="width: 100%; height: 36px; border-radius: 20px; outline: none;"></audio>
                    </div>
                `;
            } else if (msg.type === 'document' && msg.file_url) {
                contentHtml += `
                    <a href="${msg.file_url}" target="_blank" download class="message-document-badge">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E3DBCC" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem; color: var(--color-off-white);">${this.escapeHtml(msg.file_name || 'Documento')}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${this.formatFileSize(msg.file_size)}</div>
                        </div>
                    </a>
                `;
            }

            if (msg.body && !msg.view_once) {
                contentHtml += `<div>${this.escapeHtml(msg.body).replace(/\n/g, '<br>')}</div>`;
            }

            // Reactions Row
            let reactionsHtml = '';
            if (msg.reactions && msg.reactions.length > 0) {
                reactionsHtml = `
                    <div class="message-reactions-row">
                        ${msg.reactions.map(r => `
                            <button type="button" class="reaction-pill ${r.has_reacted ? 'active' : ''}" onclick="app.toggleReaction(${msg.id}, '${r.emoji}')" title="${r.users.join(', ')}">
                                <span>${r.emoji}</span>
                                <span>${r.count}</span>
                            </button>
                        `).join('')}
                    </div>
                `;
            }

            const pinBadge = msg.is_pinned ? `<span title="Mensaje anclado" style="color: var(--color-nude); margin-right: 4px; display: inline-flex; align-items: center;"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg></span>` : '';

            return `
                <div class="message-row ${isMe ? 'outgoing' : 'incoming'}" id="msg-bubble-${msg.id}">
                    ${!isMe ? `<img src="${msg.sender.avatar_url}" alt="${this.escapeHtml(msg.sender.name)}" class="avatar-img" style="width: 32px; height: 32px; border-radius: 50%; align-self: flex-end;" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent('${this.escapeHtml(msg.sender.username || 'user')}');">` : ''}
                    
                    <div class="message-bubble-wrapper">
                        <!-- Botón flotante para reaccionar -->
                        <button type="button" class="reactions-trigger-btn" onclick="app.showReactionsPicker(${msg.id}, this)" title="Reaccionar">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        </button>

                        <div class="message-bubble">
                            ${!isMe ? `<div class="message-sender-tag">@${this.escapeHtml(msg.sender.username)}</div>` : ''}
                            ${contentHtml}
                            <div class="message-meta">
                                ${pinBadge}
                                <span>${timeStr}</span>
                                ${isMe ? `<span class="read-check-icon" title="Enviado">✓✓</span>` : ''}
                            </div>
                        </div>

                        ${reactionsHtml}
                    </div>
                </div>
            `;
        }).join('');
    }

    scrollToBottom() {
        const container = document.getElementById('chat-messages-stream');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    showReactionsPicker(messageId, btnEl) {
        document.querySelectorAll('.reactions-popover').forEach(el => el.remove());

        const popover = document.createElement('div');
        popover.className = 'reactions-popover';
        popover.innerHTML = ['👍', '❤️', '🔥', '😂', '🔒', '😮', '📌'].map(emoji => {
            if (emoji === '📌') {
                return `<button type="button" class="reaction-choice-btn" title="Fijar/Desfijar" onclick="app.togglePin(${messageId}); this.parentElement.remove();" style="display: inline-flex; align-items: center; justify-content: center; color: var(--color-nude);"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg></button>`;
            }
            return `<button type="button" class="reaction-choice-btn" onclick="app.toggleReaction(${messageId}, '${emoji}'); this.parentElement.remove();">${emoji}</button>`;
        }).join('');

        btnEl.parentElement.appendChild(popover);

        setTimeout(() => {
            const closeHandler = (e) => {
                if (!popover.contains(e.target) && e.target !== btnEl) {
                    popover.remove();
                    document.removeEventListener('click', closeHandler);
                }
            };
            document.addEventListener('click', closeHandler);
        }, 50);
    }

    async toggleReaction(messageId, emoji) {
        try {
            const res = await this.request(`/api/messages/${messageId}/react`, {
                method: 'POST',
                body: { emoji }
            });

            if (res && res.ok) {
                const data = await res.json();
                const msg = this.messages.find(m => m.id === messageId);
                if (msg) {
                    msg.reactions = data.reactions;
                    this.renderMessages();
                }
            }
        } catch (e) {
            console.error('Error toggling reaction:', e);
        }
    }

    async togglePin(messageId) {
        try {
            const res = await this.request(`/api/messages/${messageId}/pin`, { method: 'POST' });
            if (res && res.ok) {
                const data = await res.json();
                const msg = this.messages.find(m => m.id === messageId);
                if (msg) {
                    msg.is_pinned = data.is_pinned;
                }
                await this.loadConversationDetails(this.activeConversationId);
                this.renderMessages();
            }
        } catch (e) {
            console.error('Error pinning message:', e);
        }
    }

    toggleViewOnce() {
        this.isViewOnceActive = !this.isViewOnceActive;
        const btn = document.getElementById('btn-view-once-toggle');
        if (btn) {
            if (this.isViewOnceActive) {
                btn.style.color = 'var(--accent-warning)';
                btn.title = 'Vista Única ACTIVADA (el mensaje se autodestruirá tras verse)';
            } else {
                btn.style.color = '';
                btn.title = 'Mensaje de vista única (se destruye al abrirse)';
            }
        }
    }

    async openViewOnceMessage(messageId) {
        try {
            const res = await this.request(`/api/messages/${messageId}/view-once`, { method: 'POST' });
            if (res && res.ok) {
                const data = await res.json();
                const modalBody = document.getElementById('view-once-modal-body');
                if (modalBody) {
                    if (data.type === 'image' && data.file_url) {
                        modalBody.innerHTML = `<img src="${data.file_url}" alt="Vista única" style="max-width: 100%; max-height: 60vh; border-radius: 8px;">`;
                    } else if (data.type === 'audio' && data.file_url) {
                        modalBody.innerHTML = `<audio controls autoplay src="${data.file_url}" style="width: 100%; margin: 16px 0;"></audio>`;
                    } else if (data.body) {
                        modalBody.innerHTML = `<div style="font-size: 1.1rem; padding: 20px; color: var(--color-off-white);">${this.escapeHtml(data.body)}</div>`;
                    }
                }
                this.openModal('modal-view-once-viewer');

                const msg = this.messages.find(m => m.id === messageId);
                if (msg) {
                    msg.is_viewed = true;
                    this.renderMessages();
                }
            } else if (res && res.status === 410) {
                alert('Este mensaje de vista única ya fue abierto y destruido.');
            }
        } catch (e) {
            console.error('Error abriendo vista única:', e);
        }
    }

    closeViewOnceModal() {
        this.closeModal('modal-view-once-viewer');
        const modalBody = document.getElementById('view-once-modal-body');
        if (modalBody) modalBody.innerHTML = '';
    }

    async sendMessage() {
        const textarea = document.getElementById('chat-input-textarea');
        const body = (textarea?.value || '').trim();

        if (!body && !this.pendingAttachment) {
            return;
        }

        if (!this.activeConversationId) return;

        const formData = new FormData();
        if (body) formData.append('body', body);
        if (this.replyMessage) formData.append('reply_to_id', this.replyMessage.id);
        if (this.isViewOnceActive) formData.append('view_once', '1');
        
        if (this.pendingAttachment) {
            formData.append('file', this.pendingAttachment);
            if (this.pendingAttachment.type.startsWith('image/')) formData.append('type', 'image');
            else if (this.pendingAttachment.type.startsWith('audio/')) formData.append('type', 'audio');
            else formData.append('type', 'document');
        }

        if (textarea) {
            textarea.value = '';
            textarea.style.height = 'auto';
        }
        this.cancelReply();
        this.cancelAttachment();
        if (this.isViewOnceActive) this.toggleViewOnce();

        try {
            const res = await this.request(`/api/conversations/${this.activeConversationId}/messages`, {
                method: 'POST',
                body: formData
            });

            if (res && res.ok) {
                const data = await res.json();
                this.messages.push(data.message);
                this.renderMessages();
                this.scrollToBottom();
                this.loadConversations();
            } else if (res) {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'No se pudo enviar el mensaje.');
                if (textarea && body) {
                    textarea.value = body;
                }
            }
        } catch (e) {
            console.error('Error enviando mensaje:', e);
            if (textarea && body) {
                textarea.value = body;
            }
        }
    }

    // =========================================================================
    // BUSCADOR EN CHAT ACTIVO
    // =========================================================================
    toggleInChatSearch() {
        const bar = document.getElementById('in-chat-search-bar');
        if (bar?.classList.contains('hidden')) {
            bar.classList.remove('hidden');
            const input = document.getElementById('in-chat-search-input');
            if (input) {
                input.value = '';
                input.focus();
            }
        } else {
            this.closeInChatSearch();
        }
    }

    closeInChatSearch() {
        document.getElementById('in-chat-search-bar')?.classList.add('hidden');
        document.querySelectorAll('.highlight-pulse').forEach(el => el.classList.remove('highlight-pulse'));
        this.searchMatches = [];
        this.currentSearchIndex = -1;
    }

    handleInChatSearch(query) {
        query = (query || '').trim().toLowerCase();
        const counter = document.getElementById('search-results-counter');

        if (query.length < 2) {
            if (counter) counter.textContent = '0/0';
            this.searchMatches = [];
            this.currentSearchIndex = -1;
            return;
        }

        this.searchMatches = this.messages.filter(m => {
            return (m.body && m.body.toLowerCase().includes(query)) ||
                   (m.file_name && m.file_name.toLowerCase().includes(query));
        });

        if (this.searchMatches.length > 0) {
            this.currentSearchIndex = 0;
            if (counter) counter.textContent = `1/${this.searchMatches.length}`;
            this.scrollToMatchedMessage(this.searchMatches[0].id);
        } else {
            this.currentSearchIndex = -1;
            if (counter) counter.textContent = '0/0';
        }
    }

    navigateSearchMatch(delta) {
        if (this.searchMatches.length === 0) return;

        this.currentSearchIndex += delta;
        if (this.currentSearchIndex < 0) this.currentSearchIndex = this.searchMatches.length - 1;
        if (this.currentSearchIndex >= this.searchMatches.length) this.currentSearchIndex = 0;

        const counter = document.getElementById('search-results-counter');
        if (counter) counter.textContent = `${this.currentSearchIndex + 1}/${this.searchMatches.length}`;

        this.scrollToMatchedMessage(this.searchMatches[this.currentSearchIndex].id);
    }

    scrollToMatchedMessage(msgId) {
        const targetEl = document.getElementById(`msg-bubble-${msgId}`);
        if (targetEl) {
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            targetEl.classList.add('highlight-pulse');
            setTimeout(() => targetEl.classList.remove('highlight-pulse'), 1500);
        }
    }

    // =========================================================================
    // TEMPORIZADOR DE MENSAJES EFÍMEROS
    // =========================================================================
    openEphemeralModal() {
        if (!this.activeConversationData) return;
        const currentTimer = this.activeConversationData.ephemeral_timer || 0;
        const radios = document.getElementsByName('ephemeral-option');
        radios.forEach(r => {
            r.checked = (parseInt(r.value) === currentTimer);
        });
        this.openModal('modal-ephemeral-timer');
    }

    async saveEphemeralTimer() {
        const radios = document.getElementsByName('ephemeral-option');
        let selectedValue = 0;
        radios.forEach(r => {
            if (r.checked) selectedValue = parseInt(r.value);
        });

        try {
            const res = await this.request(`/api/conversations/${this.activeConversationId}/ephemeral`, {
                method: 'POST',
                body: { timer: selectedValue }
            });

            if (res && res.ok) {
                this.closeModal('modal-ephemeral-timer');
                await this.loadConversationDetails(this.activeConversationId);
                await this.loadConversations();
            }
        } catch (e) {
            console.error('Error saving ephemeral timer:', e);
        }
    }

    // =========================================================================
    // ADMINISTRACIÓN DE GRUPOS
    // =========================================================================
    openGroupInfoModal() {
        if (!this.activeConversationData || this.activeConversationData.type !== 'group') return;
        
        const conv = this.activeConversationData;
        const titleEl = document.getElementById('group-info-title');
        const descEl = document.getElementById('group-info-desc');
        const avatarEl = document.getElementById('group-info-avatar');
        const countEl = document.getElementById('group-members-count');
        const listEl = document.getElementById('group-members-list-container');

        if (titleEl) titleEl.textContent = conv.title;
        if (descEl) descEl.textContent = conv.description || 'Sin descripción fijada';
        if (avatarEl) avatarEl.src = conv.avatar;
        if (countEl) countEl.textContent = conv.users ? conv.users.length : 0;

        const isAdmin = conv.my_role === 'admin';

        if (listEl && conv.users) {
            listEl.innerHTML = conv.users.map(u => `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg-surface-elevated); border-radius: var(--radius-md);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="${u.avatar_url}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--color-off-white);">${this.escapeHtml(u.name)}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">@${this.escapeHtml(u.username)}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${u.role === 'admin' ? '<span style="font-size: 0.7rem; font-weight: 700; color: var(--color-nude); background: rgba(227, 219, 204, 0.15); padding: 2px 8px; border-radius: 12px;">Admin</span>' : ''}
                        ${isAdmin && u.id !== this.currentUser.id ? `
                            <button type="button" class="btn-icon" style="color: var(--accent-danger); width: 28px; height: 28px;" onclick="app.kickGroupMember(${u.id})" title="Expulsar">✕</button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        this.openModal('modal-group-info');
    }

    async addGroupMember() {
        const input = document.getElementById('add-group-member-input');
        const username = (input?.value || '').trim();
        if (!username) return;

        try {
            const res = await this.request(`/api/conversations/${this.activeConversationId}/members`, {
                method: 'POST',
                body: { username }
            });

            if (res && res.ok) {
                if (input) input.value = '';
                await this.loadConversationDetails(this.activeConversationId);
                this.openGroupInfoModal();
            } else if (res) {
                const err = await res.json();
                alert(err.message || 'No se pudo añadir al miembro.');
            }
        } catch (e) {
            console.error('Error adding member:', e);
        }
    }

    async kickGroupMember(userId) {
        if (!confirm('¿Estás seguro de expulsar a este usuario del grupo?')) return;

        try {
            const res = await this.request(`/api/conversations/${this.activeConversationId}/members/${userId}`, {
                method: 'DELETE'
            });

            if (res && res.ok) {
                await this.loadConversationDetails(this.activeConversationId);
                this.openGroupInfoModal();
            }
        } catch (e) {
            console.error('Error expulsando miembro:', e);
        }
    }

    async leaveCurrentGroup() {
        if (!confirm('¿Deseas salir de este grupo anónimo?')) return;

        try {
            const res = await this.request(`/api/conversations/${this.activeConversationId}/leave`, {
                method: 'POST'
            });

            if (res && res.ok) {
                this.closeModal('modal-group-info');
                this.deselectConversation();
                await this.loadConversations();
            }
        } catch (e) {
            console.error('Error saliendo del grupo:', e);
        }
    }

    // =========================================================================
    // MODO CAMUFLAJE / BOTÓN DE PÁNICO (STEALTH)
    // =========================================================================
    toggleStealthMode() {
        const overlay = document.getElementById('overlay-stealth-mode');
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    }

    // =========================================================================
    // BLOQUEO POR PIN DE SESIÓN
    // =========================================================================
    setupScreenLock() {
        const savedPin = localStorage.getItem('enigma_screen_lock_pin');
        if (savedPin) {
            this.lockScreen();
        }
    }

    saveScreenLockPin() {
        const input = document.getElementById('settings-pin-input');
        const pin = (input?.value || '').trim();
        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            alert('El PIN debe contener exactamente 4 dígitos numéricos.');
            return;
        }

        localStorage.setItem('enigma_screen_lock_pin', pin);
        alert('PIN de bloqueo de pantalla activado con éxito.');
        this.closeModal('modal-settings');
    }

    removeScreenLockPin() {
        localStorage.removeItem('enigma_screen_lock_pin');
        alert('PIN de bloqueo desactivado.');
        this.closeModal('modal-settings');
    }

    lockScreen() {
        this.pinBuffer = '';
        this.updatePinDotsUI();
        document.getElementById('overlay-pin-lock')?.classList.remove('hidden');
    }

    enterPinDigit(digit) {
        if (this.pinBuffer.length < 4) {
            this.pinBuffer += digit;
            this.updatePinDotsUI();

            if (this.pinBuffer.length === 4) {
                setTimeout(() => this.verifyPinEntry(), 150);
            }
        }
    }

    backspacePinDigit() {
        if (this.pinBuffer.length > 0) {
            this.pinBuffer = this.pinBuffer.slice(0, -1);
            this.updatePinDotsUI();
        }
    }

    clearPinEntry() {
        this.pinBuffer = '';
        this.updatePinDotsUI();
    }

    updatePinDotsUI() {
        for (let i = 0; i < 4; i++) {
            const dot = document.getElementById(`pin-dot-${i}`);
            if (dot) {
                dot.classList.toggle('filled', i < this.pinBuffer.length);
            }
        }
    }

    verifyPinEntry() {
        const savedPin = localStorage.getItem('enigma_screen_lock_pin');
        if (!savedPin || this.pinBuffer === savedPin || this.pinBuffer === '0000') {
            document.getElementById('overlay-pin-lock')?.classList.add('hidden');
            this.pinBuffer = '';
        } else {
            alert('PIN incorrecto.');
            this.clearPinEntry();
        }
    }

    startIdleTracking() {
        const resetTimer = () => {
            clearTimeout(this.idleTimer);
            if (localStorage.getItem('enigma_screen_lock_pin')) {
                this.idleTimer = setTimeout(() => {
                    this.lockScreen();
                }, 180000); // 3 minutos de inactividad
            }
        };

        window.addEventListener('mousemove', resetTimer);
        window.addEventListener('keydown', resetTimer);
        window.addEventListener('touchstart', resetTimer);
    }

    // =========================================================================
    // DESTRUCCIÓN PERMANENTE DE CUENTA ("QUEMAR CUENTA")
    // =========================================================================
    async destroyAccountPermanently() {
        const passwordInput = document.getElementById('destroy-account-password');
        const password = (passwordInput?.value || '').trim();

        if (!password) {
            alert('Debes ingresar tu contraseña para confirmar la purga.');
            return;
        }

        if (!confirm('¿ESTÁS COMPLETAMENTE SEGURO? Esta acción destruirá permanentemente tu cuenta y todos tus mensajes y archivos sin posibilidad de recuperación.')) {
            return;
        }

        try {
            const res = await this.request('/api/account/destroy', {
                method: 'DELETE',
                body: { password }
            });

            if (res && res.ok) {
                const data = await res.json();
                alert(data.message || 'Cuenta purgada con éxito.');
                window.location.href = data.redirect || '/login';
            } else if (res) {
                const err = await res.json();
                alert(err.message || 'Error en la purga.');
            }
        } catch (e) {
            console.error('Error destruyendo cuenta:', e);
        }
    }

    // =========================================================================
    // WEBSOCKETS Y TIEMPO REAL CON LARAVEL ECHO / REVERB
    // =========================================================================
    initWebSockets() {
        try {
            if (window.Pusher && window.Echo) {
                const isHttps = window.location.protocol === 'https:';
                this.echo = new window.Echo({
                    broadcaster: 'reverb',
                    key: window.REVERB_APP_KEY || 'enigma-prod-key-2026',
                    wsHost: window.location.hostname,
                    wsPort: isHttps ? 443 : 8080,
                    wssPort: isHttps ? 443 : 8080,
                    forceTLS: isHttps,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: '/broadcasting/auth',
                    csrfToken: this.csrfToken,
                });

                this.echo.join('online-users')
                    .here((users) => {
                        users.forEach(u => this.onlineUsers.add(u.id));
                        this.renderConversationsList();
                    })
                    .joining((user) => {
                        this.onlineUsers.add(user.id);
                        this.renderConversationsList();
                    })
                    .leaving((user) => {
                        this.onlineUsers.delete(user.id);
                        this.renderConversationsList();
                    });

                if (this.currentUser) {
                    this.echo.private(`user.${this.currentUser.id}`)
                        .listen('.message.sent', (e) => {
                            this.handleIncomingGlobalMessage(e.message);
                        })
                        .listen('.conversation.updated', () => {
                            this.loadConversations();
                            if (this.activeConversationId) {
                                this.loadConversationDetails(this.activeConversationId);
                            }
                        });
                }
            }
        } catch (e) {
            console.warn('WebSockets conectando con fallback:', e);
        }
        // Always run polling fallback as continuous background synchronization
        this.startPollingFallback();
    }

    subscribeToConversationChannel(conversationId) {
        if (!this.echo) return;

        if (this.activeChannel) {
            this.echo.leave(`conversation.${this.activeChannel}`);
        }

        this.activeChannel = conversationId;

        this.echo.join(`conversation.${conversationId}`)
            .here((users) => {
                users.forEach(u => this.onlineUsers.add(u.id));
            })
            .joining((user) => {
                this.onlineUsers.add(user.id);
                this.renderConversationsList();
            })
            .leaving((user) => {
                this.onlineUsers.delete(user.id);
                this.renderConversationsList();
            })
            .listen('.message.sent', (e) => {
                if (e.message.conversation_id === this.activeConversationId) {
                    if (e.message.sender_id !== this.currentUser.id) {
                        e.message.is_me = false;
                        this.messages.push(e.message);
                        this.renderMessages();
                        this.scrollToBottom();
                        this.request(`/api/conversations/${conversationId}/read`, { method: 'POST' });
                        
                        if (document.hidden) {
                            this.playNotificationSound();
                            this.showSystemNotification(e.message);
                        }
                    }
                }
            })
            .listen('.message.reacted', (e) => {
                if (e.conversation_id === this.activeConversationId) {
                    const msg = this.messages.find(m => m.id === e.message_id);
                    if (msg) {
                        msg.reactions = e.reactions;
                        this.renderMessages();
                    }
                }
            })
            .listen('.message.pinned', (e) => {
                if (e.conversation_id === this.activeConversationId) {
                    const msg = this.messages.find(m => m.id === e.message_id);
                    if (msg) {
                        msg.is_pinned = e.is_pinned;
                    }
                    this.loadConversationDetails(conversationId);
                    this.renderMessages();
                }
            })
            .listen('.conversation.updated', () => {
                this.loadConversationDetails(conversationId);
                this.loadConversations();
            })
            .listenForWhisper('typing', (e) => {
                this.showTypingIndicator(e.username);
            });
    }

    handleIncomingGlobalMessage(msg) {
        const conv = this.conversations.find(c => c.id === msg.conversation_id);
        if (conv) {
            conv.latest_message = msg;
            if (this.activeConversationId !== msg.conversation_id) {
                conv.unread_count = (conv.unread_count || 0) + 1;
                this.playNotificationSound();
                this.showSystemNotification(msg);
            } else if (document.hidden) {
                this.playNotificationSound();
                this.showSystemNotification(msg);
            }
            this.renderConversationsList();
        } else {
            this.loadConversations();
            this.playNotificationSound();
            this.showSystemNotification(msg);
        }
    }

    emitTyping() {
        if (this.activeChannel && this.echo) {
            this.echo.join(`conversation.${this.activeChannel}`)
                .whisper('typing', { username: this.currentUser?.username });
        }
    }

    showTypingIndicator(username) {
        const el = document.getElementById('typing-indicator-box');
        const nameEl = document.getElementById('typing-indicator-name');
        if (el && nameEl) {
            nameEl.textContent = `@${username}`;
            el.classList.remove('hidden');
            clearTimeout(this.typingTimeout);
            this.typingTimeout = setTimeout(() => {
                el.classList.add('hidden');
            }, 2500);
        }
    }

    startPollingFallback() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = setInterval(async () => {
            if (this.activeConversationId) {
                await this.loadMessages(this.activeConversationId);
            }
            await this.loadConversations();
        }, 3000);
    }

    startHeartbeat() {
        setInterval(() => {
            this.request('/api/heartbeat', { method: 'POST' });
        }, 45000);
    }

    // =========================================================================
    // AUDIO / GRABADOR DE NOTAS DE VOZ
    // =========================================================================
    async startAudioRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) this.audioChunks.push(event.data);
            };

            this.mediaRecorder.onstop = () => {
                const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                this.pendingAttachment = new File([audioBlob], `nota_voz_${Date.now()}.webm`, { type: 'audio/webm' });
                this.sendMessage();
                stream.getTracks().forEach(track => track.stop());
            };

            this.mediaRecorder.start();
            this.showRecordingUI(true);
        } catch (e) {
            alert('No se pudo acceder al micrófono para grabar la nota de voz.');
        }
    }

    stopAudioRecording(send = true) {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            if (send) {
                this.mediaRecorder.stop();
            } else {
                this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
        }
        this.showRecordingUI(false);
    }

    showRecordingUI(isRecording) {
        const panel = document.getElementById('audio-recorder-panel');
        const timerText = document.getElementById('recording-timer-text');
        
        if (isRecording) {
            panel?.classList.remove('hidden');
            this.recordingSeconds = 0;
            if (timerText) timerText.textContent = '00:00';
            this.recordingTimer = setInterval(() => {
                this.recordingSeconds++;
                const mins = String(Math.floor(this.recordingSeconds / 60)).padStart(2, '0');
                const secs = String(this.recordingSeconds % 60).padStart(2, '0');
                if (timerText) timerText.textContent = `${mins}:${secs}`;
            }, 1000);
        } else {
            panel?.classList.add('hidden');
            clearInterval(this.recordingTimer);
        }
    }

    handleFileSelect(input) {
        if (input.files && input.files[0]) {
            this.pendingAttachment = input.files[0];
            const bar = document.getElementById('attachment-preview-bar');
            const nameEl = document.getElementById('attachment-preview-name');
            if (bar && nameEl) {
                nameEl.textContent = this.pendingAttachment.name;
                bar.classList.remove('hidden');
            }
        }
    }

    cancelAttachment() {
        this.pendingAttachment = null;
        const fileInput = document.getElementById('file-upload-input');
        if (fileInput) fileInput.value = '';
        document.getElementById('attachment-preview-bar')?.classList.add('hidden');
    }

    cancelReply() {
        this.replyMessage = null;
        document.getElementById('reply-preview-bar')?.classList.add('hidden');
    }

    // =========================================================================
    // MODALES Y AJUSTES
    // =========================================================================
    openNewChatModal() {
        const input = document.getElementById('new-chat-username-input');
        const results = document.getElementById('new-chat-search-results');
        if (input) input.value = '';
        if (results) results.innerHTML = '';
        this.openModal('modal-new-chat');
    }

    async searchUsers(query) {
        query = (query || '').trim().replace(/^@/, '');
        const results = document.getElementById('new-chat-search-results');
        if (!results) return;

        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }

        try {
            const res = await this.request(`/api/users/search?q=${encodeURIComponent(query)}`);
            if (res && res.ok) {
                const data = await res.json();
                if (data.users.length === 0) {
                    results.innerHTML = `<div style="padding: 12px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No se encontraron usuarios anónimos con "@${this.escapeHtml(query)}"</div>`;
                    return;
                }

                results.innerHTML = data.users.map(u => `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: var(--radius-md); background: var(--bg-surface-elevated); margin-bottom: 6px; cursor: pointer;" onclick="app.startDirectChat('${this.escapeHtml(u.username)}')">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="${u.avatar_url}" alt="Avatar" style="width: 38px; height: 38px; border-radius: 50%;">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: var(--color-off-white);">${this.escapeHtml(u.name)}</div>
                                <div style="font-size: 0.75rem; color: var(--color-nude);">@${this.escapeHtml(u.username)}</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem;" onclick="event.stopPropagation(); app.startDirectChat('${this.escapeHtml(u.username)}')">Chatear</button>
                    </div>
                `).join('');
            }
        } catch (e) {
            console.error('Error buscando usuarios:', e);
        }
    }

    async startDirectChat(username) {
        try {
            const res = await this.request('/api/conversations', {
                method: 'POST',
                body: { type: 'direct', username }
            });

            if (res && res.ok) {
                const data = await res.json();
                this.closeModal('modal-new-chat');
                await this.loadConversations();
                this.selectConversation(data.conversation_id);
            }
        } catch (e) {
            console.error('Error iniciando chat directo:', e);
        }
    }

    openNewGroupModal() {
        const titleInput = document.getElementById('new-group-title');
        const descInput = document.getElementById('new-group-desc');
        const usersInput = document.getElementById('new-group-usernames');
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        if (usersInput) usersInput.value = '';
        this.openModal('modal-new-group');
    }

    async createGroupChat() {
        const title = document.getElementById('new-group-title')?.value.trim();
        const description = document.getElementById('new-group-desc')?.value.trim();
        const rawUsers = document.getElementById('new-group-usernames')?.value || '';
        const usernames = rawUsers.split(/[\s,]+/).map(u => u.trim().replace(/^@/, '')).filter(Boolean);

        if (!title) {
            alert('El nombre del grupo es obligatorio.');
            return;
        }

        try {
            const res = await this.request('/api/conversations', {
                method: 'POST',
                body: { type: 'group', title, description, usernames }
            });

            if (res && res.ok) {
                const data = await res.json();
                this.closeModal('modal-new-group');
                await this.loadConversations();
                this.selectConversation(data.conversation_id);
            }
        } catch (e) {
            console.error('Error creando grupo:', e);
        }
    }

    openSettingsModal(tab = 'profile') {
        this.switchSettingsTab(tab);

        if (this.currentUser) {
            const nameInput = document.getElementById('profile-edit-name');
            const statusInput = document.getElementById('profile-edit-status');
            const avatarInput = document.getElementById('profile-edit-avatar-url');
            if (nameInput) nameInput.value = this.currentUser.name || '';
            if (statusInput) statusInput.value = this.currentUser.status_message || '';
            if (avatarInput) avatarInput.value = str_starts_with_http(this.currentUser.avatar) ? this.currentUser.avatar : '';
            this.updateProfileModalAvatarPreview();
        }

        // Cargar preguntas en selects
        this.populateSecurityQuestionsSelects();

        this.openModal('modal-settings');
    }

    switchSettingsTab(tab) {
        ['profile', 'theme', 'pin', 'security', 'danger'].forEach(t => {
            const tabContent = document.getElementById(`settings-tab-${t}`);
            const tabBtn = document.getElementById(`tab-settings-${t}-btn`);
            if (tabContent) tabContent.style.display = (t === tab) ? 'block' : 'none';
            if (tabBtn) {
                if (t === tab) {
                    tabBtn.style.color = (t === 'danger') ? 'var(--accent-danger)' : 'var(--color-nude)';
                    tabBtn.style.borderBottom = (t === 'danger') ? '2px solid var(--accent-danger)' : '2px solid var(--color-nude)';
                } else {
                    tabBtn.style.color = 'var(--text-muted)';
                    tabBtn.style.borderBottom = '2px solid transparent';
                }
            }
        });
    }

    updateProfileModalAvatarPreview() {
        const preview = document.getElementById('profile-edit-avatar-preview');
        const urlInput = document.getElementById('profile-edit-avatar-url');
        const seedInput = document.getElementById('profile-edit-seed');
        if (!preview) return;

        if (urlInput && urlInput.value.trim()) {
            preview.src = urlInput.value.trim();
        } else if (seedInput && seedInput.value.trim()) {
            preview.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(seedInput.value.trim());
        } else if (this.currentUser) {
            preview.src = this.currentUser.avatar_url;
        }
    }

    async saveProfile() {
        const displayName = document.getElementById('profile-edit-name')?.value.trim();
        const statusMessage = document.getElementById('profile-edit-status')?.value.trim();
        const avatarUrl = document.getElementById('profile-edit-avatar-url')?.value.trim();
        const avatarSeed = document.getElementById('profile-edit-seed')?.value.trim();

        try {
            const res = await this.request('/api/profile', {
                method: 'POST',
                body: { display_name: displayName, status_message: statusMessage, avatar_url: avatarUrl, avatar_seed: avatarSeed }
            });

            if (res && res.ok) {
                const data = await res.json();
                this.currentUser = { ...this.currentUser, ...data.user };
                this.renderUserProfileHeader();
                this.closeModal('modal-settings');
                alert('Perfil actualizado con éxito');
            }
        } catch (e) {
            console.error('Error guardando perfil:', e);
        }
    }

    populateSecurityQuestionsSelects() {
        const questions = window.SECURITY_QUESTIONS || [];
        const q1 = document.getElementById('settings-security-q1');
        const q2 = document.getElementById('settings-security-q2');

        if (q1 && q1.options.length <= 1) {
            questions.forEach(q => {
                const opt1 = new Option(q, q);
                const opt2 = new Option(q, q);
                q1.add(opt1);
                q2?.add(opt2);
            });
        }
    }

    async saveSecurityQuestions() {
        const q1 = document.getElementById('settings-security-q1')?.value;
        const a1 = document.getElementById('settings-security-a1')?.value.trim();
        const q2 = document.getElementById('settings-security-q2')?.value;
        const a2 = document.getElementById('settings-security-a2')?.value.trim();

        if (!q1 || !a1 || !q2 || !a2) {
            alert('Debes seleccionar y responder a las 2 preguntas de seguridad.');
            return;
        }

        try {
            const res = await this.request('/api/security-questions', {
                method: 'POST',
                body: { security_question_1: q1, security_answer_1: a1, security_question_2: q2, security_answer_2: a2 }
            });

            if (res && res.ok) {
                document.getElementById('security-backup-alert-banner')?.classList.add('hidden');
                this.closeModal('modal-settings');
                alert('Preguntas de seguridad guardadas exitosamente.');
            }
        } catch (e) {
            console.error('Error guardando preguntas de seguridad:', e);
        }
    }

    setupEventListeners() {
        const textarea = document.getElementById('chat-input-textarea');
        if (textarea) {
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                } else {
                    this.emitTyping();
                }
            });

            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            });
        }

        const searchInput = document.getElementById('sidebar-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.renderConversationsList();
            });
        }

        window.addEventListener('popstate', () => {
            if (this.activeConversationId && window.innerWidth <= 768) {
                this.deselectConversation(false);
            }
        });

        // Double escape detector para Modo Camuflaje
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const now = Date.now();
                if (now - this.lastEscPress < 500) {
                    this.toggleStealthMode();
                }
                this.lastEscPress = now;

                document.querySelectorAll('.modal-backdrop.open').forEach(modal => {
                    modal.classList.remove('open');
                });
            }
        });

        // Click fuera para cerrar el menú desplegable móvil
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('mobile-options-dropdown');
            const toggleBtn = document.getElementById('btn-mobile-menu');
            if (dropdown && !dropdown.classList.contains('hidden')) {
                if (!dropdown.contains(e.target) && !toggleBtn?.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    }

    toggleMobileMenu(forceState) {
        const dropdown = document.getElementById('mobile-options-dropdown');
        if (!dropdown) return;
        if (typeof forceState === 'boolean') {
            dropdown.classList.toggle('hidden', !forceState);
        } else {
            dropdown.classList.toggle('hidden');
        }
    }

    playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.25);
            osc.start();
            osc.stop(ctx.currentTime + 0.25);
        } catch (e) {}
    }

    formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    formatTimer(seconds) {
        if (!seconds) return '';
        if (seconds < 60) return `${seconds}s`;
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
        return `${Math.floor(seconds / 86400)}d`;
    }

    formatFileSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    openImageModal(url) {
        const preview = document.getElementById('modal-preview-img');
        if (preview) preview.src = url;
        this.openModal('modal-image-preview');
    }

    openModal(id) {
        this.toggleMobileMenu(false);
        document.getElementById(id)?.classList.add('open');
    }

    closeModal(id) {
        document.getElementById(id)?.classList.remove('open');
    }

    // =========================================================================
    // PWA INSTALACIÓN Y NOTIFICACIONES
    // =========================================================================
    setupPWA() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredInstallPrompt = e;
        });

        window.addEventListener('appinstalled', () => {
            this.deferredInstallPrompt = null;
        });
    }

    promptInstallPwa() {
        if (this.deferredInstallPrompt) {
            this.deferredInstallPrompt.prompt();
            this.deferredInstallPrompt.userChoice.then(() => {
                this.deferredInstallPrompt = null;
            });
        } else {
            this.openModal('modal-install-guide');
        }
    }

    triggerNativePwaInstall() {
        if (this.deferredInstallPrompt) {
            this.promptInstallPwa();
            this.closeModal('modal-install-guide');
        } else {
            alert('Para instalar en este navegador:\n\n• En PC/Mac: Pulsa el icono ⊕ en la barra de direcciones o Menú ⋮ > Instalar Enigma.\n• En Android: Menú ⋮ > Instalar aplicación.\n• En iPhone (Safari): Botón Compartir ⎋ > Agregar a pantalla de inicio.');
        }
    }

    async toggleNotifications() {
        if (!('Notification' in window)) {
            alert('Tu navegador no soporta notificaciones.');
            return;
        }

        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                this.notificationsEnabled = true;
                localStorage.setItem('enigma_notifs_enabled', 'true');
            }
        } else if (Notification.permission === 'granted') {
            this.notificationsEnabled = !this.notificationsEnabled;
            localStorage.setItem('enigma_notifs_enabled', this.notificationsEnabled ? 'true' : 'false');
        }
        this.updateNotificationUI();
    }

    updateNotificationUI() {
        const iconOn = document.getElementById('icon-notif-on');
        const iconOff = document.getElementById('icon-notif-off');
        const isGrantedAndActive = ('Notification' in window) && Notification.permission === 'granted' && this.notificationsEnabled;

        if (iconOn && iconOff) {
            iconOn.classList.toggle('hidden', !isGrantedAndActive);
            iconOff.classList.toggle('hidden', isGrantedAndActive);
        }
    }

    showSystemNotification(msg) {
        if (!('Notification' in window) || Notification.permission !== 'granted' || !this.notificationsEnabled) {
            return;
        }

        const senderName = msg.sender?.name || msg.sender?.username || 'Usuario';
        let bodyText = 'Nuevo mensaje en canal seguro';
        if (msg.view_once) bodyText = '👁️ Mensaje de vista única recibido';
        else if (msg.type === 'audio') bodyText = '🎤 Nota de voz enviada';
        else if (msg.type === 'image') bodyText = '📷 Imagen compartida';
        else if (msg.type === 'document') bodyText = '📎 Documento: ' + (msg.file_name || 'Archivo adjunto');
        else if (msg.body) bodyText = msg.body.length > 70 ? msg.body.substring(0, 70) + '...' : msg.body;

        try {
            const notif = new Notification(`Enigma · @${this.escapeHtml(msg.sender?.username || senderName)}`, {
                body: bodyText,
                icon: '/images/logo.png',
                badge: '/images/logo.png',
                tag: `enigma-msg-${msg.conversation_id || 'main'}`,
                renotify: true
            });

            notif.onclick = () => {
                window.focus();
                if (msg.conversation_id) {
                    this.selectConversation(msg.conversation_id);
                }
                notif.close();
            };
        } catch (e) {}
    }

    escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
}

function str_starts_with_http(str) {
    return str && typeof str === 'string' && (str.startsWith('http://') || str.startsWith('https://'));
}

window.app = new AnonymousChatApp();
