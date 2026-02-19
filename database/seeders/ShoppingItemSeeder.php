<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShoppingItem;
use Illuminate\Support\Str;

class ShoppingItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Pan', 'Leche', 'Huevos', 'Tomates', 'Lechuga',
            'Queso', 'Jamón', 'Pollo', 'Arroz', 'Pasta',
            'Aceite', 'Sal', 'Azúcar', 'Café', 'Té',
            'Galletas', 'Cereales', 'Yogur', 'Mantequilla', 'Manzanas',
            'Plátanos', 'Naranjas', 'Patatas', 'Cebolla', 'Ajo',
            'Zanahorias', 'Pimientos', 'Pepino', 'Agua', 'Zumo',
            'Cerveza', 'Vino', 'Atún', 'Sardinas', 'Tomate frito',
            'Mayonesa', 'Ketchup', 'Mostaza', 'Pimienta', 'Papel higiénico',
            'Jabón', 'Champú', 'Detergente', 'Suavizante', 'Lavavajillas',
            'Bolsas basura', 'Servilletas', 'Papel cocina', 'Aluminio', 'Limones',
        ];

        ShoppingItem::truncate();

        foreach ($items as $item) {
            ShoppingItem::create([
                'name' => $item,
                'slug' => Str::slug($item),
                'image_url' => null,
            ]);
        }

        $this->command->info('ShoppingItems seeded: ' . count($items));
    }
}
