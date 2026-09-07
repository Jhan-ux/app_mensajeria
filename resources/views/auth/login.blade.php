@extends('layouts.app')

@section('title', 'Acceso Anónimo')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: radial-gradient(circle at 50% 25%, #181818 0%, #101010 100%);">
    
    <div style="width: 100%; max-width: 440px; background: #151515; border: 1px solid rgba(227, 219, 204, 0.14); border-radius: 20px; box-shadow: 0 24px 48px rgba(0,0,0,0.7); overflow: hidden; backdrop-filter: blur(16px);">
        
        <!-- Header Banner -->
        <div style="padding: 30px 28px 22px; text-align: center; border-bottom: 1px solid rgba(227, 219, 204, 0.1); background: rgba(227, 219, 204, 0.02);">
            <div style="margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                <img src="{{ asset('images/logo.png') }}" alt="Enigma Logo" style="height: 76px; width: auto; max-width: 170px; object-fit: contain; filter: drop-shadow(0 0 20px rgba(227, 219, 204, 0.25));">
            </div>
            <h1 class="brand-title" style="font-size: 2rem; color: #FDFCF8; margin-bottom: 4px; letter-spacing: 0.06em; text-transform: uppercase;">Enigma</h1>
            <p style="font-size: 0.825rem; color: #E3DBCC; opacity: 0.85; letter-spacing: 0.03em;">Secure Anonymous Messaging · Canales encriptados</p>
        </div>

        <!-- Tab Selector -->
        <div style="display: flex; border-bottom: 1px solid rgba(227, 219, 204, 0.1); background: #181818;">
            <button id="tab-login-btn" onclick="switchAuthTab('login')" style="flex: 1; padding: 14px; background: transparent; border: none; font-weight: 700; font-size: 0.9rem; color: #E3DBCC; border-bottom: 2px solid #E3DBCC; cursor: pointer; transition: all 0.2s;">
                Iniciar Sesión
            </button>
            <button id="tab-register-btn" onclick="switchAuthTab('register')" style="flex: 1; padding: 14px; background: transparent; border: none; font-weight: 600; font-size: 0.9rem; color: #8e897f; border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s;">
                Nueva Identidad
            </button>
        </div>

        <div style="padding: 28px;">
            
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

            <!-- Formulario de Login -->
            <form id="form-login" action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario (@username)</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 14px; color: #E3DBCC; font-family: var(--font-mono); font-weight: 700;">@</span>
                        <input type="text" name="username" class="form-control" style="padding-left: 34px;" placeholder="ejemplo: sombra" required autocomplete="username" autofocus value="{{ old('username') }}">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem;">
                    Acceder al Canal Seguro
                </button>
            </form>

            <!-- Formulario de Registro Anónimo -->
            <form id="form-register" action="{{ route('register.submit') }}" method="POST" style="display: none;">
                @csrf
                
                <!-- Avatar Preview -->
                <div style="text-align: center; margin-bottom: 18px;">
                    <img id="avatar-preview-img" src="https://api.dicebear.com/7.x/bottts-neutral/svg?seed=anonimo" alt="Avatar" style="width: 68px; height: 68px; border-radius: 50%; background: #1e1e1e; border: 2px solid #E3DBCC; margin-bottom: 8px; box-shadow: 0 0 16px rgba(227, 219, 204, 0.2);">
                    <div style="font-size: 0.75rem; color: #E3DBCC; opacity: 0.75;">Identidad de robot asignada automáticamente</div>
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
                    <label class="form-label">Foto de Perfil (Link / URL Opcional)</label>
                    <input type="url" id="reg-avatar-url" name="avatar_url" class="form-control" placeholder="https://ejemplo.com/mi-foto.jpg" autocomplete="off" oninput="updateAvatarPreview()">
                    <span style="font-size: 0.72rem; color: #8e897f; margin-top: 4px; display: block;">Pega un enlace web a cualquier imagen o déjalo vacío para usar un avatar anónimo.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña Secreta</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repite tu contraseña" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem;">
                    Crear Identidad y Entrar
                </button>
            </form>

        </div>
    </div>
</div>

<script>
function switchAuthTab(tab) {
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-register');
    const loginBtn = document.getElementById('tab-login-btn');
    const registerBtn = document.getElementById('tab-register-btn');

    if (tab === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        loginBtn.style.color = '#E3DBCC';
        loginBtn.style.borderBottomColor = '#E3DBCC';
        registerBtn.style.color = '#8e897f';
        registerBtn.style.borderBottomColor = 'transparent';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        registerBtn.style.color = '#E3DBCC';
        registerBtn.style.borderBottomColor = '#E3DBCC';
        loginBtn.style.color = '#8e897f';
        loginBtn.style.borderBottomColor = 'transparent';
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
</script>
@endsection
