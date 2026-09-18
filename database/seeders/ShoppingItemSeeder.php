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
            // Panadería
            'Pan', 'Pan de molde', 'Tostadas',
            // Lácteos
            'Leche', 'Yogur', 'Queso', 'Mantequilla', 'Nata',
            'Queso rallado', 'Leche sin lactosa', 'Leche de avena',
            // Huevos
            'Huevos',
            // Carnes
            'Pollo', 'Carne', 'Ternera', 'Cerdo', 'Pavo',
            'Jamón', 'Salchichas', 'Chorizo', 'Bacon',
            // Pescado y marisco
            'Atún', 'Sardinas', 'Salmón', 'Gambas', 'Merluza', 'Mejillones',
            // Frutas
            'Manzanas', 'Plátanos', 'Naranjas', 'Limones', 'Fresas',
            'Uvas', 'Sandía', 'Melón', 'Aguacate',
            // Verduras y hortalizas
            'Tomates', 'Lechuga', 'Cebolla', 'Ajo', 'Zanahorias',
            'Pimientos', 'Pepino', 'Patatas', 'Espinacas',
            'Champiñones', 'Calabacín', 'Brócoli', 'Maíz',
            'Judías verdes', 'Aceitunas',
            // Despensa
            'Arroz', 'Pasta', 'Macarrones', 'Espaguetis', 'Fideos',
            'Aceite', 'Sal', 'Azúcar',
            'Pimienta', 'Harina', 'Lentejas', 'Garbanzos',
            'Caldo', 'Vinagre', 'Tomate frito',
            'Miel', 'Mermelada', 'Chocolate', 'Frutos secos',
            'Tortillas de trigo',
            // Salsas
            'Mayonesa', 'Ketchup', 'Mostaza', 'Nutella',
            // Desayuno y snacks
            'Café', 'Té', 'Galletas', 'Cereales', 'Palomitas',
            // Bebidas
            'Agua', 'Zumo', 'Cerveza', 'Vino',
            'Refresco', 'Coca-Cola', 'Tónica',
            // Congelados
            'Pizza congelada', 'Helado', 'Croquetas',
            // Limpieza
            'Detergente', 'Suavizante', 'Lavavajillas',
            'Lejía', 'Estropajo', 'Ambientador',
            'Bolsas basura', 'Servilletas', 'Papel cocina', 'Aluminio',
            // Higiene
            'Papel higiénico', 'Jabón', 'Champú',
            'Pasta de dientes', 'Desodorante', 'Gel de ducha',
            'Crema hidratante',
            // Hogar
            'Pilas',
        ];

        ShoppingItem::truncate();

        foreach ($items as $item) {
            $slug = Str::slug($item);
            ShoppingItem::create([
                'name' => $item,
                'slug' => $slug,
                'image_url' => '/images/shopping/' . $slug . '.svg',
            ]);
        }

        $this->command->info('ShoppingItems seeded: ' . count($items));
    }
}
