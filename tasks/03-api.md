# Task 03: API (Controllers y Endpoints)

## Objetivo
Implementar todos los controladores y rutas API.

---

## 1. Configurar CORS y Sanctum

**config/cors.php:**
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'supports_credentials' => true,
```

**app/Http/Kernel.php:**
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

---

## 2. Middleware: EnsureFamilyMembership

**app/Http/Middleware/EnsureFamilyMembership.php:**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFamilyMembership
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->hasFamily()) {
            return response()->json(['message' => 'You must belong to a family'], 403);
        }

        return $next($request);
    }
}
```

Registrar en `app/Http/Kernel.php`:
```php
protected $middlewareAliases = [
    // ...
    'has.family' => \App\Http\Middleware\EnsureFamilyMembership::class,
];
```

---

## 3. Auth Controllers

### AuthController

**app/Http/Controllers/Auth/AuthController.php:**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'User registered. Please verify your email.',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('family'));
    }
}
```

### VerificationController

**app/Http/Controllers/Auth/VerificationController.php:**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Models\User;

class VerificationController extends Controller
{
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent']);
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->email))) {
            return redirect(env('FRONTEND_URL') . '/verify-email?status=invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONTEND_URL') . '/verify-email?status=already-verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(env('FRONTEND_URL') . '/verify-email?status=verified');
    }
}
```

### PasswordResetController

**app/Http/Controllers/Auth/PasswordResetController.php:**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent'])
            : response()->json(['message' => 'Unable to send reset link'], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully'])
            : response()->json(['message' => 'Unable to reset password'], 500);
    }
}
```

---

## 4. FamilyController

**app/Http/Controllers/FamilyController.php:**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $code = Family::generateUniqueCode();

        $family = Family::create([
            'name' => $request->name,
            'code' => $code,
            'created_by' => $request->user()->id,
        ]);

        $user = $request->user();
        $user->family_id = $family->id;
        $user->save();

        return response()->json([
            'family' => $family,
            'code' => $code,
        ], 201);
    }

    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:8',
        ]);

        $family = Family::where('code', strtoupper($request->code))->first();

        if (!$family) {
            return response()->json(['message' => 'Invalid family code'], 404);
        }

        $user = $request->user();
        $user->family_id = $family->id;
        $user->save();

        return response()->json([
            'family' => $family,
            'message' => 'Successfully joined family',
        ]);
    }

    public function current(Request $request)
    {
        $family = $request->user()->family;

        if (!$family) {
            return response()->json(['message' => 'Not in a family'], 404);
        }

        return response()->json($family->load('members'));
    }

    public function leave(Request $request)
    {
        $user = $request->user();
        $user->family_id = null;
        $user->save();

        return response()->json(['message' => 'Left family']);
    }
}
```

---

## 5. ShoppingListController

**app/Http/Controllers/ShoppingListController.php:**

```php
<?php

namespace App\Http\Controllers;

use App\Models\{ShoppingItem, ShoppingListItem, TemporarySearchLog};
use Illuminate\Http\Request;

class ShoppingListController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;

        $cart = ShoppingListItem::where('family_id', $familyId)
            ->where('is_in_cart', true)
            ->orderBy('last_used_at', 'desc')
            ->get();

        $frequent = ShoppingListItem::where('family_id', $familyId)
            ->where('is_in_cart', false)
            ->orderBy('usage_count', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'cart' => $cart,
            'frequent' => $frequent,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = ShoppingItem::where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get();

        // Log si no encontró nada
        if ($items->isEmpty()) {
            TemporarySearchLog::create([
                'family_id' => $request->user()->family_id,
                'search_term' => $query,
                'not_found' => true,
            ]);
        }

        return response()->json($items);
    }

    public function add(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:100',
            'item_slug' => 'nullable|string',
            'is_predefined' => 'required|boolean',
            'image_url' => 'nullable|string',
        ]);

        $familyId = $request->user()->family_id;

        // Buscar item existente
        $query = ShoppingListItem::where('family_id', $familyId);

        if ($request->is_predefined && $request->item_slug) {
            $query->where('item_slug', $request->item_slug);
        } else {
            $query->where('item_name', $request->item_name);
        }

        $item = $query->first();

        if ($item) {
            // Actualizar existente
            $item->is_in_cart = true;
            $item->usage_count++;
            $item->last_used_at = now();
            $item->save();
        } else {
            // Crear nuevo
            $item = ShoppingListItem::create([
                'family_id' => $familyId,
                'item_name' => $request->item_name,
                'item_slug' => $request->item_slug,
                'is_predefined' => $request->is_predefined,
                'image_url' => $request->image_url,
                'is_in_cart' => true,
                'usage_count' => 1,
                'last_used_at' => now(),
                'added_by' => $request->user()->id,
            ]);

            // Log temporales
            if (!$request->is_predefined) {
                TemporarySearchLog::create([
                    'family_id' => $familyId,
                    'search_term' => $request->item_name,
                    'not_found' => false,
                ]);
            }
        }

        return response()->json($item, 201);
    }

    public function remove(Request $request, $id)
    {
        $item = ShoppingListItem::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $item->is_in_cart = false;
        $item->save();

        return response()->json($item);
    }
}
```

---

## 6. CalendarController, TaskController, BirthdayController

Implementar siguiendo el mismo patrón:
- Validar `family_id` en todas las queries
- CRUD estándar
- Ver SPEC.md para detalles de cada endpoint

---

## 7. DashboardController

**app/Http/Controllers/DashboardController.php:**

```php
<?php

namespace App\Http\Controllers;

use App\Models\{ShoppingListItem, CalendarEvent, Task, Birthday};
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;

        // 4 items del carrito
        $shopping = ShoppingListItem::where('family_id', $familyId)
            ->where('is_in_cart', true)
            ->orderBy('last_used_at', 'desc')
            ->limit(4)
            ->get();

        // 4 próximos eventos
        $events = CalendarEvent::where('family_id', $familyId)
            ->where('date', '>=', Carbon::today())
            ->orderBy('date', 'asc')
            ->limit(4)
            ->get();

        // 4 últimas tareas pendientes
        $tasks = Task::where('family_id', $familyId)
            ->where('is_completed', false)
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        // 4 próximos cumpleaños
        $birthdays = Birthday::where('family_id', $familyId)->get()
            ->sortBy('next_birthday')
            ->take(4)
            ->values();

        return response()->json([
            'shopping' => $shopping,
            'events' => $events,
            'tasks' => $tasks,
            'birthdays' => $birthdays,
        ]);
    }
}
```

---

## 8. Rutas API

**routes/api.php:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{AuthController, VerificationController, PasswordResetController};
use App\Http\Controllers\{FamilyController, ShoppingListController, CalendarController, TaskController, BirthdayController, DashboardController};

// Auth (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Email verification
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

// Protected routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/email/verification-notification', [VerificationController::class, 'sendVerificationEmail']);

    // Family setup
    Route::post('/family/create', [FamilyController::class, 'create']);
    Route::post('/family/join', [FamilyController::class, 'join']);

    // Family required
    Route::middleware('has.family')->group(function () {
        Route::get('/family/current', [FamilyController::class, 'current']);
        Route::post('/family/leave', [FamilyController::class, 'leave']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Shopping
        Route::get('/shopping', [ShoppingListController::class, 'index']);
        Route::get('/shopping/search', [ShoppingListController::class, 'search']);
        Route::post('/shopping/add', [ShoppingListController::class, 'add']);
        Route::post('/shopping/remove/{id}', [ShoppingListController::class, 'remove']);

        // Calendar
        Route::apiResource('calendar/events', CalendarController::class);

        // Tasks
        Route::apiResource('tasks', TaskController::class);
        Route::put('/tasks/{id}/complete', [TaskController::class, 'complete']);

        // Birthdays
        Route::apiResource('birthdays', BirthdayController::class);
    });
});
```

---

## Checklist

- [ ] Todos los controllers creados
- [ ] Rutas API definidas
- [ ] Middleware EnsureFamilyMembership
- [ ] Validaciones en todos los endpoints
- [ ] Probar con Postman/Insomnia

---

**Próxima tarea:** `04-ui.md` (Frontend Vue)
