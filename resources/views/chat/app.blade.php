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

            <div style="display: flex; align-items: center; gap: 4px;">
                <!-- Botón Ajustes & Seguridad -->
                <button type="button" id="btn-settings-toggle" class="btn-icon" onclick="app.openSettingsModal('security')" title="Ajustes y Preguntas de Seguridad">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>

                <!-- Botón Notificaciones -->
                <button type="button" id="btn-notification-toggle" class="btn-icon" onclick="app.toggleNotifications()" title="Activar / Desactivar notificaciones">
                    <svg id="icon-notif-on" class="hidden" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--color-nude)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <svg id="icon-notif-off" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        <path d="M18.63 13A17.89 17.89 0 0 1 18 8"/>
                        <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"/>
                        <path d="M18 8a6 6 0 0 0-9.33-5"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>

                <!-- Botón Instalar / Descargar App (PWA) -->
                <button type="button" id="btn-install-pwa" class="btn-icon" onclick="app.promptInstallPwa()" title="Descargar / Instalar aplicación Enigma">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </button>

                <!-- Botón Nuevo Chat Directo -->
                <button class="btn-icon" onclick="app.openNewChatModal()" title="Nuevo chat directo por @username">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <line x1="12" y1="8" x2="12" y2="14"/>
                        <line x1="9" y1="11" x2="15" y2="11"/>
                    </svg>
                </button>

                <!-- Botón Nuevo Grupo -->
                <button class="btn-icon" onclick="app.openNewGroupModal()" title="Crear grupo anónimo">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </button>

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-icon" title="Cerrar sesión anónima" style="color: var(--text-muted);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
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
     MODALES
     ======================================================================== -->

<!-- Modal: Iniciar Chat Directo por @username -->
<div id="modal-new-chat" class="modal-backdrop">
    <div class="modal-content">
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

<!-- Modal: Ajustes de Cuenta y Seguridad (Perfil + Preguntas de Respaldo) -->
<div id="modal-settings" class="modal-backdrop">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h3 class="modal-title">Ajustes & Seguridad</h3>
            <button class="btn-icon" onclick="app.closeModal('modal-settings')">✕</button>
        </div>
        
        <!-- Tab selector de ajustes -->
        <div style="display: flex; border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface-elevated);">
            <button id="tab-settings-profile-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--color-nude); border-bottom: 2px solid var(--color-nude); font-size: 0.85rem;" onclick="app.switchSettingsTab('profile')">
                Mi Perfil
            </button>
            <button id="tab-settings-security-btn" type="button" class="btn" style="flex: 1; border-radius: 0; background: transparent; color: var(--text-muted); border-bottom: 2px solid transparent; font-size: 0.85rem;" onclick="app.switchSettingsTab('security')">
                Preguntas de Respaldo
            </button>
        </div>

        <div class="modal-body" style="max-height: 72vh; overflow-y: auto;">
            
            <!-- TAB 1: PERFIL ANÓNIMO -->
            <div id="settings-tab-profile">
                <!-- Preview del Avatar en tiempo real -->
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
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;">Debe ser un enlace directo a una imagen (.jpg, .png). Si falla, usará el robot automático.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">O generar Robot con Palabra Clave (Semilla)</label>
                    <input type="text" id="profile-edit-seed" class="form-control" placeholder="Escribe cualquier palabra para cambiar tu robot" oninput="app.updateProfileModalAvatarPreview()">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-settings')">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="app.saveProfile()">Guardar Cambios</button>
                </div>
            </div>

            <!-- TAB 2: PREGUNTAS DE RESPALDO DE SEGURIDAD -->
            <div id="settings-tab-security" style="display: none;">
                
                <!-- Estado de respaldo actual -->
                <div id="settings-security-status-box" style="padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; font-size: 0.825rem; line-height: 1.45;"></div>

                <!-- Alert de feedback -->
                <div id="settings-security-alert" class="hidden" style="border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.85rem;"></div>

                <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
                    Si olvidas tu contraseña secreta, podrás recuperar tu cuenta respondiendo exactamente a estas 2 preguntas:
                </p>

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
                    <button type="button" id="btn-save-security" class="btn btn-primary" onclick="app.saveSecurityQuestions()">
                        Guardar Respaldo
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

<!-- Modal: Descargar / Instalar Aplicación Enigma -->
<div id="modal-install-guide" class="modal-backdrop">
    <div class="modal-content" style="max-width: 440px;">
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

            <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 12px 16px; text-align: left; font-size: 0.8rem; color: var(--text-secondary); line-height: 1.6;">
                <div style="font-weight: 700; color: var(--color-nude); margin-bottom: 4px; font-size: 0.825rem;">Otras formas de instalación:</div>
                <ul style="margin: 0; padding-left: 16px;">
                    <li><strong>Chrome / Edge (PC/Mac):</strong> Pulsa el icono ⊕ en la barra de direcciones o Menú ⋮ &gt; <em>Instalar Enigma</em>.</li>
                    <li><strong>Android:</strong> Pulsa Menú ⋮ &gt; <em>Instalar Aplicación</em> o <em>Agregar a pantalla de inicio</em>.</li>
                    <li><strong>iPhone / iPad (Safari):</strong> Pulsa el botón Compartir ⎋ &gt; <em>Agregar a pantalla de inicio</em>.</li>
                </ul>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="app.closeModal('modal-install-guide')">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.REVERB_APP_KEY = "{{ env('REVERB_APP_KEY', 'ralp37ndeim3xxs252fq') }}";
    window.SECURITY_QUESTIONS = @json(\App\Http\Controllers\AuthController::SECURITY_QUESTIONS);
</script>
<script src="{{ asset('js/chat-app.js') }}?v={{ time() }}"></script>
@endsection
