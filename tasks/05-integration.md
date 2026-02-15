# Task 05: Integration

## Objetivo
Conectar frontend y backend, probar flujos completos, configurar emails y hacer testing end-to-end.

---

## 1. Configurar variables de entorno

**.env (backend):**
```env
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost

# Email (desarrollo - usar Mailtrap o log)
MAIL_MAILER=log
```

**.env.local (frontend):**
```env
VITE_API_URL=http://localhost:8080/api
VITE_APP_URL=http://localhost:5173
```

---

## 2. Configurar CORS

**config/cors.php:**
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'supports_credentials' => true,
```

---

## 3. Configurar URLs de email

**config/app.php:**
```php
'url' => env('APP_URL', 'http://localhost'),
'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
```

**app/Providers/AuthServiceProvider.php:**
```php
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

public function boot()
{
    VerifyEmail::toMailUsing(function ($notifiable, $url) {
        $frontendUrl = config('app.frontend_url') . '/verify-email?url=' . urlencode($url);

        return (new MailMessage)
            ->subject('Verifica tu email')
            ->line('Haz clic en el botón para verificar tu email.')
            ->action('Verificar Email', $frontendUrl)
            ->line('Si no creaste una cuenta, ignora este mensaje.');
    });
}
```

---

## 4. Testing de flujos completos

### Flujo 1: Registro y setup familia

1. **Registro**:
   - Ir a `/register`
   - Llenar formulario (nombre, email, password)
   - Submit → mensaje "Verifica tu email"

2. **Verificación**:
   - En logs (si MAIL_MAILER=log): buscar link de verificación
   - Copiar URL, pegar en browser
   - Redirige a `/verify-email?status=verified`
   - Ir a login

3. **Login**:
   - Email + password
   - Redirige a `/family/setup` (no tiene familia)

4. **Crear familia**:
   - Botón "Crear familia"
   - Introduce nombre
   - Genera código (ej: "ABC12345")
   - Muestra botón WhatsApp
   - Redirige a `/dashboard`

### Flujo 2: Unirse a familia

1. **Segundo usuario se registra y verifica**
2. **Login → setup**
3. **Unirse a familia**:
   - Introduce código del primer usuario
   - Submit → redirige a dashboard
4. **Verificar que ambos ven los mismos datos**

### Flujo 3: Lista de compra

1. **Buscar item predefinido**:
   - Escribir "pan" en buscador
   - Ver cards con imagen
   - Click en card → se añade a carrito (arriba)

2. **Buscar item nuevo (temporal)**:
   - Escribir "tofu" (no existe)
   - Ver card con letra "T"
   - Click → se añade a carrito

3. **Quitar del carrito**:
   - Click en item del carrito
   - Desaparece de carrito, va a "más utilizados"

4. **Botón recargar**:
   - Click en recargar
   - Refresh de datos

5. **Segundo usuario**:
   - Entra a lista de compra
   - Ve los mismos items en carrito
   - (Sin tiempo real, debe recargar manualmente)

### Flujo 4: Calendario

1. **Vista mensual**:
   - Ver grid del mes actual
   - Navegar con flechas ←→

2. **Crear evento**:
   - Click en día vacío
   - Modal: título + fecha
   - Submit → aparece evento en el día

3. **Vista anual**:
   - Cambiar a vista anual
   - Ver 12 mini-calendarios
   - Días con eventos tienen indicador

4. **Editar/eliminar**:
   - Click en evento → modal
   - Editar título o eliminar

### Flujo 5: Tareas

1. **Crear tarea**:
   - Input arriba, escribir título
   - Enter o botón "+" → añade tarea
   - Aparece primera en lista (LIFO)

2. **Completar tarea**:
   - Checkbox → se tacha y mueve a "Completadas" (abajo)

3. **Eliminar completada**:
   - Botón eliminar en tarea completada → desaparece

### Flujo 6: Cumpleaños

1. **Añadir cumpleaños**:
   - Botón "+" o similar
   - Form: nombre + fecha nacimiento
   - Submit → aparece en lista ordenada por próximo

2. **Ver cálculos**:
   - Próximo cumpleaños (DD/MM)
   - Edad que cumplirá
   - Días restantes

3. **Editar/eliminar**:
   - Click en card → modal editar
   - Botón eliminar

### Flujo 7: Dashboard

1. **Ver widgets**:
   - 4 items compra
   - 4 próximos eventos
   - 4 tareas pendientes
   - 4 próximos cumpleaños

2. **Click "Ver todo"**:
   - Navega a sección completa

---

## 5. Configurar emails producción

**.env.prod:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@family.tudominio.com
MAIL_FROM_NAME="Family Hub"
```

---

## 6. Testing API con Postman/Insomnia

Crear colección con todos los endpoints:
- Auth (register, login, logout)
- Family (create, join, current)
- Shopping (index, search, add, remove)
- Calendar (CRUD events)
- Tasks (CRUD, complete)
- Birthdays (CRUD)
- Dashboard (index)

---

## 7. Manejo de errores frontend

Implementar toast notifications para:
- Éxito: "Item añadido", "Tarea completada", etc.
- Error: "No se pudo guardar", "Código inválido", etc.
- Info: "Email de verificación enviado", etc.

**Ejemplo con vue-toastification:**
```bash
npm install vue-toastification@next
```

```typescript
// app.ts
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

app.use(Toast);
```

---

## 8. Loading states

Implementar spinners/skeletons en:
- Fetch inicial de datos
- Submit de formularios
- Navegación entre páginas

---

## 9. Validaciones frontend

Agregar validaciones en formularios:
- Email válido
- Password mínimo 8 caracteres
- Campos requeridos
- Confirmación password

---

## 10. Responsive testing

Probar en:
- Mobile (320px, 375px, 414px)
- Tablet (768px, 1024px)
- Desktop (1280px, 1920px)

Verificar:
- Bottom nav funciona en mobile
- Sidebar funciona en desktop
- Cards/grids adaptan correctamente
- Modals centrados y responsive

---

## Checklist

- [ ] Frontend conecta correctamente con backend
- [ ] CORS configurado
- [ ] Emails funcionan (verificación y reset password)
- [ ] Flujo completo registro → familia → dashboard
- [ ] Todos los CRUDs funcionan
- [ ] Lista de compra: búsqueda, añadir, quitar, recargar
- [ ] Calendario: vistas mensual/anual, CRUD eventos
- [ ] Tareas: crear, completar, eliminar
- [ ] Cumpleaños: CRUD, cálculos correctos
- [ ] Dashboard muestra 4 de cada sección
- [ ] WhatsApp share funciona
- [ ] Toast notifications funcionan
- [ ] Loading states implementados
- [ ] Validaciones frontend
- [ ] Responsive en mobile, tablet, desktop
- [ ] Sin errores en consola
- [ ] No hay memory leaks

---

**Próxima tarea:** `06-polish.md` (Pulido, optimización, documentación)
