# Task 01: Setup del Proyecto

## Objetivo
Inicializar el proyecto Laravel + Vue con MongoDB y Docker, siguiendo la estructura de `whatmovie`.

---

## 1. Estructura base del proyecto

```bash
family/
├── docker/
│   ├── php/
│   │   ├── Dockerfile.dev
│   │   └── Dockerfile.prod
│   ├── nginx/
│   │   ├── dev.conf
│   │   └── prod.conf
│   └── scripts/
│       └── mongo-init.js
├── docker-compose.yml
├── docker-compose.prod.yml
├── deploy.sh
├── deploy-remote.sh
├── .env.example
├── .env.prod.example
├── DEPLOY.md
└── (archivos Laravel)
```

---

## 2. Crear proyecto Laravel

```bash
cd /home/user/POCS/family
composer create-project laravel/laravel . "12.*"
```

---

## 3. Instalar dependencias MongoDB

```bash
composer require mongodb/laravel-mongodb
```

**Configurar `config/database.php`:**

```php
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 27017),
    'database' => env('DB_DATABASE', 'family_hub'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'options' => [
        'database' => env('DB_AUTH_DATABASE', 'admin'),
    ],
],
```

---

## 4. Instalar Laravel Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**Configurar `config/sanctum.php`:**

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

---

## 5. Configurar `.env`

**.env.example:**
```env
APP_NAME="Family Hub"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_DOMAIN=localhost

DB_CONNECTION=mongodb
DB_HOST=mongodb
DB_PORT=27017
DB_DATABASE=family_hub
DB_USERNAME=family_admin
DB_PASSWORD=local_password

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@family.local
MAIL_FROM_NAME="${APP_NAME}"

SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost
```

**.env.prod.example:**
```env
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
DB_PASSWORD=CHANGE_ME_IN_PRODUCTION

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=CHANGE_ME
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@family.tudominio.com
MAIL_FROM_NAME="${APP_NAME}"

SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=family.tudominio.com
```

Copiar `.env.example` a `.env`

---

## 6. Configurar Frontend (Vue + Vite)

```bash
npm install
npm install vue@next vue-router@4 axios pinia
npm install -D @vitejs/plugin-vue typescript @types/node
npm install -D tailwindcss postcss autoprefixer
npm install -D vite-plugin-pwa
npx tailwindcss init -p
```

**vite.config.ts:**
```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'prompt',
            injectRegister: false,
            buildBase: '/',
            includeAssets: ['favicon.ico', 'icons/*.png'],
            manifest: {
                name: 'Family Hub',
                short_name: 'Family',
                description: 'Gestión familiar compartida',
                theme_color: '#2196F3',
                background_color: '#FFFFFF',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                navigateFallback: null,
                clientsClaim: true,
                skipWaiting: true,
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
```

**tsconfig.json:**
```json
{
  "compilerOptions": {
    "target": "ESNext",
    "module": "ESNext",
    "moduleResolution": "Node",
    "strict": true,
    "jsx": "preserve",
    "resolveJsonModule": true,
    "esModuleInterop": true,
    "lib": ["ESNext", "DOM"],
    "skipLibCheck": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["resources/js/*"]
    }
  },
  "include": ["resources/js/**/*"],
  "exclude": ["node_modules"]
}
```

**tailwind.config.js:**
```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.ts",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#2196F3',
        success: '#4CAF50',
        warning: '#FF9800',
        error: '#F44336',
      },
    },
  },
  plugins: [],
}
```

---

## 7. Docker Setup

### docker/php/Dockerfile.dev

```dockerfile
FROM php:8.4-fpm

WORKDIR /var/www

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev

# Instalar extensión MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 20.x
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Copiar archivos
COPY . /var/www

# Permisos
RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

### docker/php/Dockerfile.prod

```dockerfile
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-alpine
WORKDIR /var/www

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    openssl-dev \
    autoconf \
    g++ \
    make

RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN docker-php-ext-install pdo_mysql opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www
COPY --from=node_builder /app/public/build /var/www/public/build

RUN composer install --optimize-autoloader --no-dev

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

### docker/nginx/dev.conf

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### docker/nginx/prod.conf

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/public;

    index index.php;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### docker/scripts/mongo-init.js

```javascript
db = db.getSiblingDB('family_hub');

db.createUser({
  user: 'family_admin',
  pwd: 'local_password',
  roles: [
    {
      role: 'readWrite',
      db: 'family_hub'
    }
  ]
});
```

### docker-compose.yml (desarrollo)

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile.dev
    container_name: family_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/php.ini
    networks:
      - family_network
    depends_on:
      - mongodb

  nginx:
    image: nginx:alpine
    container_name: family_nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/dev.conf:/etc/nginx/conf.d/default.conf
    networks:
      - family_network
    depends_on:
      - app

  mongodb:
    image: mongo:8.0
    container_name: family_mongodb
    restart: unless-stopped
    environment:
      MONGO_INITDB_DATABASE: family_hub
      MONGO_INITDB_ROOT_USERNAME: root
      MONGO_INITDB_ROOT_PASSWORD: root
    volumes:
      - mongodb_data:/data/db
      - ./docker/scripts/mongo-init.js:/docker-entrypoint-initdb.d/mongo-init.js:ro
    ports:
      - "27017:27017"
    networks:
      - family_network

  vite:
    image: node:20-alpine
    container_name: family_vite
    working_dir: /var/www
    volumes:
      - ./:/var/www
    ports:
      - "5173:5173"
    command: npm run dev -- --host
    networks:
      - family_network

networks:
  family_network:
    driver: bridge

volumes:
  mongodb_data:
    driver: local
```

### docker-compose.prod.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile.prod
    container_name: family_prod_app
    restart: always
    working_dir: /var/www
    volumes:
      - app_storage:/var/www/storage
      - app_logs:/var/www/storage/logs
    networks:
      - family_prod_network
    depends_on:
      mongodb:
        condition: service_healthy
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_CONNECTION=mongodb
      - DB_HOST=mongodb
      - DB_PORT=27017
      - DB_DATABASE=${DB_DATABASE:-family_hub}
    healthcheck:
      test: ["CMD-SHELL", "php-fpm -t || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3

  nginx:
    image: nginx:alpine
    container_name: family_prod_nginx
    restart: always
    volumes:
      - ./public:/var/www/public:ro
      - ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf:ro
    networks:
      - family_prod_network
      - proxy
    depends_on:
      - app
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.family.rule=Host(`${APP_DOMAIN:-family.tudominio.com}`)"
      - "traefik.http.routers.family.entrypoints=websecure"
      - "traefik.http.routers.family.tls.certresolver=letsencrypt"
      - "traefik.http.services.family.loadbalancer.server.port=80"
      - "traefik.http.middlewares.family-headers.headers.stsSeconds=31536000"
      - "traefik.http.middlewares.family-headers.headers.stsIncludeSubdomains=true"
      - "traefik.http.middlewares.family-headers.headers.stsPreload=true"
      - "traefik.http.routers.family.middlewares=family-headers"
      - "traefik.docker.network=proxy"

  mongodb:
    image: mongo:8.0
    container_name: family_prod_mongodb
    restart: always
    environment:
      MONGO_INITDB_DATABASE: ${DB_DATABASE:-family_hub}
      MONGO_INITDB_ROOT_USERNAME: ${DB_USERNAME:-family_admin}
      MONGO_INITDB_ROOT_PASSWORD: ${DB_PASSWORD:-changeme_in_production}
    volumes:
      - mongodb_prod_data:/data/db
      - ./docker/scripts/mongo-init.js:/docker-entrypoint-initdb.d/mongo-init.js:ro
    networks:
      - family_prod_network
    healthcheck:
      test: echo 'db.runCommand("ping").ok' | mongosh localhost:27017/test --quiet
      interval: 30s
      timeout: 10s
      retries: 3

networks:
  family_prod_network:
    driver: bridge
    name: family_prod_network
  proxy:
    external: true

volumes:
  mongodb_prod_data:
    driver: local
    name: family_prod_mongodb_data
  app_storage:
    driver: local
    name: family_prod_app_storage
  app_logs:
    driver: local
    name: family_prod_app_logs
```

---

## 8. Scripts de deploy

Copiar y adaptar de `whatmovie`:
- `deploy.sh`
- `deploy-remote.sh`
- `DEPLOY.md`

Cambiar referencias de "whatmovie" a "family" en todos los scripts.

---

## 9. Blade principal (SPA)

**resources/views/app.blade.php:**

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
```

**routes/web.php:**

```php
Route::get('/{any}', fn() => view('app'))->where('any', '.*');
```

---

## 10. Crear iconos PWA

Crear iconos en `public/icons/`:

```bash
mkdir -p public/icons
```

**Iconos necesarios:**
- `public/icons/icon-192x192.png` (192x192px)
- `public/icons/icon-512x512.png` (512x512px)
- `public/favicon.ico`

**Diseño sugerido:**
- Fondo: color primary #2196F3
- Logo/texto: "Family" o "FH" en blanco
- Bordes redondeados para maskable

Usar herramientas como:
- https://realfavicongenerator.net/
- Figma/Photoshop para diseño

---

## 11. Inicializar Git

```bash
git init
git add .
git commit -m "chore: initial setup Laravel + Vue + MongoDB + Docker"
```

---

## 12. Verificar que todo funciona

```bash
# Copiar .env
cp .env.example .env

# Levantar contenedores
docker compose up -d

# Entrar al contenedor app
docker exec -it family_app bash

# Generar key
php artisan key:generate

# Instalar dependencias frontend
npm install

# Salir del contenedor
exit

# Ver logs
docker compose logs -f

# Acceder a http://localhost:8080 (debería ver página Laravel)
# Acceder a http://localhost:5173 (Vite dev server)
```

---

## Checklist de completado

- [ ] Laravel 12 instalado
- [ ] MongoDB configurado
- [ ] Sanctum instalado
- [ ] Vue + Vite + TypeScript configurado
- [ ] Tailwind CSS configurado
- [ ] Docker compose (dev y prod) creado
- [ ] Scripts de deploy copiados
- [ ] Git inicializado
- [ ] Proyecto arranca correctamente
- [ ] Blade principal carga Vue app

---

**Próxima tarea:** `02-data-layer.md` (Modelos y estructura de datos)
