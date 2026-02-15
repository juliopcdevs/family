# Task 02: Data Layer (Modelos y MongoDB)

## Objetivo
Crear todos los modelos MongoDB con sus relaciones, casts, índices y seeders.

---

## 1. Configurar Models base

Todos los models extienden `MongoDB\Laravel\Eloquent\Model`.

---

## 2. Model: User

**app/Models/User.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'family_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function hasFamily(): bool
    {
        return !is_null($this->family_id);
    }
}
```

**Crear índice:**
```php
// En migration o seeder
User::raw()->createIndex(['email' => 1], ['unique' => true]);
```

---

## 3. Model: Family

**app/Models/Family.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Str;

class Family extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'families';

    protected $fillable = [
        'name',
        'code',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function members()
    {
        return $this->hasMany(User::class, 'family_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
```

**Crear índice:**
```php
Family::raw()->createIndex(['code' => 1], ['unique' => true]);
```

---

## 4. Model: ShoppingItem (predefinidos)

**app/Models/ShoppingItem.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShoppingItem extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'shopping_items';

    protected $fillable = [
        'name',
        'slug',
        'image_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

**Crear índice:**
```php
ShoppingItem::raw()->createIndex(['slug' => 1], ['unique' => true]);
ShoppingItem::raw()->createIndex(['name' => 'text']);
```

---

## 5. Model: ShoppingListItem

**app/Models/ShoppingListItem.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'shopping_list_items';

    protected $fillable = [
        'family_id',
        'item_name',
        'item_slug',
        'is_predefined',
        'image_url',
        'is_in_cart',
        'usage_count',
        'last_used_at',
        'added_by',
    ];

    protected $casts = [
        'is_predefined' => 'boolean',
        'is_in_cart' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
```

**Crear índices:**
```php
ShoppingListItem::raw()->createIndex(['family_id' => 1, 'is_in_cart' => 1]);
ShoppingListItem::raw()->createIndex(['family_id' => 1, 'usage_count' => -1, 'last_used_at' => -1]);
ShoppingListItem::raw()->createIndex(['family_id' => 1, 'item_slug' => 1]);
```

---

## 6. Model: CalendarEvent

**app/Models/CalendarEvent.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'calendar_events';

    protected $fillable = [
        'family_id',
        'title',
        'date',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

**Crear índices:**
```php
CalendarEvent::raw()->createIndex(['family_id' => 1, 'date' => 1]);
```

---

## 7. Model: Task

**app/Models/Task.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Task extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'tasks';

    protected $fillable = [
        'family_id',
        'title',
        'is_completed',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false)->orderBy('created_at', 'desc');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true)->orderBy('completed_at', 'desc');
    }
}
```

**Crear índices:**
```php
Task::raw()->createIndex(['family_id' => 1, 'is_completed' => 1, 'created_at' => -1]);
```

---

## 8. Model: Birthday

**app/Models/Birthday.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Birthday extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'birthdays';

    protected $fillable = [
        'family_id',
        'person_name',
        'birth_date',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'next_birthday',
        'age_on_next_birthday',
        'days_until_birthday',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNextBirthdayAttribute(): Carbon
    {
        $today = Carbon::today();
        $birthDate = Carbon::parse($this->birth_date);

        $nextBirthday = Carbon::create(
            $today->year,
            $birthDate->month,
            $birthDate->day
        );

        if ($nextBirthday->lt($today)) {
            $nextBirthday->addYear();
        }

        return $nextBirthday;
    }

    public function getAgeOnNextBirthdayAttribute(): int
    {
        $birthDate = Carbon::parse($this->birth_date);
        $nextBirthday = $this->next_birthday;

        return $nextBirthday->year - $birthDate->year;
    }

    public function getDaysUntilBirthdayAttribute(): int
    {
        return Carbon::today()->diffInDays($this->next_birthday, false);
    }

    public function scopeOrderedByNextBirthday($query)
    {
        // Se ordena en el controlador después de calcular next_birthday
        return $query;
    }
}
```

**Crear índice:**
```php
Birthday::raw()->createIndex(['family_id' => 1]);
```

---

## 9. Model: TemporarySearchLog

**app/Models/TemporarySearchLog.php:**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TemporarySearchLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'temporary_search_logs';

    protected $fillable = [
        'family_id',
        'search_term',
        'not_found',
    ];

    protected $casts = [
        'not_found' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
}
```

**Crear índice con TTL (90 días):**
```php
TemporarySearchLog::raw()->createIndex(['created_at' => 1], ['expireAfterSeconds' => 7776000]);
TemporarySearchLog::raw()->createIndex(['search_term' => 1]);
```

---

## 10. Crear Command para índices

**app/Console/Commands/CreateMongoIndexes.php:**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{User, Family, ShoppingItem, ShoppingListItem, CalendarEvent, Task, Birthday, TemporarySearchLog};

class CreateMongoIndexes extends Command
{
    protected $signature = 'mongo:indexes';
    protected $description = 'Create MongoDB indexes for all collections';

    public function handle()
    {
        $this->info('Creating indexes...');

        // User
        User::raw()->createIndex(['email' => 1], ['unique' => true]);
        $this->info('✓ users indexes created');

        // Family
        Family::raw()->createIndex(['code' => 1], ['unique' => true]);
        $this->info('✓ families indexes created');

        // ShoppingItem
        ShoppingItem::raw()->createIndex(['slug' => 1], ['unique' => true]);
        ShoppingItem::raw()->createIndex(['name' => 'text']);
        $this->info('✓ shopping_items indexes created');

        // ShoppingListItem
        ShoppingListItem::raw()->createIndex(['family_id' => 1, 'is_in_cart' => 1]);
        ShoppingListItem::raw()->createIndex(['family_id' => 1, 'usage_count' => -1, 'last_used_at' => -1]);
        ShoppingListItem::raw()->createIndex(['family_id' => 1, 'item_slug' => 1]);
        $this->info('✓ shopping_list_items indexes created');

        // CalendarEvent
        CalendarEvent::raw()->createIndex(['family_id' => 1, 'date' => 1]);
        $this->info('✓ calendar_events indexes created');

        // Task
        Task::raw()->createIndex(['family_id' => 1, 'is_completed' => 1, 'created_at' => -1]);
        $this->info('✓ tasks indexes created');

        // Birthday
        Birthday::raw()->createIndex(['family_id' => 1]);
        $this->info('✓ birthdays indexes created');

        // TemporarySearchLog
        TemporarySearchLog::raw()->createIndex(['created_at' => 1], ['expireAfterSeconds' => 7776000]);
        TemporarySearchLog::raw()->createIndex(['search_term' => 1]);
        $this->info('✓ temporary_search_logs indexes created');

        $this->info('All indexes created successfully!');
    }
}
```

---

## 11. Seeder: ShoppingItems

**database/seeders/ShoppingItemSeeder.php:**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShoppingItem;
use Illuminate\Support\Str;

class ShoppingItemSeeder extends Seeder
{
    public function run()
    {
        $items = [
            'Pan',
            'Leche',
            'Huevos',
            'Tomates',
            'Lechuga',
            'Queso',
            'Jamón',
            'Pollo',
            'Arroz',
            'Pasta',
            'Aceite',
            'Sal',
            'Azúcar',
            'Café',
            'Té',
            'Galletas',
            'Cereales',
            'Yogur',
            'Mantequilla',
            'Manzanas',
            'Plátanos',
            'Naranjas',
            'Patatas',
            'Cebolla',
            'Ajo',
            'Zanahorias',
            'Pimientos',
            'Pepino',
            'Agua',
            'Zumo',
            'Cerveza',
            'Vino',
            'Atún',
            'Sardinas',
            'Tomate frito',
            'Mayonesa',
            'Ketchup',
            'Mostaza',
            'Sal',
            'Pimienta',
            'Papel higiénico',
            'Jabón',
            'Champú',
            'Detergente',
            'Suavizante',
            'Lavavajillas',
            'Bolsas basura',
            'Servilletas',
            'Papel cocina',
            'Aluminio',
        ];

        foreach ($items as $item) {
            ShoppingItem::create([
                'name' => $item,
                'slug' => Str::slug($item),
                'image_url' => 'https://via.placeholder.com/150?text=' . urlencode($item),
            ]);
        }

        $this->command->info('ShoppingItems seeded: ' . count($items));
    }
}
```

**database/seeders/DatabaseSeeder.php:**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            ShoppingItemSeeder::class,
        ]);
    }
}
```

---

## 12. Ejecutar setup

```bash
# Entrar al contenedor
docker exec -it family_app bash

# Crear índices
php artisan mongo:indexes

# Seed data
php artisan db:seed

# Salir
exit
```

---

## Checklist de completado

- [ ] Todos los modelos creados (User, Family, ShoppingItem, ShoppingListItem, CalendarEvent, Task, Birthday, TemporarySearchLog)
- [ ] Relaciones definidas
- [ ] Casts configurados
- [ ] Command `mongo:indexes` creado
- [ ] Índices creados en MongoDB
- [ ] Seeder de ShoppingItems creado
- [ ] 50 items predefinidos sembrados
- [ ] Verificar con MongoDB Compass o mongosh que las colecciones existen

---

**Próxima tarea:** `03-api.md` (Controllers y endpoints)
