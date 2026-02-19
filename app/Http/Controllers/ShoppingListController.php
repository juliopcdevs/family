<?php

namespace App\Http\Controllers;

use App\Models\ShoppingItem;
use App\Models\ShoppingListItem;
use App\Models\TemporarySearchLog;
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
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = ShoppingItem::where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get();

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

        $query = ShoppingListItem::where('family_id', $familyId);

        if ($request->is_predefined && $request->item_slug) {
            $query->where('item_slug', $request->item_slug);
        } else {
            $query->where('item_name', $request->item_name);
        }

        $item = $query->first();

        if ($item) {
            $item->is_in_cart = true;
            $item->usage_count++;
            $item->last_used_at = now();
            $item->save();
        } else {
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
