/**
 * ==========================================================================
 * APLICACIÓN DE MENSAJERÍA ANÓNIMA EN TIEMPO REAL (CLIENTE JS)
 * ==========================================================================
 */

class AnonymousChatApp {
    constructor() {
        this.currentUser = null;
        this.conversations = [];
        this.activeConversationId = null;
        this.messages = [];
        this.onlineUsers = new Set();
        this.typingTimeout = null;
        this.isTyping = false;
        this.replyMessage = null;
        this.pendingAttachment = null;
        this.echo = null;
        this.activeChannel = null;
        
        // Grabación de audio
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.recordingTimer = null;
        this.recordingSeconds = 0;

        // Polling fallback interval
        this.pollingInterval = null;

        this.init();
    }

    async init() {
        this.setupCSRF();
        await this.loadCurrentUser();
        await this.loadConversations();
        this.setupEventListeners();
        this.initWebSockets();
        this.startHeartbeat();
    }

    setupCSRF() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
        
        if (avatarEl) avatarEl.src = this.currentUser.avatar_url;
        if (nameEl) nameEl.textContent = this.currentUser.name;
        if (handleEl) handleEl.textContent = `@${this.currentUser.username}`;

        // Profile Drawer form defaults
        const profName = document.getElementById('profile-edit-name');
        const profStatus = document.getElementById('profile-edit-status');
        const profAvatarUrl = document.getElementById('profile-edit-avatar-url');
        const profPreview = document.getElementById('profile-edit-avatar-preview');
        if (profName) profName.value = this.currentUser.display_name || this.currentUser.username;
        if (profStatus) profStatus.value = this.currentUser.status_message || '';
        if (profAvatarUrl) profAvatarUrl.value = (this.currentUser.avatar && this.currentUser.avatar.startsWith('http')) ? this.currentUser.avatar : '';
        if (profPreview) profPreview.src = this.currentUser.avatar_url;
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
                <div style="text-align: center; padding: 36px 16px; color: var(--text-muted); font-size: 0.85rem;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 12px; border-radius: 50%; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: center; color: var(--color-nude);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div style="font-weight: 600; color: var(--color-off-white);">Sin conversaciones activas</div>
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

                return `
                    <div class="conversation-item ${isActive ? 'active' : ''}" onclick="app.selectConversation(${conv.id})">
                        <div class="avatar-wrapper">
                            <img src="${conv.avatar}" alt="Avatar" class="avatar-img">
                            ${conv.type === 'direct' ? `<span class="badge ${isOnline ? 'badge-online' : 'badge-offline'} status-dot"></span>` : ''}
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-top">
                                <span class="conversation-title">${this.escapeHtml(conv.title)}</span>
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

    async selectConversation(conversationId) {
        this.activeConversationId = conversationId;
        document.querySelector('.chat-app-container')?.classList.add('chat-selected');
        
        // Limpiar reply y previews
        this.cancelReply();
        this.cancelAttachment();

        // Actualizar visual de la lista
        this.renderConversationsList();

        // Mostrar header de la conversación
        const conv = this.conversations.find(c => c.id === conversationId);
        if (conv) {
            this.renderChatHeader(conv);
            // Marcar leídos localmente
            conv.unread_count = 0;
            this.renderConversationsList();
        }

        // Cargar mensajes
        await this.loadMessages(conversationId);

        // Suscribir al canal de WebSocket
        this.subscribeToConversationChannel(conversationId);
    }

    renderChatHeader(conv) {
        const headerEl = document.getElementById('chat-main-header');
        if (!headerEl) return;

        const isOnline = conv.type === 'direct' ? this.onlineUsers.has(conv.other_user?.id) || conv.is_online : false;
        const statusText = conv.type === 'group' 
            ? 'Grupo Anónimo' 
            : (isOnline ? 'En línea' : (conv.other_user?.status_message || 'Desconectado'));

        headerEl.innerHTML = `
            <div class="chat-target-info">
                <button class="btn-icon mobile-back-btn" onclick="app.deselectConversation()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </button>
                <div class="avatar-wrapper" style="width: 44px; height: 44px;">
                    <img src="${conv.avatar}" alt="Avatar" class="avatar-img">
                    ${conv.type === 'direct' ? `<span class="badge ${isOnline ? 'badge-online' : 'badge-offline'} status-dot"></span>` : ''}
                </div>
                <div>
                    <div class="chat-target-name">${this.escapeHtml(conv.title)}</div>
                    <div class="chat-target-status ${isOnline ? 'online' : ''}">${this.escapeHtml(statusText)}</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button class="btn-icon" title="Buscar en el chat" onclick="alert('Función de búsqueda rápida activada')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </div>
        `;

        // Mostrar el chat area y ocultar placeholder
        document.getElementById('empty-chat-state')?.classList.add('hidden');
        document.getElementById('active-chat-container')?.classList.remove('hidden');
    }

    deselectConversation() {
        this.activeConversationId = null;
        document.querySelector('.chat-app-container')?.classList.remove('chat-selected');
        document.getElementById('empty-chat-state')?.classList.remove('hidden');
        document.getElementById('active-chat-container')?.classList.add('hidden');
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

            // Media rendering
            if (msg.type === 'image' && msg.file_url) {
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

            if (msg.body) {
                contentHtml += `<div>${this.escapeHtml(msg.body).replace(/\n/g, '<br>')}</div>`;
            }

            return `
                <div class="message-row ${isMe ? 'outgoing' : 'incoming'}" id="msg-bubble-${msg.id}">
                    ${!isMe ? `<img src="${msg.sender.avatar_url}" alt="${msg.sender.name}" class="avatar-img" style="width: 32px; height: 32px; border-radius: 50%; align-self: flex-end;">` : ''}
                    <div class="message-bubble">
                        ${!isMe ? `<div class="message-sender-tag">@${this.escapeHtml(msg.sender.username)}</div>` : ''}
                        ${contentHtml}
                        <div class="message-meta">
                            <span>${timeStr}</span>
                            ${isMe ? `<span class="read-check-icon" title="Enviado">✓✓</span>` : ''}
                        </div>
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
        
        if (this.pendingAttachment) {
            formData.append('file', this.pendingAttachment);
            if (this.pendingAttachment.type.startsWith('image/')) formData.append('type', 'image');
            else if (this.pendingAttachment.type.startsWith('audio/')) formData.append('type', 'audio');
            else formData.append('type', 'document');
        }

        // Limpiar inputs
        if (textarea) textarea.value = '';
        this.cancelReply();
        this.cancelAttachment();

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
                this.loadConversations(); // refrescar preview en sidebar
            }
        } catch (e) {
            console.error('Error enviando mensaje:', e);
        }
    }

    // =========================================================================
    // WEBSOCKETS Y TIEMPO REAL CON LARAVEL ECHO / REVERB
    // =========================================================================
    initWebSockets() {
        try {
            if (window.Pusher && window.Echo) {
                this.echo = new window.Echo({
                    broadcaster: 'reverb',
                    key: window.REVERB_APP_KEY || 'ralp37ndeim3xxs252fq',
                    wsHost: window.location.hostname,
                    wsPort: 8080,
                    wssPort: 8080,
                    forceTLS: false,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: '/broadcasting/auth',
                    csrfToken: this.csrfToken,
                });

                // Suscripción al canal de presencia global
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

                // Suscripción al canal personal del usuario para notificaciones
                if (this.currentUser) {
                    this.echo.private(`user.${this.currentUser.id}`)
                        .listen('.message.sent', (e) => {
                            this.handleIncomingGlobalMessage(e.message);
                        });
                }
            } else {
                this.startPollingFallback();
            }
        } catch (e) {
            console.warn('WebSockets no disponibles, activando modo polling fallback:', e);
            this.startPollingFallback();
        }
    }

    subscribeToConversationChannel(conversationId) {
        if (!this.echo) return;

        // Salir del canal anterior si existe
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
                    // Evitar duplicar si es mi propio mensaje
                    if (e.message.sender_id !== this.currentUser.id) {
                        e.message.is_me = false;
                        this.messages.push(e.message);
                        this.renderMessages();
                        this.scrollToBottom();
                        // Marcar leído
                        this.request(`/api/conversations/${conversationId}/read`, { method: 'POST' });
                    }
                }
            })
            .listen('.message.read', (e) => {
                console.log('Mensajes leídos por receptor:', e);
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
            }
            this.renderConversationsList();
        } else {
            this.loadConversations();
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
        const recorderPanel = document.getElementById('audio-recorder-panel');
        const inputField = document.getElementById('chat-input-textarea');
        const micBtn = document.getElementById('mic-record-btn');
        const timerEl = document.getElementById('recording-timer-text');

        if (isRecording) {
            recorderPanel?.classList.remove('hidden');
            if (inputField) inputField.style.display = 'none';
            if (micBtn) micBtn.classList.add('hidden');
            
            this.recordingSeconds = 0;
            if (timerEl) timerEl.textContent = '00:00';
            this.recordingTimer = setInterval(() => {
                this.recordingSeconds++;
                const mins = String(Math.floor(this.recordingSeconds / 60)).padStart(2, '0');
                const secs = String(this.recordingSeconds % 60).padStart(2, '0');
                if (timerEl) timerEl.textContent = `${mins}:${secs}`;
            }, 1000);
        } else {
            recorderPanel?.classList.add('hidden');
            if (inputField) inputField.style.display = 'block';
            if (micBtn) micBtn.classList.remove('hidden');
            clearInterval(this.recordingTimer);
        }
    }

    // =========================================================================
    // MODALES Y GESTIÓN DE ATTACHMENTS
    // =========================================================================
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
        document.getElementById('attachment-preview-bar')?.classList.add('hidden');
        const fileInput = document.getElementById('file-upload-input');
        if (fileInput) fileInput.value = '';
    }

    cancelReply() {
        this.replyMessage = null;
        document.getElementById('reply-preview-bar')?.classList.add('hidden');
    }

    openImageModal(src) {
        const modal = document.getElementById('modal-image-preview');
        const img = document.getElementById('modal-preview-img');
        if (modal && img) {
            img.src = src;
            modal.classList.add('open');
        }
    }

    closeModal(id) {
        document.getElementById(id)?.classList.remove('open');
    }

    openNewChatModal() {
        document.getElementById('modal-new-chat')?.classList.add('open');
        document.getElementById('new-chat-username-input')?.focus();
    }

    openNewGroupModal() {
        document.getElementById('modal-new-group')?.classList.add('open');
    }

    openProfileModal() {
        if (this.currentUser) {
            const profName = document.getElementById('profile-edit-name');
            const profStatus = document.getElementById('profile-edit-status');
            const profAvatarUrl = document.getElementById('profile-edit-avatar-url');
            const profPreview = document.getElementById('profile-edit-avatar-preview');
            if (profName) profName.value = this.currentUser.display_name || this.currentUser.username;
            if (profStatus) profStatus.value = this.currentUser.status_message || '';
            if (profAvatarUrl) profAvatarUrl.value = (this.currentUser.avatar && this.currentUser.avatar.startsWith('http')) ? this.currentUser.avatar : '';
            if (profPreview) profPreview.src = this.currentUser.avatar_url;
        }
        document.getElementById('modal-profile-edit')?.classList.add('open');
    }

    updateProfileModalAvatarPreview() {
        const previewEl = document.getElementById('profile-edit-avatar-preview');
        const urlInput = (document.getElementById('profile-edit-avatar-url')?.value || '').trim();
        const seedInput = (document.getElementById('profile-edit-seed')?.value || '').trim();

        if (urlInput.startsWith('http://') || urlInput.startsWith('https://')) {
            if (previewEl) {
                previewEl.src = urlInput;
                previewEl.onerror = () => {
                    if (seedInput) {
                        previewEl.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(seedInput);
                    } else if (this.currentUser) {
                        previewEl.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(this.currentUser.username);
                    }
                };
            }
        } else if (seedInput.length > 0) {
            if (previewEl) previewEl.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(seedInput);
        } else if (this.currentUser) {
            if (previewEl) previewEl.src = this.currentUser.avatar_url;
        }
    }

    async searchUsers(query) {
        const resultsEl = document.getElementById('new-chat-search-results');
        if (!resultsEl) return;

        if (query.trim().length < 2) {
            resultsEl.innerHTML = '';
            return;
        }

        try {
            const res = await this.request(`/api/users/search?q=${encodeURIComponent(query)}`);
            if (res && res.ok) {
                const data = await res.json();
                if (data.users.length === 0) {
                    resultsEl.innerHTML = '<div style="color: var(--text-muted); font-size: 0.85rem; padding: 10px;">No se encontraron usuarios con ese @username</div>';
                    return;
                }

                resultsEl.innerHTML = data.users.map(u => `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="${u.avatar_url}" style="width: 38px; height: 38px; border-radius: 50%;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">${this.escapeHtml(u.name)}</div>
                                <div style="font-size: 0.75rem; color: var(--accent-primary); font-family: var(--font-mono);">@${this.escapeHtml(u.username)}</div>
                            </div>
                        </div>
                        <button class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem;" onclick="app.startDirectChat('${u.username}')">
                            Chatear
                        </button>
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
                body: { type: 'direct', username: username }
            });

            if (res && res.ok) {
                const data = await res.json();
                this.closeModal('modal-new-chat');
                await this.loadConversations();
                this.selectConversation(data.conversation_id);
            } else {
                const err = await res.json();
                alert(err.message || 'Error al iniciar conversación');
            }
        } catch (e) {
            console.error('Error creando conversación:', e);
        }
    }

    async createGroupChat() {
        const title = (document.getElementById('new-group-title')?.value || '').trim();
        const usernamesStr = (document.getElementById('new-group-usernames')?.value || '').trim();

        if (!title) {
            alert('Por favor escribe un nombre para el grupo');
            return;
        }

        const usernames = usernamesStr.split(/[\s,]+/).filter(u => u.length > 0).map(u => u.replace(/^@/, ''));

        try {
            const res = await this.request('/api/conversations', {
                method: 'POST',
                body: {
                    type: 'group',
                    title: title,
                    usernames: usernames
                }
            });

            if (res && res.ok) {
                const data = await res.json();
                this.closeModal('modal-new-group');
                await this.loadConversations();
                this.selectConversation(data.conversation_id);
            } else {
                const err = await res.json();
                alert(err.message || 'Error al crear grupo');
            }
        } catch (e) {
            console.error('Error creando grupo:', e);
        }
    }

    async saveProfile() {
        const name = document.getElementById('profile-edit-name')?.value;
        const status = document.getElementById('profile-edit-status')?.value;
        const avatarUrl = document.getElementById('profile-edit-avatar-url')?.value;
        const seed = document.getElementById('profile-edit-seed')?.value;

        try {
            const res = await this.request('/api/profile', {
                method: 'POST',
                body: {
                    display_name: name,
                    status_message: status,
                    avatar_url: avatarUrl,
                    avatar_seed: seed
                }
            });

            if (res && res.ok) {
                const data = await res.json();
                this.currentUser = { ...this.currentUser, ...data.user };
                this.renderUserProfileHeader();
                this.closeModal('modal-profile-edit');
                await this.loadConversations();
            } else {
                const err = await res.json();
                alert(err.message || 'Error al actualizar el perfil');
            }
        } catch (e) {
            console.error('Error actualizando perfil:', e);
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
        }

        const searchInput = document.getElementById('sidebar-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.renderConversationsList();
            });
        }
    }

    playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
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

    formatFileSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
}

// Inicialización global
window.app = new AnonymousChatApp();
