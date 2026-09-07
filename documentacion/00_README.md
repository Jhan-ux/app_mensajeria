# 📱 Plataforma de Mensajería Anónima en Tiempo Real (Laravel + MySQL)

Bienvenido a la documentación técnica de la aplicación de **mensajería anónima** en tiempo real desarrollada con **Laravel**, **MySQL** y **Laravel Reverb (WebSockets)**.

---

## 🔒 Filosofía de Privacidad y Anonimato
* **Sin datos personales ni correos:** El usuario solo necesita un **nombre de usuario único (`username`)** y una **contraseña**.
* **Búsqueda por `@username`:** Los usuarios se contactan e inician chats directos o grupales únicamente conociendo el `@username`.
* **Seguridad y Cero Rastreo:** Acceso seguro con autenticación rápida sin confirmaciones de email ni números de teléfono.

---

## 📂 Índice de Documentación

Esta carpeta contiene las especificaciones detalladas para la arquitectura, base de datos, APIs y plan de desarrollo del proyecto:

| Archivo | Descripción |
| :--- | :--- |
| **[01_ARQUITECTURA_Y_STACK.md](./01_ARQUITECTURA_Y_STACK.md)** | Arquitectura general, enfoque de privacidad y flujo de comunicación cliente-servidor. |
| **[02_BASE_DE_DATOS_MODELO_ER.md](./02_BASE_DE_DATOS_MODELO_ER.md)** | Modelo de Base de Datos MySQL adaptado para autenticación por `username` único. |
| **[03_EVENTOS_Y_WEBSOCKETS.md](./03_EVENTOS_Y_WEBSOCKETS.md)** | Canales de broadcasting (privados/presencia) y eventos de tiempo real con Laravel Reverb. |
| **[04_ENDPOINTS_API_Y_RUTAS.md](./04_ENDPOINTS_API_Y_RUTAS.md)** | Especificación de rutas y endpoints de la API (Auth por Username, Conversaciones, Mensajes). |
| **[05_ROADMAP_Y_FASES_DESARROLLO.md](./05_ROADMAP_Y_FASES_DESARROLLO.md)** | Plan de trabajo detallado fase por fase para la implementación del sistema. |

---

## 🚀 Resumen del Stack Tecnológico

* **Backend:** Laravel 11 / 12 (PHP 8.2+)
* **Base de Datos:** MySQL 8.0+ (InnoDB)
* **WebSockets / Tiempo Real:** Laravel Reverb + Laravel Echo
* **Autenticación Anónima:** Laravel Sanctum (login mediante `username` + `password`)
* **Manejo de Archivos:** Storage local / S3 para multimedia (imágenes, audios, documentos)
* **Frontend:** Blade + Alpine.js / TailwindCSS o Inertia.js (Vue/React)
