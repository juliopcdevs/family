# Especificación - Family Hub

## 1. Visión General

**Family Hub** es una aplicación web responsive para gestión familiar compartida. Permite a múltiples miembros de una familia (típicamente padres) coordinar actividades diarias mediante listas de compra, calendario, tareas y recordatorios de cumpleaños.

### Problema que resuelve
Las familias necesitan una forma simple y visual de compartir información cotidiana sin usar múltiples apps o grupos de WhatsApp desordenados.

### Usuarios objetivo
Familias (principalmente padres) que buscan coordinar tareas domésticas y eventos familiares.

---

## 2. Stack Tecnológico

### Backend
- **Framework**: Laravel 11.x (PHP 8.2+)
- **Base de datos**: MongoDB 7.0
- **Autenticación**: Laravel Sanctum (SPA)
- **Email**: SMTP (configuración en .env)

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **Router**: Vue Router
- **HTTP Client**: Axios
- **Build**: Vite
- **Estilos**: Tailwind CSS
- **PWA**: vite-plugin-pwa (Progressive Web App)
  - Instalable en móvil (iOS y Android)
  - Banner de instalación para Android
  - Instrucciones de instalación para iOS
  - Service Worker para cache
  - Manifest con iconos 192x192 y 512x512

### Infraestructura
- **Contenedores**: Docker + Docker Compose
- **Reverse Proxy**: Traefik (SSL automático con Let's Encrypt)
- **Estructura**: Similar a proyecto `whatmovie`
  - `docker-compose.yml` (desarrollo)
  - `docker-compose.prod.yml` (producción)
  - Scripts `deploy.sh` y `deploy-remote.sh`

### Convenciones
- TypeScript en frontend (modo estricto)
- Imports absolutos con `@/`
- Kebab-case para archivos
- PascalCase para componentes Vue
- camelCase para variables/funciones
- Conventional commits (feat, fix, chore, docs)

---

## 3. Arquitectura

### Estructura de carpetas (Laravel)
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── ShoppingListController.php
│   │   ├── CalendarController.php
│   │   ├── TaskController.php
│   │   ├── BirthdayController.php
│   │   └── FamilyController.php
│   └── Middleware/
│       └── EnsureFamilyMembership.php
├── Models/
│   ├── User.php
│   ├── Family.php
│   ├── ShoppingItem.php
│   ├── CalendarEvent.php
│   ├── Task.php
│   └── Birthday.php
├── Events/
└── Notifications/

resources/
└── js/
    ├── components/
    │   ├── layout/
    │   │   ├── AppLayout.vue
    │   │   ├── Sidebar.vue
    │   │   └── Navbar.vue
    │   ├── shopping/
    │   │   ├── ShoppingCard.vue
    │   │   ├── ShoppingSearch.vue
    │   │   └── ShoppingCart.vue
    │   ├── calendar/
    │   │   ├── MonthView.vue
    │   │   └── YearView.vue
    │   ├── tasks/
    │   │   ├── TaskList.vue
    │   │   └── TaskItem.vue
    │   └── birthdays/
    │       ├── BirthdayList.vue
    │       └── BirthdayCard.vue
    ├── views/
    │   ├── auth/
    │   │   ├── Login.vue
    │   │   ├── Register.vue
    │   │   ├── VerifyEmail.vue
    │   │   ├── ForgotPassword.vue
    │   │   └── ResetPassword.vue
    │   ├── family/
    │   │   ├── CreateOrJoin.vue
    │   │   └── Settings.vue
    │   ├── Dashboard.vue
    │   ├── ShoppingList.vue
    │   ├── Calendar.vue
    │   ├── Tasks.vue
    │   └── Birthdays.vue
    ├── services/
    │   ├── api.ts
    │   ├── auth.service.ts
    │   ├── family.service.ts
    │   └── ...
    ├── stores/
    │   ├── auth.ts
    │   └── family.ts
    ├── types/
    ├── utils/
    ├── router/
    └── App.vue
```

---

## 4. Modelos de Datos (MongoDB)

### User
```javascript
{
  _id: ObjectId,
  name: String,
  email: String (unique, index),
  email_verified_at: Date | null,
  password: String (hashed),
  family_id: ObjectId | null (ref: families),
  created_at: Date,
  updated_at: Date
}
```

### Family
```javascript
{
  _id: ObjectId,
  name: String,
  code: String (unique, 8 chars uppercase alphanumeric, index),
  created_by: ObjectId (ref: users),
  created_at: Date,
  updated_at: Date
}
```

### ShoppingItem (predefinidos con imagen)
```javascript
{
  _id: ObjectId,
  name: String,
  slug: String (unique, index),
  image_url: String,
  created_at: Date,
  updated_at: Date
}
```

### ShoppingListItem (items en carrito actual + historial)
```javascript
{
  _id: ObjectId,
  family_id: ObjectId (ref: families, index),
  item_name: String,
  item_slug: String | null, // null si es temporal
  is_predefined: Boolean, // true si existe en ShoppingItem
  image_url: String | null, // null si es temporal (letra inicial en frontend)
  is_in_cart: Boolean (index), // true = en carrito, false = historial
  usage_count: Number, // veces usado en total
  last_used_at: Date (index), // para ordenar "más utilizados"
  added_by: ObjectId (ref: users),
  created_at: Date,
  updated_at: Date
}

// Índices compuestos:
// { family_id: 1, is_in_cart: 1 }
// { family_id: 1, usage_count: -1, last_used_at: -1 }
```

### CalendarEvent
```javascript
{
  _id: ObjectId,
  family_id: ObjectId (ref: families, index),
  title: String,
  date: Date (index), // fecha del evento
  created_by: ObjectId (ref: users),
  created_at: Date,
  updated_at: Date
}

// Índice compuesto: { family_id: 1, date: 1 }
```

### Task
```javascript
{
  _id: ObjectId,
  family_id: ObjectId (ref: families, index),
  title: String,
  is_completed: Boolean (index),
  completed_at: Date | null,
  created_by: ObjectId (ref: users),
  created_at: Date (index), // para orden LIFO
  updated_at: Date
}

// Índice compuesto: { family_id: 1, is_completed: 1, created_at: -1 }
```

### Birthday
```javascript
{
  _id: ObjectId,
  family_id: ObjectId (ref: families, index),
  person_name: String,
  birth_date: Date, // fecha completa de nacimiento
  created_by: ObjectId (ref: users),
  created_at: Date,
  updated_at: Date
}

// Índice: { family_id: 1 }
// Cálculo de próximo cumpleaños en backend
```

### TemporarySearchLog (para admin)
```javascript
{
  _id: ObjectId,
  family_id: ObjectId (ref: families),
  search_term: String (index),
  not_found: Boolean, // true si no existe en ShoppingItem
  created_at: Date (TTL index: 90 días)
}
```

---

## 5. Funcionalidades Core

### 5.1 Autenticación

#### Registro
- Formulario: nombre, email, password, confirmación password
- Validaciones: email único, password mínimo 8 caracteres
- Envío de email de verificación
- Redirección a página "Verifica tu email"

#### Verificación Email
- Link en email con token
- Al hacer clic → marca `email_verified_at`
- Redirección a login

#### Login
- Email + password
- Verifica que email esté verificado
- Genera token Sanctum (SPA)
- Si NO tiene `family_id` → redirige a "Crear o unirse a familia"
- Si tiene `family_id` → redirige a Dashboard

#### Recuperación de contraseña
- Página "Olvidé mi contraseña" → introduce email
- Envía email con link de reset (token temporal)
- Página de reset con token → introduce nueva contraseña
- Actualiza password y redirige a login

### 5.2 Gestión de Familias

#### Crear familia
- Usuario logueado sin familia ve pantalla con 2 opciones
- Botón "Crear familia" → genera código único (8 chars)
- Asigna `family_id` al usuario
- Muestra código generado con botón "Compartir por WhatsApp"
- Link WhatsApp: `whatsapp://send?text=Únete a nuestra familia en Family Hub con el código: {CODE}`

#### Unirse a familia
- Formulario: introducir código de 8 caracteres
- Valida que el código exista
- Asigna `family_id` al usuario
- Redirección a Dashboard

#### Compartir código
- En settings/perfil: muestra código actual
- Botón "Compartir por WhatsApp"

### 5.3 Lista de la Compra

#### Pantalla principal
**Estructura:**
1. **Buscador** (arriba)
2. **Carrito** (sección fija superior) - items con `is_in_cart = true`
3. **Más utilizados** (abajo del carrito) - items ordenados por `usage_count` DESC, `last_used_at` DESC, con `is_in_cart = false`
4. **Botón "Recargar"** - recarga datos del servidor

#### Funcionamiento del buscador
- Input de texto, búsqueda al escribir (debounce 300ms)
- Busca en `ShoppingItem` (predefinidos) por `name` (match parcial)
- Muestra cards de resultados:
  - **Si existe predefinido**: Card con imagen + nombre
  - **Si NO existe**: Card con letra inicial (primera letra uppercase) + nombre escrito

#### Cards de items
- **Predefinido**: Imagen circular + nombre debajo
- **Temporal**: Círculo con letra inicial (CSS background color) + nombre
- Tap/click en card → añade a carrito

#### Añadir a carrito
- Si es predefinido:
  - Busca en `ShoppingListItem` si ya existe ese `item_slug` para esta familia
  - Si existe: actualiza `is_in_cart = true`, incrementa `usage_count`, actualiza `last_used_at`
  - Si no existe: crea nuevo con `is_predefined = true`, `is_in_cart = true`, `usage_count = 1`
- Si es temporal:
  - Busca por `item_name` exacto (case-insensitive)
  - Si existe: actualiza igual que predefinido
  - Si no existe: crea nuevo con `is_predefined = false`, `item_slug = null`, `image_url = null`
  - Registra en `TemporarySearchLog` para análisis admin

#### Quitar del carrito
- Tap/click en item del carrito
- Actualiza `is_in_cart = false`
- Mantiene en historial (para "más utilizados")

#### Más utilizados
- Query: `family_id + is_in_cart=false`, ordenado por `usage_count DESC, last_used_at DESC`
- Muestra cards (con imagen o letra inicial según tipo)
- Tap para volver a añadir al carrito

#### Botón recargar
- Re-fetch de todos los datos de la lista
- Actualiza carrito y más utilizados

### 5.4 Calendario

#### Vista mensual (default)
- Grid 7x5 (o 7x6 según mes)
- Cabecera: lun-dom
- Navegación: flechas ← mes/año →
- Celdas:
  - Día del mes
  - Eventos del día (título, máx 2-3 visibles con scroll)
- Click en día vacío → modal/formulario para añadir evento
- Click en evento → modal para ver/editar/eliminar

#### Vista anual
- Grid 3x4 o 2x6 (12 mini-calendarios)
- Cada mes muestra días con indicador de eventos (punto o badge)
- Click en mes → cambia a vista mensual de ese mes

#### CRUD Eventos
- **Crear**: Modal con título + date picker
- **Editar**: Modal pre-llenado, actualiza
- **Eliminar**: Botón eliminar en modal

### 5.5 Tareas

#### Lista principal (sin completar)
- Query: `family_id + is_completed=false`, ordenado por `created_at DESC` (LIFO)
- Cada item: checkbox + título
- Click en checkbox → marca `is_completed=true`, `completed_at=now()`, mueve a sección de abajo

#### Lista completadas
- Query: `family_id + is_completed=true`, ordenado por `completed_at DESC`
- Cada item: ~~texto tachado~~ + botón "Eliminar"
- Click eliminar → DELETE del documento

#### CRUD
- **Crear**: Input arriba, Enter o botón "+" → añade nueva
- **Completar**: Checkbox
- **Eliminar**: Solo desde completadas

### 5.6 Cumpleaños

#### Lista
- Query: `family_id`, ordenado por próximo cumpleaños
- Cálculo backend: próximo cumpleaños = `birth_date` con año actual o siguiente
- Cada card muestra:
  - Nombre
  - Fecha cumpleaños (DD/MM)
  - Edad que cumplirá
  - Días restantes (ej: "En 15 días")

#### CRUD
- **Crear**: Formulario (nombre + date picker)
- **Editar**: Modal pre-llenado
- **Eliminar**: Botón en card o modal

### 5.7 Dashboard

#### 4 widgets:
1. **Próximos eventos** (4 más cercanos en fecha)
2. **Tareas pendientes** (4 últimas añadidas sin completar)
3. **Lista de compra** (4 primeros items del carrito actual)
4. **Próximos cumpleaños** (4 más cercanos)

Cada widget:
- Título de sección
- Lista/cards de items
- Botón "Ver todo" → navega a sección completa

---

## 6. Pantallas y Rutas

### Públicas (no requieren auth)
- `/login` - Login.vue
- `/register` - Register.vue
- `/verify-email` - VerifyEmail.vue
- `/forgot-password` - ForgotPassword.vue
- `/reset-password/:token` - ResetPassword.vue

### Protegidas (requieren auth + email verificado)
- `/family/setup` - CreateOrJoin.vue (solo si NO tiene family_id)

### Protegidas (requieren auth + email verificado + family_id)
- `/` o `/dashboard` - Dashboard.vue
- `/shopping` - ShoppingList.vue
- `/calendar` - Calendar.vue
- `/tasks` - Tasks.vue
- `/birthdays` - Birthdays.vue
- `/family/settings` - Settings.vue (ver código, compartir, salir de familia)

### Middleware (guards)
1. `auth` - requiere login + email verificado
2. `guest` - solo accesible si NO está logueado
3. `has-family` - requiere estar en una familia
4. `no-family` - requiere NO estar en una familia (para setup)

---

## 7. API Endpoints

### Auth
- `POST /api/register` - Registro
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `POST /api/email/verification-notification` - Reenviar email verificación
- `GET /api/email/verify/:id/:hash` - Verificar email
- `POST /api/forgot-password` - Solicitar reset
- `POST /api/reset-password` - Resetear contraseña
- `GET /api/user` - Usuario actual

### Family
- `POST /api/family/create` - Crear familia
- `POST /api/family/join` - Unirse con código
- `GET /api/family/current` - Info familia actual
- `POST /api/family/leave` - Salir de familia

### Shopping List
- `GET /api/shopping` - Lista actual (carrito + historial)
- `POST /api/shopping/add` - Añadir/mover a carrito
- `POST /api/shopping/remove` - Quitar del carrito
- `GET /api/shopping/search?q=query` - Buscar items predefinidos
- `GET /api/shopping/frequent` - Más utilizados

### Calendar
- `GET /api/calendar/events?month=X&year=Y` - Eventos del mes
- `POST /api/calendar/events` - Crear evento
- `PUT /api/calendar/events/:id` - Editar evento
- `DELETE /api/calendar/events/:id` - Eliminar evento

### Tasks
- `GET /api/tasks` - Todas las tareas (completadas y no)
- `POST /api/tasks` - Crear tarea
- `PUT /api/tasks/:id/complete` - Marcar completada
- `DELETE /api/tasks/:id` - Eliminar tarea

### Birthdays
- `GET /api/birthdays` - Todos los cumpleaños (ordenados)
- `POST /api/birthdays` - Crear cumpleaños
- `PUT /api/birthdays/:id` - Editar cumpleaños
- `DELETE /api/birthdays/:id` - Eliminar cumpleaños

### Dashboard
- `GET /api/dashboard` - Datos del dashboard (4 de cada)

### Admin (opcional, para análisis)
- `GET /api/admin/temporary-searches` - Items temporales creados

---

## 8. Validaciones y Reglas

### Registro
- Email: válido, único, requerido
- Password: mínimo 8 caracteres, requerido
- Nombre: requerido, máx 100 chars

### Código familia
- 8 caracteres alfanuméricos uppercase
- Generación: verificar unicidad antes de crear
- Join: validar que existe

### Shopping items
- Nombre: requerido, máx 100 chars
- Búsqueda: trim, case-insensitive

### Calendar events
- Título: requerido, máx 200 chars
- Fecha: requerida, fecha válida

### Tasks
- Título: requerido, máx 200 chars

### Birthdays
- Nombre: requerido, máx 100 chars
- Fecha: requerida, fecha válida (pasado o presente)

---

## 9. UX y Diseño

### Responsive
- Mobile-first (320px+)
- Tablet (768px+)
- Desktop (1024px+)

### Colores (Material Design inspired)
- Primary: #2196F3 (azul)
- Success: #4CAF50 (verde)
- Warning: #FF9800 (naranja)
- Error: #F44336 (rojo)
- Neutral: grises (#F5F5F5, #E0E0E0, #757575, #212121)

### Layout
- Sidebar navegación (desktop) / Bottom nav (mobile)
- Header con logo + nombre usuario
- Cards con sombras sutiles
- Espaciado consistente (múltiplos de 4px)

### Interacciones
- Hover states en botones/cards
- Loading spinners en peticiones
- Toasts para feedback (éxito, error)
- Confirmaciones para acciones destructivas (eliminar)

---

## 10. Seguridad

### Autenticación
- Tokens Sanctum (HTTP-only cookies para SPA)
- Rate limiting en login/register (5 intentos por minuto)

### Autorización
- Middleware `EnsureFamilyMembership` verifica que el recurso pertenece a la familia del usuario
- Validar `family_id` en todas las queries

### Datos sensibles
- Passwords hasheados con bcrypt
- Tokens de reset expiran en 60 minutos
- CSRF protection (Sanctum)

### MongoDB
- Validaciones en models (casting, required)
- Índices para performance y unicidad
- No exponer IDs internos si no es necesario

---

## 11. PWA (Progressive Web App)

### Instalación en dispositivos móviles

La aplicación es una PWA completa, instalable en móviles iOS y Android.

### Componentes PWA

#### Service Worker
- Cache de assets estáticos (JS, CSS, iconos)
- Estrategia: cache-first para assets, network-first para API
- Skip waiting + clients claim para actualizaciones inmediatas

#### Web App Manifest
```json
{
  "name": "Family Hub",
  "short_name": "Family",
  "description": "Gestión familiar compartida",
  "theme_color": "#2196F3",
  "background_color": "#FFFFFF",
  "display": "standalone",
  "orientation": "portrait",
  "scope": "/",
  "start_url": "/",
  "icons": [
    {
      "src": "/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}
```

#### Banner de instalación (Android)
- Detecta evento `beforeinstallprompt`
- Muestra banner azul en la parte superior con:
  - Texto: "Instala la app en tu dispositivo para acceder más rápido"
  - Botón "Instalar" (blanco con texto azul)
  - Botón "×" para cerrar
- Al instalar o cerrar, se guarda en localStorage para no volver a mostrar
- Se oculta automáticamente si ya está instalada (standalone mode)

#### Instrucciones iOS
- Detecta dispositivos iOS (iPad, iPhone, iPod)
- Muestra banner azul con instrucciones:
  - Icono de compartir (flecha hacia arriba)
  - Texto: "Para instalar: pulsa [icono] y luego **Añadir a pantalla de inicio**"
  - Botón "×" para cerrar
- Se guarda preferencia en localStorage
- Se oculta si ya está en modo standalone

#### Composable: usePwaInstall()
```typescript
// resources/js/composables/usePwaInstall.ts
{
  showAndroidBanner: computed boolean,
  showIosBanner: computed boolean,
  install: () => Promise<void>,
  dismiss: () => void
}
```

### Integración en Layout

**AppLayout.vue** incluye los banners en la parte superior:
1. Banner Android (si aplica)
2. Banner iOS (si aplica)
3. Resto del layout (navbar, sidebar, content)

### Iconos requeridos

Ubicación: `public/icons/`
- `icon-192x192.png` - Icono estándar
- `icon-512x512.png` - Icono alta resolución + maskable

**Formato**: PNG con fondo sólido (color primary #2196F3 o blanco con logo)

---

## 12. Testing (opcional para MVP, documentar)

### Backend
- Feature tests: endpoints principales
- Unit tests: modelos, servicios

### Frontend
- Unit tests: componentes críticos
- E2E: flujo registro → crear familia → añadir item

---

## 12. Deployment

### Desarrollo
- `docker-compose.yml` con hot-reload
- Variables en `.env.local`

### Producción
- `docker-compose.prod.yml` con Traefik
- Scripts `deploy.sh` y `deploy-remote.sh` (basados en whatmovie)
- Variables en `.env.prod`
- SSL automático con Let's Encrypt
- Volúmenes persistentes para MongoDB

### Variables de entorno (.env)
```
APP_NAME="Family Hub"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://family.tudominio.com
APP_DOMAIN=family.tudominio.com

DB_CONNECTION=mongodb
DB_HOST=mongodb
DB_PORT=27017
DB_DATABASE=family_hub
DB_USERNAME=family_admin
DB_PASSWORD=secure_password_here

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@family.tudominio.com
MAIL_FROM_NAME="${APP_NAME}"

SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=family.tudominio.com
```

---

## 13. Out of Scope (NO incluir en MVP)

- ❌ Notificaciones push
- ❌ App móvil nativa (solo web responsive)
- ❌ Sincronización en tiempo real (WebSockets/polling)
- ❌ Roles/permisos diferenciados (todos los miembros tienen acceso total)
- ❌ Exportar datos (PDF, CSV, etc.)
- ❌ Integración con calendarios externos (Google Calendar, etc.)
- ❌ Recordatorios automáticos por email/SMS
- ❌ Imágenes personalizadas de usuario
- ❌ Chat entre miembros
- ❌ Modo oscuro
- ❌ Múltiples idiomas (solo español)
- ❌ Analytics/estadísticas de uso
- ❌ Historial de cambios ("quién hizo qué")

---

## 14. Notas Técnicas

### MongoDB con Laravel
- Usar paquete `mongodb/laravel-mongodb`
- Configurar en `config/database.php`
- Models extienden `MongoDB\Laravel\Eloquent\Model`
- Usar `$connection = 'mongodb'` en models

### Vue + Laravel
- SPA total, Laravel solo API
- Vite para build
- Axios con interceptors para auth (Sanctum)
- Rutas protegidas con Vue Router guards

### Docker
- `app` (PHP-FPM)
- `nginx` (servidor web)
- `mongodb` (base de datos)
- Traefik aparte (compartido, ver whatmovie/deploy.md)

### Seed data inicial
- Crear ~50 items predefinidos para lista de compra (pan, leche, tomates, etc.)
- Script `php artisan db:seed --class=ShoppingItemSeeder`

---

## 15. Criterios de Éxito (MVP Completado)

- [x] Usuario puede registrarse y verificar email
- [x] Usuario puede crear/unirse a familia
- [x] Compartir código por WhatsApp funciona
- [x] Lista de compra: añadir/quitar items (predefinidos y temporales)
- [x] Calendario: crear/ver/editar/eliminar eventos (vista mensual y anual)
- [x] Tareas: crear, completar, eliminar
- [x] Cumpleaños: CRUD completo con cálculo de próximos
- [x] Dashboard muestra 4 items de cada sección
- [x] Botón recargar en lista de compra funciona
- [x] Responsive (mobile + desktop)
- [x] Deploy con Docker + Traefik funcional

---

**Versión**: 1.0
**Fecha**: 2026-02-15
**Autor**: Julio (juliopcdevs)
