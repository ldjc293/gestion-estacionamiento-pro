# Sistema de Gestión de Pagos - Estacionamiento Residencial

Este sistema está diseñado para la gestión y control de pagos mensuales del estacionamiento residencial para los Bloques 27 al 32. Permite a los residentes declarar sus pagos, gestionar controles de acceso (500 controles en total: 250 posiciones × 2 receptores A y B) y a los administradores u operadores llevar el control y auditoría de la cobranza.

## Estructura del Proyecto

El proyecto está diseñado bajo un patrón arquitectónico **MVC (Modelo-Vista-Controlador)** personalizado y limpio:

```text
├── app/                      # Lógica principal de la aplicación
│   ├── controllers/          # Controladores (AuthController, AdminController, etc.)
│   ├── helpers/              # Clases de ayuda (MailHelper, PDFHelper, ValidationHelper, etc.)
│   ├── models/               # Modelos de Base de Datos (Usuario, Pago, Apartamento, etc.)
│   └── views/                # Vistas de la aplicación estructuradas por Rol
├── config/                   # Archivos de configuración y constantes globales
├── database/                 # Recursos de base de datos
│   ├── archive/              # Historial de parches SQL y scripts de diagnóstico antiguos (Organizado)
│   ├── migrations/           # Migraciones activas
│   ├── schema.sql            # Estructura principal de la base de datos MySQL
│   ├── seeds.sql             # Semillas de datos mínimos iniciales
│   └── supabase_schema.sql   # Esquema adaptado para compatibilidad con Supabase
├── public/                   # Carpeta pública del servidor web
│   ├── index.php             # Controlador frontal (Front Controller) y enrutador
│   ├── css/                  # Estilos CSS
│   ├── js/                   # Librerías y scripts JS principales
│   └── uploads/              # Archivo de subida de comprobantes y recibos generados
├── tools/                    # Herramientas de desarrollo y diagnóstico (Organizado)
│   ├── public_debug/         # Scripts de depuración movidos desde la carpeta public/
│   └── (scripts de raíz)     # Scripts de prueba y utilidades movidos desde la raíz
├── .env.example              # Plantilla para variables de entorno locales
├── composer.json             # Dependencias del proyecto de PHP (Dompdf, PHPMailer, etc.)
└── README.md                 # Esta documentación
```

## Requisitos del Sistema

* PHP >= 8.1
* Servidor Web (Apache con `mod_rewrite` habilitado o Nginx)
* MySQL >= 5.7 o PostgreSQL
* Composer para la instalación de dependencias

## Instalación y Configuración Local

1. **Clonar/Descargar el repositorio** en tu directorio de servidor local (ej. `htdocs/` o `/var/www/html/`).
2. **Instalar dependencias de PHP:**
   ```bash
   composer install
   ```
3. **Configurar Variables de Entorno:**
   * Copia el archivo `.env.example` y cámbialo a `.env`.
   * Configura las credenciales de tu base de datos y la URL base:
     ```env
     APP_URL=http://localhost/Gestion-estacionamiento
     DB_HOST=localhost
     DB_NAME=estacionamiento_db
     DB_USER=root
     DB_PASS=tu_contraseña
     ```
4. **Inicializar la Base de Datos:**
   * Crea una base de datos llamada `estacionamiento_db`.
   * Importa el archivo de base de datos principal localizado en `database/schema.sql` en tu gestor de base de datos.
   * *(Opcional)* Si requieres semillas de datos para pruebas, importa `database/seeds.sql`.

## Características Clave del Sistema

* **Seguridad Avanzada:**
  * Protección contra ataques de inyección SQL mediante sentencias preparadas con PDO de manera uniforme.
  * Protección CSRF integrada en todos los formularios de acción POST.
  * Control de inactividad de sesión y bloqueo de login tras 5 intentos fallidos consecutivos.
  * Cookies de sesión seguras con directivas HTTP `HttpOnly` y `SameSite=Strict`.
* **Roles de Usuario:**
  * **Administrador:** Acceso completo.
  * **Operador:** Gestión manual de mensualidades, aprobación/rechazo de pagos y registros.
  * **Consultor:** Exportación de reportes a PDF/Excel y visualización de estadísticas.
  * **Cliente (Residente):** Declaración de transferencias, visualización de estados de cuenta e historial.
* **Procesos Automatizados:**
  * Cálculo y generación de mensualidades mensual (día 5 de cada mes).
  * Control de morosidad automático (bloqueo preventivo de controles a los 4 meses vencidos).
