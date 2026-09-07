@extends('layouts.app')

@section('title', 'Acceso Anónimo')

@section('styles')
<style>
    .auth-container {
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        padding-top: calc(20px + env(safe-area-inset-top, 0px));
        padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        background: radial-gradient(circle at 50% 25%, #181818 0%, #101010 100%);
    }
    .auth-card {
        width: 100%;
        max-width: 460px;
        background: #151515;
        border: 1px solid rgba(227, 219, 204, 0.14);
        border-radius: 20px;
        box-shadow: 0 24px 48px rgba(0,0,0,0.7);
        overflow: hidden;
        backdrop-filter: blur(16px);
    }
    .auth-banner {
        padding: 28px 24px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(227, 219, 204, 0.1);
        background: rgba(227, 219, 204, 0.02);
    }
    .auth-body {
        padding: 26px;
    }
    .auth-tab-btn {
        flex: 1;
        padding: 13px 8px;
        background: transparent;
        border: none;
        font-weight: 600;
        font-size: 0.85rem;
        color: #8e897f;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .auth-tab-btn.active {
        color: #E3DBCC;
        font-weight: 700;
        border-bottom-color: #E3DBCC;
    }
    .auth-section-divider {
        margin: 22px 0 16px;
        padding-top: 16px;
        border-top: 1px dashed rgba(227, 219, 204, 0.16);
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--color-nude);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    @media (max-width: 480px) {
        .auth-container {
            padding: 10px;
        }
        .auth-card {
            border-radius: 16px;
        }
        .auth-banner {
            padding: 20px 16px 14px;
        }
        .auth-banner img {
            height: 60px !important;
        }
        .auth-banner h1 {
            font-size: 1.55rem !important;
        }
        .auth-body {
            padding: 18px 14px;
        }
        .auth-tab-btn {
            font-size: 0.8rem;
            padding: 11px 4px;
        }
    }
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <!-- Header Banner -->
        <div class="auth-banner">
            <div style="margin: 0 auto 8px; display: flex; align-items: center; justify-content: center;">
                <img src="{{ asset('images/logo.png') }}" alt="Enigma Logo" style="height: 72px; width: auto; max-width: 170px; object-fit: contain; filter: drop-shadow(0 0 20px rgba(227, 219, 204, 0.25));">
            </div>
            <h1 class="brand-title" style="font-size: 1.9rem; color: #FDFCF8; margin-bottom: 2px; letter-spacing: 0.06em; text-transform: uppercase;">Enigma</h1>
            <p style="font-size: 0.8rem; color: #E3DBCC; opacity: 0.85; letter-spacing: 0.03em;">Secure Anonymous Messaging · Canales encriptados</p>
        </div>

        <!-- Tab Selector: 3 Pestañas (Login, Registro, Recuperación) -->
        <div style="display: flex; border-bottom: 1px solid rgba(227, 219, 204, 0.1); background: #181818;">
            <button id="tab-login-btn" class="auth-tab-btn active" onclick="switchAuthTab('login')">
                Iniciar Sesión
            </button>
            <button id="tab-register-btn" class="auth-tab-btn" onclick="switchAuthTab('register')">
                Nueva Identidad
            </button>
            <button id="tab-recovery-btn" class="auth-tab-btn" onclick="switchAuthTab('recovery')">
                Recuperar
            </button>
        </div>

        <div class="auth-body">
            
            @if ($errors->any())
                <div style="background: rgba(224, 108, 117, 0.12); border: 1px solid rgba(224, 108, 117, 0.4); border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem; color: #fca5a5;">
                    <ul style="list-style: none; padding-left: 0; margin: 0;">
                        @foreach ($errors->all() as $error)
                            <li style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e06c75" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span>{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- ================================================================
                 1. FORMULARIO DE INICIO DE SESIÓN
                 ================================================================ -->
            <form id="form-login" action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario (@username)</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 14px; color: #E3DBCC; font-family: var(--font-mono); font-weight: 700;">@</span>
                        <input type="text" name="username" class="form-control" style="padding-left: 34px;" placeholder="ejemplo: sombra" required autocomplete="username" autofocus value="{{ old('username') }}">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div style="display: flex; justify-content: flex-end; margin-bottom: 22px;">
                    <a href="javascript:void(0)" onclick="switchAuthTab('recovery')" style="color: var(--color-nude); font-size: 0.8rem; text-decoration: none; opacity: 0.9; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">
                        ¿Olvidaste tu contraseña? Recuperar acceso
                    </a>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem;">
                    Acceder al Canal Seguro
                </button>
            </form>

            <!-- ================================================================
                 2. FORMULARIO DE REGISTRO ANÓNIMO CON PREGUNTAS DE SEGURIDAD
                 ================================================================ -->
            <form id="form-register" action="{{ route('register.submit') }}" method="POST" style="display: none;">
                @csrf
                
                <!-- Avatar Preview -->
                <div style="text-align: center; margin-bottom: 18px;">
                    <img id="avatar-preview-img" src="https://api.dicebear.com/7.x/bottts-neutral/svg?seed=anonimo" alt="Avatar" style="width: 66px; height: 66px; border-radius: 50%; background: #1e1e1e; border: 2px solid #E3DBCC; margin-bottom: 6px; box-shadow: 0 0 16px rgba(227, 219, 204, 0.2);">
                    <div style="font-size: 0.72rem; color: #E3DBCC; opacity: 0.75;">Identidad de robot asignada automáticamente</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre de Usuario Único</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 14px; color: #E3DBCC; font-family: var(--font-mono); font-weight: 700;">@</span>
                        <input type="text" id="reg-username" name="username" class="form-control" style="padding-left: 34px;" placeholder="min 3 caracteres (letras, num, _)" required autocomplete="off" oninput="updateAvatarPreview(this.value)">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Apodo o Alias Visible (Opcional)</label>
                    <input type="text" name="display_name" class="form-control" placeholder="ej: Cyber Ghost">
                </div>

                <div class="form-group">
                    <label class="form-label">Foto de Perfil (Link directo a Imagen - Opcional)</label>
                    <input type="url" id="reg-avatar-url" name="avatar_url" class="form-control" placeholder="https://ejemplo.com/mi-foto.jpg" autocomplete="off" oninput="updateAvatarPreview()">
                    <span style="font-size: 0.72rem; color: #8e897f; margin-top: 4px; display: block;">Usa un enlace directo a una imagen (.jpg, .png). Si lo dejas vacío, se genera un robot anónimo.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña Secreta</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repite tu contraseña" required autocomplete="new-password">
                </div>

                <!-- Sección: Preguntas de Seguridad para Recuperación -->
                <div class="auth-section-divider">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Preguntas de Seguridad (Recuperación)</span>
                </div>
                <p style="font-size: 0.76rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
                    Configura 2 preguntas secretas para poder recuperar tu cuenta si olvidas tu contraseña:
                </p>

                <div class="form-group">
                    <label class="form-label">Pregunta Secreta 1</label>
                    <select name="security_question_1" class="form-control" required>
                        <option value="" disabled selected>Selecciona tu primera pregunta...</option>
                        @foreach($securityQuestions as $q)
                            <option value="{{ $q }}">{{ $q }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Respuesta Secreta 1</label>
                    <input type="text" name="security_answer_1" class="form-control" placeholder="Escribe tu respuesta secreta 1" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label">Pregunta Secreta 2</label>
                    <select name="security_question_2" class="form-control" required>
                        <option value="" disabled selected>Selecciona una segunda pregunta distinta...</option>
                        @foreach($securityQuestions as $q)
                            <option value="{{ $q }}">{{ $q }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Respuesta Secreta 2</label>
                    <input type="text" name="security_answer_2" class="form-control" placeholder="Escribe tu respuesta secreta 2" required autocomplete="off">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem;">
                    Crear Identidad y Entrar
                </button>
            </form>

            <!-- ================================================================
                 3. FORMULARIO DE RECUPERACIÓN DE CUENTA EN 2 PASOS
                 ================================================================ -->
            <div id="form-recovery" style="display: none;">
                
                <!-- Feedback message box -->
                <div id="recovery-alert" class="hidden" style="border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 0.85rem;"></div>

                <!-- PASO 1: Solicitar @username para cargar preguntas -->
                <div id="recovery-step-1">
                    <div style="text-align: center; margin-bottom: 16px;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); display: inline-flex; align-items: center; justify-content: center; color: var(--color-nude); margin-bottom: 8px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <h3 style="color: var(--color-off-white); font-size: 1.1rem; font-family: var(--font-serif); margin-bottom: 4px;">Recuperar Canal Anónimo</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                            Ingresa tu @username para validar tus 2 preguntas de seguridad secretas.
                        </p>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Nombre de Usuario (@username)</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <span style="position: absolute; left: 14px; color: #E3DBCC; font-family: var(--font-mono); font-weight: 700;">@</span>
                            <input type="text" id="recovery-username-input" class="form-control" style="padding-left: 34px;" placeholder="ejemplo: sombra" autocomplete="username">
                        </div>
                    </div>

                    <button type="button" id="btn-fetch-questions" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 0.95rem;" onclick="appRecovery.fetchQuestions()">
                        <span>Obtener Preguntas de Seguridad</span>
                    </button>
                </div>

                <!-- PASO 2: Responder preguntas y definir nueva contraseña -->
                <div id="recovery-step-2" style="display: none;">
                    
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: 12px; margin-bottom: 18px;">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--bg-input); display: flex; align-items: center; justify-content: center; color: var(--color-nude); font-weight: bold; font-size: 0.9rem;">@</div>
                        <div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Recuperando cuenta:</div>
                            <div id="recovery-target-user" style="font-weight: 700; color: var(--color-off-white); font-family: var(--font-mono); font-size: 0.9rem;"></div>
                        </div>
                        <button type="button" class="btn-icon" style="margin-left: auto; width: 28px; height: 28px; font-size: 0.75rem;" onclick="appRecovery.resetToStep1()" title="Cambiar usuario">✕</button>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="color: var(--color-nude);" id="recovery-q1-label">Pregunta 1</label>
                        <input type="text" id="recovery-a1-input" class="form-control" placeholder="Escribe tu respuesta exacta 1" autocomplete="off">
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" style="color: var(--color-nude);" id="recovery-q2-label">Pregunta 2</label>
                        <input type="text" id="recovery-a2-input" class="form-control" placeholder="Escribe tu respuesta exacta 2" autocomplete="off">
                    </div>

                    <div class="auth-section-divider">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Nueva Contraseña</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" id="recovery-new-password" class="form-control" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                    </div>

                    <div class="form-group" style="margin-bottom: 22px;">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" id="recovery-new-password-confirm" class="form-control" placeholder="Repite la nueva contraseña" autocomplete="new-password">
                    </div>

                    <button type="button" id="btn-submit-reset" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem;" onclick="appRecovery.submitReset()">
                        <span>Restablecer Contraseña y Entrar</span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
function switchAuthTab(tab) {
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-register');
    const recoveryForm = document.getElementById('form-recovery');
    
    const loginBtn = document.getElementById('tab-login-btn');
    const registerBtn = document.getElementById('tab-register-btn');
    const recoveryBtn = document.getElementById('tab-recovery-btn');

    // Desactivar todos los botones
    [loginBtn, registerBtn, recoveryBtn].forEach(b => b.classList.remove('active'));

    // Ocultar todos los formularios
    loginForm.style.display = 'none';
    registerForm.style.display = 'none';
    recoveryForm.style.display = 'none';

    if (tab === 'login') {
        loginForm.style.display = 'block';
        loginBtn.classList.add('active');
    } else if (tab === 'register') {
        registerForm.style.display = 'block';
        registerBtn.classList.add('active');
    } else if (tab === 'recovery') {
        recoveryForm.style.display = 'block';
        recoveryBtn.classList.add('active');
        document.getElementById('recovery-username-input')?.focus();
    }
}

function updateAvatarPreview() {
    const img = document.getElementById('avatar-preview-img');
    const customUrl = (document.getElementById('reg-avatar-url')?.value || '').trim();
    const username = (document.getElementById('reg-username')?.value || '').trim();

    if (customUrl.startsWith('http://') || customUrl.startsWith('https://')) {
        img.src = customUrl;
        img.onerror = function() {
            img.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(username || 'anonimo');
        };
    } else if (username.length > 0) {
        img.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=' + encodeURIComponent(username);
    } else {
        img.src = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=anonimo';
    }
}

// Controlador de Recuperación de Cuenta
const appRecovery = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    currentUsername: '',

    showAlert(message, isError = true) {
        const alertEl = document.getElementById('recovery-alert');
        if (!alertEl) return;

        alertEl.classList.remove('hidden');
        if (isError) {
            alertEl.style.background = 'rgba(224, 108, 117, 0.12)';
            alertEl.style.border = '1px solid rgba(224, 108, 117, 0.4)';
            alertEl.style.color = '#fca5a5';
        } else {
            alertEl.style.background = 'rgba(227, 219, 204, 0.15)';
            alertEl.style.border = '1px solid rgba(227, 219, 204, 0.4)';
            alertEl.style.color = '#FDFCF8';
        }
        alertEl.textContent = message;
    },

    hideAlert() {
        const alertEl = document.getElementById('recovery-alert');
        if (alertEl) alertEl.classList.add('hidden');
    },

    async fetchQuestions() {
        const username = (document.getElementById('recovery-username-input')?.value || '').trim();
        if (!username) {
            this.showAlert('Por favor ingresa tu nombre de usuario (@username).', true);
            return;
        }

        const btn = document.getElementById('btn-fetch-questions');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Verificando...';
        }
        this.hideAlert();

        try {
            const res = await fetch('/recovery/questions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ username: username })
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                this.currentUsername = data.username;
                document.getElementById('recovery-target-user').textContent = `@${data.username}`;
                document.getElementById('recovery-q1-label').textContent = data.question_1;
                document.getElementById('recovery-q2-label').textContent = data.question_2;

                document.getElementById('recovery-step-1').style.display = 'none';
                document.getElementById('recovery-step-2').style.display = 'block';
                document.getElementById('recovery-a1-input')?.focus();
            } else {
                this.showAlert(data.message || 'No se pudieron obtener preguntas para este usuario.', true);
            }
        } catch (e) {
            this.showAlert('Error de conexión. Inténtalo de nuevo.', true);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Obtener Preguntas de Seguridad</span>';
            }
        }
    },

    resetToStep1() {
        this.currentUsername = '';
        this.hideAlert();
        document.getElementById('recovery-step-1').style.display = 'block';
        document.getElementById('recovery-step-2').style.display = 'none';
        document.getElementById('recovery-username-input')?.focus();
    },

    async submitReset() {
        const a1 = (document.getElementById('recovery-a1-input')?.value || '').trim();
        const a2 = (document.getElementById('recovery-a2-input')?.value || '').trim();
        const password = document.getElementById('recovery-new-password')?.value || '';
        const passwordConfirmation = document.getElementById('recovery-new-password-confirm')?.value || '';

        if (!a1 || !a2) {
            this.showAlert('Debes responder a ambas preguntas de seguridad.', true);
            return;
        }

        if (password.length < 6) {
            this.showAlert('La nueva contraseña debe tener al menos 6 caracteres.', true);
            return;
        }

        if (password !== passwordConfirmation) {
            this.showAlert('Las nuevas contraseñas no coinciden.', true);
            return;
        }

        const btn = document.getElementById('btn-submit-reset');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Restableciendo acceso...';
        }
        this.hideAlert();

        try {
            const res = await fetch('/recovery/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    username: this.currentUsername,
                    answer_1: a1,
                    answer_2: a2,
                    password: password,
                    password_confirmation: passwordConfirmation
                })
            });

            const data = await res.json();

            if (res.ok) {
                this.showAlert(data.message || 'Contraseña restablecida con éxito. Entrando al chat...', false);
                setTimeout(() => {
                    window.location.href = '/chat';
                }, 1000);
            } else {
                this.showAlert(data.message || 'Respuestas incorrectas o error al restablecer contraseña.', true);
            }
        } catch (e) {
            this.showAlert('Error de conexión al procesar la solicitud.', true);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Restablecer Contraseña y Entrar</span>';
            }
        }
    }
};
</script>
@endsection
