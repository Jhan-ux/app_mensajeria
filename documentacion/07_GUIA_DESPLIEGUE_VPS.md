# 🌐 Guía de Despliegue en Servidor VPS (Producción)

Esta guía detalla el proceso paso a paso para desplegar **Enigma** en un servidor virtual privado (VPS) con **Ubuntu Linux**, **Nginx**, **PHP-FPM**, **MySQL 8** y **Supervisor** para mantener los WebSockets de **Laravel Reverb** activos 24/7.

---

## 📋 Requisitos del Servidor
- **Sistema Operativo:** Ubuntu 22.04 LTS o Ubuntu 24.04 LTS
- **Servidor Web:** Nginx
- **PHP:** PHP 8.2 o superior con extensiones (`php-fpm`, `php-mysql`, `php-mbstring`, `php-xml`, `php-curl`, `php-zip`)
- **Gestor de Paquetes:** Composer y Node.js / NPM
- **Base de Datos:** MySQL 8.0 o MariaDB 10.11+
- **Gestor de Procesos:** Supervisor

---

## 🚀 Paso 1: Clonar el Repositorio en el VPS

Conéctate por SSH a tu servidor y descarga el código:

```bash
cd /var/www
sudo git clone https://github.com/Jhan-ux/app_mensajeria.git enigma
cd /var/www/enigma
```

---

## 📦 Paso 2: Instalación de Dependencias y Permisos

```bash
# Instalar dependencias optimizadas para producción
composer install --no-dev --optimize-autoloader

# Asignar propiedad al usuario del servidor web
sudo chown -R www-data:www-data /var/www/enigma
sudo chmod -R 775 /var/www/enigma/storage /var/www/enigma/bootstrap/cache
```

---

## ⚙️ Paso 3: Configuración del Archivo de Entorno (`.env`)

Copia la plantilla de entorno y genera la clave de cifrado:

```bash
cp .env.example .env
php artisan key:generate
```

Edita tu archivo `.env` (`nano .env`) y ajusta los valores para producción:

```env
APP_NAME="Enigma"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=enigma_db
DB_USERNAME=usuario_enigma
DB_PASSWORD=tu_clave_segura

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database

REVERB_APP_ID=100001
REVERB_APP_KEY=enigma_key_segura_2026
REVERB_APP_SECRET=enigma_secret_super_seguro
REVERB_HOST="tudominio.com"
REVERB_PORT=443
REVERB_SCHEME="https"

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## 🗄️ Paso 4: Migraciones, Enlace Simbólico y Caché

```bash
# 1. Ejecutar migraciones en MySQL de producción
php artisan migrate --force

# 2. Crear enlace para servir fotos, audios y archivos
php artisan storage:link

# 3. Compilar cachés de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🤖 Paso 5: Configuración de Supervisor para Reverb (WebSockets 24/7)

Supervisor garantiza que el servidor de WebSockets se ejecute en segundo plano y se reinicie automáticamente ante fallos o reinicios del servidor.

1. Instalar supervisor:
   ```bash
   sudo apt update && sudo apt install supervisor -y
   ```

2. Crear la configuración del servicio:
   ```bash
   sudo nano /etc/supervisor/conf.d/enigma-reverb.conf
   ```

3. Contenido del archivo:
   ```ini
   [program:enigma-reverb]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/enigma/artisan reverb:start --host=127.0.0.1 --port=8080
   autostart=true
   autorestart=true
   user=www-data
   redirect_stderr=true
   stdout_logfile=/var/www/enigma/storage/logs/reverb.log
   stopwaitsecs=3600
   ```

4. Iniciar y habilitar el servicio:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start enigma-reverb:*
   ```

---

## 🌐 Paso 6: Configuración del Bloque de Servidor en Nginx

Crea el archivo de configuración para tu sitio:

```bash
sudo nano /etc/nginx/sites-available/enigma
```

Pega la siguiente configuración con soporte para proxy de WebSockets:

```nginx
server {
    listen 80;
    server_name tudominio.com www.tudominio.com;
    root /var/www/enigma/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Enrutamiento HTTP de Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy inverso para WebSockets (Reverb)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Ajustar según versión instalada
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Habilitar el sitio y reiniciar Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/enigma /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 🔒 Paso 7: Certificado SSL Gratuito (HTTPS)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d tudominio.com -d www.tudominio.com
```

---

## 🔄 Actualizaciones Futuras (Deploy Script)

Cada vez que subas cambios a GitHub y quieras actualizar tu VPS, ejecuta:

```bash
cd /var/www/enigma
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart enigma-reverb:*
```
