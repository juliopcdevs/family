# Guia de Despliegue - FAMILY HUB

## Requisitos Previos

- Acceso SSH al servidor (root@<IP_DEL_SERVIDOR>)
- Git configurado con acceso al repositorio (`git@github.com-juliopcdevs:juliopcdevs/family.git`)
- Docker y Docker Compose instalados en el servidor
- **Traefik reverse proxy corriendo** (infra compartida: repo `sibanainfra`, ver seccion Reverse Proxy)

## Estructura del Proyecto

| Archivo | Descripcion |
|---------|-------------|
| `docker-compose.prod.yml` | Docker Compose para produccion con Traefik |
| `.env.prod.example` | Template de variables de entorno |
| `deploy.sh` | Script de despliegue en servidor |
| `deploy-remote.sh` | Script de despliegue remoto |

**Dominio actual:** `familyhub.desarrollodeaplicaciones.es`

## Reverse Proxy (Traefik)

Traefik es **infraestructura compartida por los proyectos del servidor** y **no vive
en este repo**. Su fuente de verdad es el repo
[`sibanainfra`](https://github.com/juliopcdevs/sibanainfra) y corre desde
`/opt/sibanainfra/reverse-proxy`.

Desplegar Family Hub **no toca Traefik** y no requiere hacer nada con él: al levantar
los contenedores con sus labels `traefik.*`, Traefik los detecta solo por el socket
de Docker (red externa `proxy`).

### Verificar que Traefik esta corriendo

```bash
docker ps | grep traefik
docker logs traefik
```

### Si hay que CAMBIAR algo del proxy

No se edita en el servidor ni aquí: se edita en el repo `sibanainfra` y se aplica con
`git pull` + `docker compose up -d`. Ver su README.

## Despliegue Automatizado

### Opcion 1: Desde el servidor

```bash
# Conectar al servidor
ssh root@<IP_DEL_SERVIDOR>

# Ir al directorio del proyecto
cd /opt/family

# Ejecutar el script de despliegue
./deploy.sh <rama>

# Ejemplos:
./deploy.sh main              # Desplegar main en produccion
./deploy.sh develop           # Desplegar develop en produccion
./deploy.sh feature/nueva     # Desplegar una feature
```

### Opcion 2: Desde tu maquina local (con expect)

```bash
expect -c '
set timeout 600
spawn ssh -o StrictHostKeyChecking=no root@<IP_DEL_SERVIDOR> "cd /opt/family && git pull origin <rama> && echo y | ./deploy.sh <rama> 2>&1"
expect "password:"
send "<password>\r"
expect eof
'
```

### Opcion 3: Usando deploy-remote.sh

```bash
./deploy-remote.sh <rama>

# Ejemplos:
./deploy-remote.sh main
./deploy-remote.sh develop
./deploy-remote.sh main --server <IP_DEL_SERVIDOR>
```

> El servidor por defecto se configura en `deploy-remote.sh` (variable
> `DEFAULT_SERVER`). Mientras esté como `CHANGE_ME_SERVER_IP` hay que pasar
> `--server <IP>` en cada invocacion.

## Primer Despliegue (bootstrap del servidor)

La primera vez el directorio `/opt/family` no existe todavia. Pasos:

```bash
# 1. Conectar al servidor
ssh root@<IP_DEL_SERVIDOR>

# 2. Clonar el repositorio
mkdir -p /opt/family
git clone git@github.com-juliopcdevs:juliopcdevs/family.git /opt/family
cd /opt/family

# 3. Crear el .env de produccion a partir del template
cp .env.prod.example .env
nano .env
#   - APP_KEY: dejar vacio (el script lo genera) o generar con:
#       docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
#   - DB_USERNAME / DB_PASSWORD: credenciales de Mongo de produccion
#   - MAIL_*: SMTP real para verificacion de email
#   - APP_DOMAIN ya viene como familyhub.desarrollodeaplicaciones.es

# 4. Asegurar la red proxy (si no existe, el script la crea)
docker network create proxy 2>/dev/null || true

# 5. Lanzar el despliegue
chmod +x deploy.sh
./deploy.sh main
```

A partir de aqui, los despliegues siguientes son simplemente `./deploy.sh <rama>`
(en el servidor) o `./deploy-remote.sh <rama>` (desde local).

## Que hace el script deploy.sh

1. **Validacion**: Verifica que Docker, Docker Compose, Git y la red proxy estan disponibles
2. **Limpieza de contenedores**: Para y elimina los contenedores `family_prod_*`
3. **Limpieza de imagenes**: Elimina las imagenes Docker del proyecto y las dangling
4. **Actualizacion del codigo**: Hace `git fetch` y `checkout` de la rama especificada
5. **Configuracion de entorno**: Crea `.env` desde el template solo si no existe (nunca pisa el `.env` real del servidor)
6. **Build de imagenes**: Reconstruye las imagenes con `--no-cache --pull`
7. **Inicio de contenedores**: Levanta los contenedores con `docker compose up -d`
8. **Copia de .env**: Copia el `.env` al contenedor PHP y genera `APP_KEY` si esta vacio
9. **Copia de assets**: Copia los assets compilados del contenedor al host (para nginx)
10. **Configuracion de Laravel**: Limpia caches, crea indices Mongo (`mongo:indexes`), siembra el catalogo de compra (`ShoppingItemSeeder`), fija permisos y crea storage link

## Opciones del script

```bash
./deploy.sh <rama> [opciones]

Opciones:
  --clean-volumes    Elimina los volumenes de datos (BORRA LA BASE DE DATOS)
  --skip-pull        No hace git pull, usa el codigo actual
  --help             Muestra la ayuda
```

## Despliegue Manual (paso a paso)

Si necesitas hacer el despliegue manualmente:

```bash
# 1. Conectar al servidor
ssh root@<IP_DEL_SERVIDOR>
cd /opt/family

# 2. Actualizar el codigo
git fetch origin
git checkout <rama>
git pull origin <rama>

# 3. Parar y eliminar contenedores existentes
docker compose -f docker-compose.prod.yml -p family-prod down

# 4. Reconstruir imagenes
docker compose -f docker-compose.prod.yml -p family-prod build --no-cache

# 5. Iniciar contenedores
docker compose -f docker-compose.prod.yml -p family-prod up -d

# 6. Copiar .env al contenedor
docker cp .env family_prod_app:/var/www/.env

# 7. Copiar assets al host (nginx los sirve desde el host)
docker cp family_prod_app:/var/www/public/build public/build

# 8. Limpiar caches de Laravel
docker exec family_prod_app php artisan optimize:clear

# 9. Crear indices y seed
docker exec family_prod_app php artisan mongo:indexes
docker exec family_prod_app php artisan db:seed --class=ShoppingItemSeeder --force

# 10. Configurar permisos
docker exec family_prod_app chown -R www-data:www-data storage bootstrap/cache
docker exec family_prod_app chmod -R 775 storage bootstrap/cache
```

## Configuracion del Dominio

1. **DNS**: Apunta `familyhub.desarrollodeaplicaciones.es` al servidor (A record -> IP del servidor)
2. **Editar .env**: `APP_DOMAIN=familyhub.desarrollodeaplicaciones.es` (ya viene en el template)
3. **Reiniciar**: `docker compose -f docker-compose.prod.yml -p family-prod restart`

El certificado SSL se generara automaticamente via Let's Encrypt cuando Traefik detecte el nuevo dominio.

## Troubleshooting

### Los assets CSS/JS no cargan (404)

Los assets se compilan dentro del contenedor PHP pero nginx los sirve desde el host. Asegurate de copiar los assets:

```bash
docker cp family_prod_app:/var/www/public/build public/build
```

### Error "No application encryption key"

El `.env` no se copio al contenedor o no tiene APP_KEY:

```bash
# Verificar que .env tiene APP_KEY
cat .env | grep APP_KEY

# Copiar .env al contenedor
docker cp .env family_prod_app:/var/www/.env

# Generar clave y limpiar cache
docker exec family_prod_app php artisan key:generate --force
docker exec family_prod_app php artisan config:clear
```

### Cambios no se reflejan

OPcache esta habilitado con `validate_timestamps=0`. Reinicia el contenedor:

```bash
docker restart family_prod_app
```

### Los usuarios no pueden iniciar sesion ("verifica tu email")

Family Hub implementa `MustVerifyEmail`. En produccion el usuario debe verificar
su correo mediante el email que envia el SMTP configurado en `.env`. Si necesitas
verificar un usuario manualmente:

```bash
docker exec family_prod_app php artisan tinker --execute='
$u = \App\Models\User::where("email", "correo@dominio.com")->first();
$u->email_verified_at = now(); $u->save();
echo "verificado";'
```

### Ver logs

```bash
# Logs de Laravel
docker exec family_prod_app tail -f storage/logs/laravel.log

# Logs de contenedores
docker compose -f docker-compose.prod.yml -p family-prod logs -f

# Logs de Traefik
docker logs -f traefik
```

### Certificado SSL no se genera

1. Verificar que el dominio apunta al servidor
2. Verificar que Traefik esta corriendo
3. Revisar logs de Traefik: `docker logs traefik`

## Contenedores

| Contenedor | Descripcion |
|------------|-------------|
| `family_prod_app` | PHP-FPM (Laravel) |
| `family_prod_nginx` | Nginx (servidor web) |
| `family_prod_mongodb` | MongoDB (base de datos) |
| `traefik` | Reverse proxy (compartido) |

## Redes Docker

| Red | Uso |
|-----|-----|
| `proxy` | Comunicacion entre Traefik y nginx (externa, compartida) |
| `family_prod_network` | Comunicacion interna del proyecto |

## Notas Importantes

1. **Los volumenes de MongoDB no se eliminan por defecto** para preservar los datos. Usa `--clean-volumes` solo si quieres reiniciar la base de datos.

2. **El build siempre usa `--no-cache --pull`** para asegurar que los cambios se apliquen correctamente.

3. **Los assets se compilan en el Dockerfile** usando multi-stage build (Node -> PHP).

4. **Nginx monta `./public` del host**, por eso hay que copiar los assets despues del build.

5. **Traefik maneja SSL automaticamente** via Let's Encrypt. Nginx solo sirve HTTP internamente.

6. **`deploy.sh` no pisa el `.env` del servidor**: solo lo crea desde el template si no existe. Los secretos de produccion se editan a mano en el servidor.

---

## Instrucciones para Claude AI

### Comando de despliegue

Cuando el usuario pida desplegar, usar **siempre** `expect` con el script `deploy-remote.sh`:

```bash
expect -c '
set timeout 600
spawn ./deploy-remote.sh <rama>
expect {
    "Continue with remote deployment?" {
        send "y\r"
        exp_continue
    }
    "Continue with deployment?" {
        send "y\r"
        exp_continue
    }
    "password:" {
        send "<PASSWORD>\r"
        exp_continue
    }
    "Password:" {
        send "<PASSWORD>\r"
        exp_continue
    }
    timeout {
        puts "Timeout reached"
        exit 1
    }
    eof
}
'
```

### Parametros

| Parametro | Valores | Descripcion |
|-----------|---------|-------------|
| `<rama>` | `develop`, `main`, `feature/xxx` | Rama de git a desplegar |
| `<PASSWORD>` | (proporcionada por usuario) | Contraseña SSH del servidor |

### Configuracion de expect

- **timeout**: 600 segundos (10 minutos) - suficiente para build completo con `--no-cache`
- **prompts manejados**:
  - `"Continue with remote deployment?"` - confirmacion local
  - `"Continue with deployment?"` - confirmacion remota
  - `"password:"` / `"Password:"` - autenticacion SSH (se pide varias veces: scp + ssh)

### URL de acceso

`https://familyhub.desarrollodeaplicaciones.es` (segun `APP_DOMAIN` en `.env` del servidor).

### Verificacion post-despliegue

El script muestra al final:
- `[OK] Deployment completed successfully!` - exito
- Lista de contenedores corriendo
- URL de acceso (si APP_DOMAIN esta configurado)

### Notas importantes

1. **Siempre usar expect** - el script tiene multiples prompts interactivos
2. **La contraseña se usa varias veces** - para scp del script y ssh
3. **No hacer git push antes** si no se ha pedido explicitamente
4. **Verificar la rama actual** con `git branch` antes de desplegar si hay dudas
5. **El servidor por defecto** (`DEFAULT_SERVER` en `deploy-remote.sh`) debe estar configurado, o pasar `--server <IP>`
