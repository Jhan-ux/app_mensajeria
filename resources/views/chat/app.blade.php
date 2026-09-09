@extends('layouts.app')

@section('title', 'Canal Seguro')

@section('styles')
<style>
    .hidden { display: none !important; }
</style>
@endsection

@section('content')
<div class="chat-app-container">
    
    <!-- ====================================================================
         SIDEBAR: LISTA DE CHATS Y PERFIL
         ==================================================================== -->
    <aside class="chat-sidebar">
        
        <!-- Header del usuario autenticado -->
        <!-- Header del usuario autenticado -->
        <div class="sidebar-header">
            <div class="user-profile-badge" onclick="app.openSettingsModal('profile')" title="Ver mi perfil y ajustes">
                <div class="avatar-wrapper">
                    <img id="user-header-avatar" src="https://api.dicebear.com/7.x/bottts-neutral/svg?seed=user" alt="Avatar" class="avatar-img">
                    <span class="badge badge-online status-dot"></span>
                </div>
                <div class="user-meta-info">
                    <span id="user-header-name" class="user-meta-name">Cargando...</span>
                    <span id="user-header-handle" class="user-meta-handle">@usuario</span>
                </div>
            </div>

            <!-- Acciones de Cabecera (Desktop & Móvil) -->
            <div class="sidebar-header-actions" style="position: relative; display: flex; align-items: center; gap: 4px;">
                <!-- Botón Modo Camuflaje / Pánico -->
                <button type="button" class="btn-icon" onclick="app.toggleStealthMode()" title="Modo Pánico / Camuflaje rápido (Doble Escape)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <circle cx="12" cy="11" r="1"/>
                    </svg>
                </button>

                <!-- Botón Nuevo Chat Directo -->
                <button type="button" class="btn-icon" onclick="app.openNewChatModal()" title="Nuevo chat directo por @username">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <line x1="12" y1="8" x2="12" y2="14"/>
                        <line x1="9" y1="11" x2="15" y2="11"/>
                    </svg>
                </button>

                <!-- Botón Menú Desplegable (Ajustes, PIN, Grupos, PWA, Logout) -->
                <button type="button" id="btn-mobile-menu" class="btn-icon" onclick="app.toggleMobileMenu()" title="Más opciones">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </button>

                <!-- Dropdown Menú Flotante -->
                <div id="mobile-options-dropdown" class="mobile-dropdown-menu hidden" onclick="event.stopPropagation()">
                    <div class="mobile-dropdown-item" onclick="app.openNewGroupModal(); app.toggleMobileMenu(false);">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Crear Grupo Anónimo</span>
                    </div>
                    <div class="mobile-dropdown-item" onclick="app.lockScreen(); app.toggleMobileMenu(false);">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Bloquear Pantalla (PIN)</span>
                    </div>
                    <div class="mobile-dropdown-item" onclick="app.openSettingsModal('profile'); app.toggleMobileMenu(false);">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Ajustes & Temas</span>
                    </div>
                    <div class="mobile-dropdown-item" onclick="app.toggleNotifications(); app.toggleMobileMenu(false);">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span>Notificaciones</span>
                    </div>
                    <div class="mobile-dropdown-item" onclick="app.promptInstallPwa(); app.toggleMobileMenu(false);">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Descargar / Instalar App</span>
                    </div>
                    <div class="mobile-dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="mobile-dropdown-item" style="width: 100%; border: none; background: transparent; color: var(--accent-danger); cursor: pointer;">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Buscador de Chats en Sidebar -->
        <div class="sidebar-search-box">
            <div class="search-input-wrapper">
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="sidebar-search-input" class="form-control search-input" placeholder="Filtrar chats o contactos...">
            </div>
        </div>

        <!-- Banner de aviso: Sin respaldo de cuenta -->
        <div id="security-backup-alert-banner" class="hidden" style="margin: 6px 10px 2px; padding: 10px 12px; background: rgba(229, 192, 123, 0.08); border: 1px solid rgba(229, 192, 123, 0.3); border-radius: 12px; font-size: 0.8rem;">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-warning)" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #FDFCF8; margin-bottom: 2px;">Respaldo de cuenta pendiente</div>
                    <div style="color: #E3DBCC; opacity: 0.85; line-height: 1.35; font-size: 0.76rem;">Configura tus 2 preguntas de seguridad para poder recuperar tu acceso si olvidas tu contraseña.</div>
                    <button type="button" class="btn btn-primary" style="margin-top: 8px; padding: 4px 10px; font-size: 0.75rem;" onclick="app.openSettingsModal('security')">
                        Configurar Preguntas
                    </button>
                </div>
                <button type="button" class="btn-icon" style="width: 20px; height: 20px; font-size: 0.75rem; color: var(--text-muted);" onclick="document.getElementById('security-backup-alert-banner').classList.add('hidden')" title="Ocultar aviso">✕</button>
            </div>
        </div>

        <!-- Lista de Conversaciones -->
        <div id="conversation-list-container" class="conversation-list">
            <!-- Renderizado dinámico vía chat-app.js -->
        </div>

        <!-- Botón Flotante para Móvil (FAB: Nuevo Chat / Grupo) -->
        <button type="button" id="mobile-fab-btn" class="mobile-fab" onclick="app.openNewChatModal()" title="Nuevo Chat">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </button>

    </aside>

    <!-- ====================================================================
         ÁREA PRINCIPAL DE CHAT
         ==================================================================== -->
    <main class="chat-main-area">
        
        <!-- Estado Vacío (Ningún chat seleccionado) -->
        <div id="empty-chat-state" class="empty-chat-state">
            <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">
                <img src="{{ asset('images/logo.png') }}" alt="Enigma Logo" style="height: 100px; width: auto; max-width: 220px; object-fit: contain; filter: drop-shadow(0 0 24px rgba(227, 219, 204, 0.25));">
            </div>
            <h2 class="brand-title" style="font-size: 1.85rem; color: var(--color-off-white); margin-bottom: 4px; letter-spacing: 0.06em; text-transform: uppercase;">Enigma</h2>
            <p style="font-size: 0.8rem; color: var(--color-nude); margin-bottom: 12px; opacity: 0.9; letter-spacing: 0.05em; font-weight: 600;">SECURE ANONYMOUS MESSAGING</p>
            <p style="max-width: 380px; font-size: 0.9rem; line-height: 1.6; color: var(--text-muted);">
                Selecciona una conversación del panel lateral o pulsa el botón <strong>+</strong> para iniciar un chat privado buscando por <strong>@username</strong>.
            </p>
        </div>

        <!-- Contenedor del Chat Activo -->
        <div id="active-chat-container" class="hidden" style="display: flex; flex-direction: column; height: 100%;">
            
            <!-- Cabecera del chat activo -->
            <header id="chat-main-header" class="chat-main-header">
                <!-- Renderizado dinámico vía chat-app.js -->
            </header>

            <!-- Barra de Búsqueda dentro del Chat -->
            <div id="in-chat-search-bar" class="in-chat-search-bar hidden">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-nude)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="in-chat-search-input" placeholder="Buscar palabras clave en este chat..." oninput="app.handleInChatSearch(this.value)">
                <span id="search-results-counter" class="search-results-counter">0/0</span>
                <button type="button" class="btn-icon" style="width: 28px; height: 28px;" onclick="app.navigateSearchMatch(-1)" title="Anterior">▲</button>
                <button type="button" class="btn-icon" style="width: 28px; height: 28px;" onclick="app.navigateSearchMatch(1)" title="Siguiente">▼</button>
                <button type="button" class="btn-icon" style="width: 28px; height: 28px;" onclick="app.closeInChatSearch()" title="Cerrar búsqueda">✕</button>
            </div>

            <!-- Banner de Mensaje Fijado (Pinned Message) -->
            <div id="pinned-message-banner" class="pinned-banner hidden" onclick="app.jumpToPinnedMessage()">
                <div class="pinned-banner-content">
                    <div class="pinned-banner-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                    </div>
                    <div style="min-width: 0;">
                        <div class="pinned-banner-title">Mensaje Fijado</div>
                        <div id="pinned-banner-text" class="pinned-banner-text">Cargando mensaje...</div>
                    </div>
                </div>
                <button type="button" class="btn-icon" style="width: 24px; height: 24px;" onclick="event.stopPropagation(); app.unpinActiveMessage()" title="Desfijar mensaje">✕</button>
            </div>

            <!-- Historial de Mensajes -->
            <div id="chat-messages-stream" class="chat-messages-container">
                <!-- Burbujas de mensajes vía chat-app.js -->
            </div>

            <!-- Indicador de "Escribiendo..." -->
            <div id="typing-indicator-box" class="hidden" style="padding: 0 24px 8px;">
                <div class="typing-indicator-bubble">
                    <span id="typing-indicator-name" style="font-weight: 600; color: var(--color-nude);"></span> está escribiendo
                    <div style="display: inline-flex; gap: 3px; margin-left: 4px;">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            </div>

            <!-- Barra de Entrada de Mensaje -->
            <div class="chat-input-wrapper">
                
                <!-- Botón de Adjuntar Archivo -->
                <input type="file" id="file-upload-input" class="hidden" onchange="app.handleFileSelect(this)" accept="image/*,audio/*,application/pdf,.doc,.docx,.txt,.zip">
                <button type="button" class="btn-icon" title="Adjuntar imagen o archivo" onclick="document.getElementById('file-upload-input').click()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                </button>

                <!-- Botón Toggle Vista Única (View Once) -->
                <button type="button" id="btn-view-once-toggle" class="btn-icon" title="Mensaje de vista única (se destruye al abrirse)" onclick="app.toggleViewOnce()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </button>

                <!-- Input Container con Previews de Reply y Archivo -->
                <div class="chat-input-field-container">
                    
                    <!-- Barra de preview de Respuesta -->
                    <div id="reply-preview-bar" class="reply-preview-bar hidden">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#E3DBCC" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                            <span style="color: var(--color-nude); font-size: 0.8rem; font-weight: 600;">Respondiendo a:</span>
                            <span id="reply-preview-text" style="color: var(--text-secondary); font-style: italic;"></span>
                        </div>
                        <button type="button" class="btn-icon" style="width: 24px; height: 24px;" onclick="app.cancelReply()">✕</button>
                    </div>

                    <!-- Barra de preview de Archivo adjunto -->
                    <div id="attachment-preview-bar" class="attachment-preview-bar hidden">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E3DBCC" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span id="attachment-preview-name" style="font-size: 0.8rem; font-weight: 600; color: var(--color-nude);"></span>
                        <button type="button" class="btn-icon" style="width: 24px; height: 24px; margin-left: auto;" onclick="app.cancelAttachment()">✕</button>
                    </div>

                    <!-- Textarea de mensaje -->
                    <textarea id="chat-input-textarea" class="chat-textarea" placeholder="Escribe un mensaje anónimo... (Enter para enviar, Shift+Enter nueva línea)" rows="1"></textarea>

                    <!-- Panel Grabador de Voz -->
                    <div id="audio-recorder-panel" class="audio-recorder-panel hidden">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="recording-pulse"></span>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--color-nude);">Grabando nota de voz...</span>
                            <span id="recording-timer-text" style="font-family: var(--font-mono); font-size: 0.85rem; font-weight: 700; color: var(--color-off-white);">00:00</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;" onclick="app.stopAudioRecording(false)">Cancelar</button>
                            <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick="app.stopAudioRecording(true)">Enviar Nota</button>
                        </div>
                    </div>

                </div>

                <!-- Botón Micrófono (Grabar Nota de Voz) -->
                <button type="button" id="mic-record-btn" class="btn-icon" title="Grabar nota de voz" onclick="app.startAudioRecording()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                </button>

                <!-- Botón Enviar Mensaje -->
                <button type="button" class="btn btn-primary" style="height: 44px; padding: 0 18px;" onclick="app.sendMessage()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>

            </div>

        </div>

    </main>
</div>

<!-- ========================================================================
     MODALES Y OVERLAYS
     ======================================================================== -->

<!-- Modal: Iniciar Chat Directo por @username -->
<div id="modal-new-chat" class="modal-backdrop">
    <div class="modal-content">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <h3 class="modal-title">Iniciar Chat Directo</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-new-chat')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Buscar por @username</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <span style="position: absolute; left: 14px; color: var(--accent-primary); font-family: var(--font-mono); font-weight: 700;">@</span>
                    <input type="text" id="new-chat-username-input" class="form-control" style="padding-left: 34px;" placeholder="ej: neo, trinity, sombra..." oninput="app.searchUsers(this.value)">
                </div>
            </div>
            
            <div id="new-chat-search-results" style="max-height: 240px; overflow-y: auto; margin-top: 12px;">
                <!-- Resultados de búsqueda -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-new-chat')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal: Crear Nuevo Grupo -->
<div id="modal-new-group" class="modal-backdrop">
    <div class="modal-content">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <h3 class="modal-title">Crear Grupo Anónimo</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-new-group')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nombre del Grupo</label>
                <input type="text" id="new-group-title" class="form-control" placeholder="ej: Operación Secreta">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción del Grupo (Opcional)</label>
                <input type="text" id="new-group-desc" class="form-control" placeholder="Propósito del canal...">
            </div>
            <div class="form-group">
                <label class="form-label">Miembros (@usernames separados por comas)</label>
                <textarea id="new-group-usernames" class="form-control" rows="3" placeholder="ej: @neo, @trinity, @sombra"></textarea>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Puedes añadir varios usuarios ingresando sus nombres de usuario.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-new-group')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="app.createGroupChat()">Crear Grupo</button>
        </div>
    </div>
</div>

<!-- Modal: Información y Administración del Grupo -->
<div id="modal-group-info" class="modal-backdrop">
    <div class="modal-content" style="max-width: 480px;">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <h3 class="modal-title">Información del Grupo</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-group-info')">✕</button>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            
            <div style="text-align: center; margin-bottom: 16px;">
                <img id="group-info-avatar" src="" alt="Grupo" style="width: 72px; height: 72px; border-radius: 50%; border: 2px solid var(--color-nude); margin-bottom: 8px;">
                <h4 id="group-info-title" style="color: var(--color-off-white); font-size: 1.15rem;"></h4>
                <p id="group-info-desc" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;"></p>
            </div>

            <div style="margin-bottom: 18px; padding: 10px 14px; background: var(--bg-surface-elevated); border-radius: var(--radius-md);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--color-nude);">Añadir Miembro</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="add-group-member-input" class="form-control" style="font-size: 0.85rem;" placeholder="@username">
                    <button type="button" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem;" onclick="app.addGroupMember()">Añadir</button>
                </div>
            </div>

            <div>
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--color-nude); margin-bottom: 8px;">Participantes (<span id="group-members-count">0</span>)</div>
                <div id="group-members-list-container" style="display: flex; flex-direction: column; gap: 6px;">
                    <!-- Renderizado dinámico de miembros -->
                </div>
            </div>

            <div style="margin-top: 24px; padding-top: 14px; border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" style="color: var(--accent-danger); border-color: rgba(224, 108, 117, 0.3);" onclick="app.leaveCurrentGroup()">
                    Salir del Grupo
                </button>
                <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-group-info')">Cerrar</button>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Temporizador de Mensajes Efímeros (Autodestrucción) -->
<div id="modal-ephemeral-timer" class="modal-backdrop">
    <div class="modal-content" style="max-width: 400px;">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <h3 class="modal-title">Mensajes Efímeros</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-ephemeral-timer')">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5;">
                Configura un temporizador para que los nuevos mensajes de este chat se borren automáticamente de todos los dispositivos y del servidor.
            </p>
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); cursor: pointer;">
                    <input type="radio" name="ephemeral-option" value="0">
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem;">Desactivado</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Los mensajes no se autodestruyen</div>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); cursor: pointer;">
                    <input type="radio" name="ephemeral-option" value="60">
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem;">1 Minuto 🔥</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Máxima confidencialidad</div>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); cursor: pointer;">
                    <input type="radio" name="ephemeral-option" value="3600">
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem;">1 Hora</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Ideal para conversaciones temporales</div>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); cursor: pointer;">
                    <input type="radio" name="ephemeral-option" value="86400">
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem;">24 Horas</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Limpieza diaria automática</div>
                    </div>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-ephemeral-timer')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="app.saveEphemeralTimer()">Aplicar Temporizador</button>
        </div>
    </div>
</div>

<!-- Modal: Ajustes Completos (Perfil, Respaldo, Temas, Bloqueo PIN, Quemar Cuenta) -->
<div id="modal-settings" class="modal-backdrop">
    <div class="modal-content" style="max-width: 520px;">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <h3 class="modal-title">Ajustes de Enigma</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-settings')">✕</button>
        </div>
        
        <!-- Tab selector de ajustes -->
        <div style="display: flex; border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface-elevated); overflow-x: auto;">
            <button id="tab-settings-profile-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--color-nude); border-bottom: 2px solid var(--color-nude); font-size: 0.8rem; white-space: nowrap; padding: 10px;" onclick="app.switchSettingsTab('profile')">
                Perfil
            </button>
            <button id="tab-settings-theme-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--text-muted); border-bottom: 2px solid transparent; font-size: 0.8rem; white-space: nowrap; padding: 10px;" onclick="app.switchSettingsTab('theme')">
                Temas
            </button>
            <button id="tab-settings-pin-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--text-muted); border-bottom: 2px solid transparent; font-size: 0.8rem; white-space: nowrap; padding: 10px;" onclick="app.switchSettingsTab('pin')">
                PIN Bloqueo
            </button>
            <button id="tab-settings-security-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--text-muted); border-bottom: 2px solid transparent; font-size: 0.8rem; white-space: nowrap; padding: 10px;" onclick="app.switchSettingsTab('security')">
                Respaldo
            </button>
            <button id="tab-settings-danger-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--accent-danger); border-bottom: 2px solid transparent; font-size: 0.8rem; white-space: nowrap; padding: 10px;" onclick="app.switchSettingsTab('danger')">
                Zona Peligro
            </button>
        </div>

        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            
            <!-- TAB 1: PERFIL ANÓNIMO -->
            <div id="settings-tab-profile">
                <div style="text-align: center; margin-bottom: 18px;">
                    <img id="profile-edit-avatar-preview" src="https://api.dicebear.com/7.x/bottts-neutral/svg?seed=user" alt="Avatar" style="width: 72px; height: 72px; border-radius: 50%; background: #1c1c1c; border: 2px solid var(--color-nude); box-shadow: 0 0 16px rgba(227, 219, 204, 0.2); object-fit: cover;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Previsualización de tu foto o avatar</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Apodo / Nombre Visible</label>
                    <input type="text" id="profile-edit-name" class="form-control" placeholder="Tu alias">
                </div>
                <div class="form-group">
                    <label class="form-label">Mensaje de Estado</label>
                    <input type="text" id="profile-edit-status" class="form-control" placeholder="ej: En misión confidencial">
                </div>
                <div class="form-group">
                    <label class="form-label">Foto de Perfil (Link directo a Imagen)</label>
                    <input type="url" id="profile-edit-avatar-url" class="form-control" placeholder="https://ejemplo.com/mi-foto.jpg" oninput="app.updateProfileModalAvatarPreview()">
                </div>
                <div class="form-group">
                    <label class="form-label">O generar Robot con Semilla</label>
                    <input type="text" id="profile-edit-seed" class="form-control" placeholder="Escribe cualquier palabra para cambiar tu robot" oninput="app.updateProfileModalAvatarPreview()">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-settings')">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="app.saveProfile()">Guardar Cambios</button>
                </div>
            </div>

            <!-- TAB 2: TEMAS VISUALES -->
            <div id="settings-tab-theme" style="display: none;">
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px;">
                    Personaliza la estética visual de tu canal seguro:
                </p>
                
                <div class="theme-grid">
                    <div class="theme-card active" data-theme="default" onclick="app.selectTheme('default')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #101010;"></span>
                            <span class="theme-swatch" style="background: #E3DBCC;"></span>
                            <span class="theme-swatch" style="background: #1e1e1e;"></span>
                        </div>
                        <div class="theme-name">Obsidian Gold</div>
                        <div class="theme-desc">Editorial elegante (Por defecto)</div>
                    </div>

                    <div class="theme-card" data-theme="lilac" onclick="app.selectTheme('lilac')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #ffffff;"></span>
                            <span class="theme-swatch" style="background: #d8b4e2;"></span>
                            <span class="theme-swatch" style="background: #8b5cf6;"></span>
                        </div>
                        <div class="theme-name">Lilac Dream</div>
                        <div class="theme-desc">Lila, blanco y amatista suave</div>
                    </div>

                    <div class="theme-card" data-theme="cinnamon" onclick="app.selectTheme('cinnamon')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #ffffff;"></span>
                            <span class="theme-swatch" style="background: #f2e8dc;"></span>
                            <span class="theme-swatch" style="background: #b88358;"></span>
                        </div>
                        <div class="theme-name">Canela & Crema</div>
                        <div class="theme-desc">Blanco, crema y marrón topo</div>
                    </div>

                    <div class="theme-card" data-theme="sage" onclick="app.selectTheme('sage')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #ffffff;"></span>
                            <span class="theme-swatch" style="background: #e5efe8;"></span>
                            <span class="theme-swatch" style="background: #40916c;"></span>
                        </div>
                        <div class="theme-name">Sage Herbal</div>
                        <div class="theme-desc">Verde salvia, crema y blanco</div>
                    </div>

                    <div class="theme-card" data-theme="frost" onclick="app.selectTheme('frost')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #ffffff;"></span>
                            <span class="theme-swatch" style="background: #e2e8f0;"></span>
                            <span class="theme-swatch" style="background: #0284c7;"></span>
                        </div>
                        <div class="theme-name">Nordic Frost</div>
                        <div class="theme-desc">Blanco puro, glaciar y zafiro</div>
                    </div>

                    <div class="theme-card" data-theme="matrix" onclick="app.selectTheme('matrix')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #060e08;"></span>
                            <span class="theme-swatch" style="background: #00ff66;"></span>
                            <span class="theme-swatch" style="background: #112215;"></span>
                        </div>
                        <div class="theme-name">Matrix Terminal</div>
                        <div class="theme-desc">Cyberpunk verde fósforo</div>
                    </div>

                    <div class="theme-card" data-theme="amoled" onclick="app.selectTheme('amoled')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #000000;"></span>
                            <span class="theme-swatch" style="background: #ffffff;"></span>
                            <span class="theme-swatch" style="background: #141414;"></span>
                        </div>
                        <div class="theme-name">Pure AMOLED</div>
                        <div class="theme-desc">Negro absoluto para sigilo</div>
                    </div>

                    <div class="theme-card" data-theme="navy" onclick="app.selectTheme('navy')">
                        <div class="theme-swatches">
                            <span class="theme-swatch" style="background: #030712;"></span>
                            <span class="theme-swatch" style="background: #38bdf8;"></span>
                            <span class="theme-swatch" style="background: #132247;"></span>
                        </div>
                        <div class="theme-name">Cobalt Navy</div>
                        <div class="theme-desc">Azul marino táctico</div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: BLOQUEO POR PIN -->
            <div id="settings-tab-pin" style="display: none;">
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5;">
                    Establece un código PIN de 4 dígitos para proteger tu pantalla cuando te alejes del dispositivo.
                </p>

                <div class="form-group">
                    <label class="form-label">PIN de Seguridad (4 dígitos numéricos)</label>
                    <input type="password" id="settings-pin-input" class="form-control" maxlength="4" placeholder="••••" style="letter-spacing: 0.3em; font-size: 1.2rem; text-align: center;">
                </div>

                <div style="display: flex; gap: 8px; margin-top: 18px;">
                    <button type="button" class="btn btn-primary" style="flex: 1;" onclick="app.saveScreenLockPin()">Guardar PIN</button>
                    <button type="button" class="btn btn-secondary" onclick="app.removeScreenLockPin()">Desactivar PIN</button>
                </div>
            </div>

            <!-- TAB 4: PREGUNTAS DE RESPALDO -->
            <div id="settings-tab-security" style="display: none;">
                <div id="settings-security-status-box" style="padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; font-size: 0.825rem; line-height: 1.45;"></div>
                <div id="settings-security-alert" class="hidden" style="border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.85rem;"></div>

                <div class="form-group">
                    <label class="form-label">Pregunta Secreta 1</label>
                    <select id="settings-security-q1" class="form-control" required>
                        <option value="" disabled selected>Selecciona tu primera pregunta...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Respuesta Secreta 1</label>
                    <input type="text" id="settings-security-a1" class="form-control" placeholder="Escribe tu respuesta secreta 1" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">Pregunta Secreta 2</label>
                    <select id="settings-security-q2" class="form-control" required>
                        <option value="" disabled selected>Selecciona tu segunda pregunta...</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Respuesta Secreta 2</label>
                    <input type="text" id="settings-security-a2" class="form-control" placeholder="Escribe tu respuesta secreta 2" autocomplete="off">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-settings')">Cancelar</button>
                    <button type="button" id="btn-save-security" class="btn btn-primary" onclick="app.saveSecurityQuestions()">Guardar Respaldo</button>
                </div>
            </div>

            <!-- TAB 5: ZONA DE PELIGRO (QUEMAR CUENTA) -->
            <div id="settings-tab-danger" style="display: none;">
                <div style="padding: 16px; background: rgba(224, 108, 117, 0.08); border: 1px solid rgba(224, 108, 117, 0.3); border-radius: var(--radius-md); margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        <h4 style="color: var(--accent-danger); font-size: 1.05rem; margin: 0; font-weight: 700;">Destrucción Total de Cuenta ("Quemar Cuenta")</h4>
                    </div>
                    <p style="font-size: 0.8rem; color: #f0a0a8; line-height: 1.5; margin-bottom: 12px;">
                        Esta acción es <strong>irreversible</strong>. Se eliminará tu usuario, todos los mensajes enviados, notas de voz, fotos adjuntas y se limpiará cualquier rastro en la base de datos y disco del servidor.
                    </p>
                    <div class="form-group">
                        <label class="form-label" style="color: var(--accent-danger);">Ingresa tu contraseña para confirmar la purga:</label>
                        <input type="password" id="destroy-account-password" class="form-control" placeholder="Contraseña actual">
                    </div>
                    <button type="button" class="btn btn-primary" style="background: var(--accent-danger); border-color: var(--accent-danger); width: 100%; justify-content: center; margin-top: 10px;" onclick="app.destroyAccountPermanently()">
                        Destruir Cuenta y Datos Permanentemente
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Previsualización de Imagen -->
<div id="modal-image-preview" class="modal-backdrop" onclick="app.closeModal('modal-image-preview')">
    <div style="position: relative; max-width: 90vw; max-height: 90vh;">
        <img id="modal-preview-img" src="" alt="Zoom" style="max-width: 100%; max-height: 85vh; border-radius: 12px; box-shadow: 0 16px 40px rgba(0,0,0,0.8);">
    </div>
</div>

<!-- Modal: Vista Única Abierta -->
<div id="modal-view-once-viewer" class="modal-backdrop" onclick="app.closeViewOnceModal()">
    <div class="modal-content" style="max-width: 500px; text-align: center;" onclick="event.stopPropagation()">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-nude)" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <h3 class="modal-title" style="margin: 0;">Mensaje de Vista Única</h3>
            </div>
            <button class="btn-icon" onclick="app.closeViewOnceModal()">✕</button>
        </div>
        <div class="modal-body">
            <div id="view-once-modal-body">
                <!-- Contenido multimedia efímero -->
            </div>
            <div style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.75rem; color: var(--accent-danger); margin-top: 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Al cerrar esta ventana, el contenido será destruido permanentemente.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" style="width: 100%;" onclick="app.closeViewOnceModal()">Entendido y Destruir</button>
        </div>
    </div>
</div>

<!-- Modal: Descargar / Instalar Aplicación Enigma -->
<div id="modal-install-guide" class="modal-backdrop">
    <div class="modal-content" style="max-width: 440px;">
        <div class="bottom-sheet-grabber"></div>
        <div class="modal-header">
            <h3 class="modal-title">Descargar Enigma</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-install-guide')">✕</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <div style="margin: 0 auto 12px; display: flex; align-items: center; justify-content: center;">
                <img src="{{ asset('images/logo.png') }}" alt="Enigma Logo" style="height: 68px; width: auto; filter: drop-shadow(0 0 18px rgba(227, 219, 204, 0.25));">
            </div>
            <h4 style="color: var(--color-off-white); font-size: 1.15rem; margin-bottom: 6px; letter-spacing: 0.04em;">Instalar en tu Dispositivo</h4>
            <p style="font-size: 0.85rem; color: var(--color-nude); margin-bottom: 18px; opacity: 0.9; line-height: 1.5;">
                Instala Enigma como una aplicación de escritorio o móvil nativa para recibir notificaciones instantáneas y abrir tus chats con un solo clic.
            </p>

            <div id="pwa-native-install-box" style="margin-bottom: 16px;">
                <button type="button" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 0.95rem; justify-content: center; gap: 8px;" onclick="app.triggerNativePwaInstall()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Instalar Aplicación Ahora</span>
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-install-guide')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================================================
     OVERLAY: MODO CAMUFLAJE / PÁNICO (STEALTH)
     ======================================================================== -->
<div id="overlay-stealth-mode" class="stealth-overlay hidden">
    <div class="stealth-topbar">
        <span>sys_kernel_diagnostic.sh — Visual Studio Code (Workspace)</span>
        <button type="button" style="background: none; border: none; color: #858585; cursor: pointer; font-size: 0.8rem;" onclick="app.toggleStealthMode()" title="Restaurar (Doble Escape)">✕ Restablecer</button>
    </div>
    <div class="stealth-content">
        <pre style="margin: 0; color: #6a9955;">// Diagnostic Memory Buffer Allocation Dump</pre>
        <pre style="margin: 0; color: #4ec9b0;">#include &lt;stdio.h&gt;</pre>
        <pre style="margin: 0; color: #4ec9b0;">#include &lt;stdlib.h&gt;</pre>
        <br>
        <pre style="margin: 0; color: #ce9178;">int init_socket_stream(void* buffer, size_t len) {</pre>
        <pre style="margin: 0; color: #dcdcaa;">    printf("Allocating virtual thread stack buffer: %zu bytes\n", len);</pre>
        <pre style="margin: 0; color: #c586c0;">    return 0;</pre>
        <pre style="margin: 0; color: #ce9178;">}</pre>
        <br>
        <pre style="margin: 0; color: #6a9955;">// [Kernel]: PID 4092 thread running normally. (Presiona Esc dos veces o el botón superior para regresar)</pre>
    </div>
</div>

<!-- ========================================================================
     OVERLAY: BLOQUEO DE PANTALLA POR PIN
     ======================================================================== -->
<div id="overlay-pin-lock" class="screen-lock-modal hidden">
    <div class="pin-container">
        <div style="margin-bottom: 12px; display: flex; align-items: center; justify-content: center;">
            <img src="{{ asset('images/logo.png') }}" alt="Enigma Logo" style="height: 54px; width: auto; filter: drop-shadow(0 0 16px rgba(227, 219, 204, 0.25));">
        </div>
        <h3 style="color: var(--color-off-white); font-size: 1.25rem; font-weight: 600; margin-bottom: 4px;">Canal Bloqueado</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted);">Ingresa tu PIN de 4 dígitos para desbloquear</p>

        <div class="pin-dots">
            <span class="pin-dot" id="pin-dot-0"></span>
            <span class="pin-dot" id="pin-dot-1"></span>
            <span class="pin-dot" id="pin-dot-2"></span>
            <span class="pin-dot" id="pin-dot-3"></span>
        </div>

        <div class="pin-keypad">
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('1')">1</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('2')">2</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('3')">3</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('4')">4</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('5')">5</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('6')">6</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('7')">7</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('8')">8</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('9')">9</button>
            <button type="button" class="pin-btn" style="font-size: 1rem;" onclick="app.clearPinEntry()">C</button>
            <button type="button" class="pin-btn" onclick="app.enterPinDigit('0')">0</button>
            <button type="button" class="pin-btn" style="font-size: 1.1rem;" onclick="app.backspacePinDigit()">⌫</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.REVERB_APP_KEY = "{{ config('broadcasting.connections.reverb.key', 'ralp37ndeim3xxs252fq') }}";
    window.SECURITY_QUESTIONS = @json(\App\Http\Controllers\AuthController::SECURITY_QUESTIONS);
</script>
<script src="{{ asset('js/chat-app.js') }}?v={{ time() }}"></script>
@endsection
