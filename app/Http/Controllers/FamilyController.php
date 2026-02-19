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
            return response()->json([
                'message' => 'Invalid family code',
            ], 404);
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
            return response()->json([
                'message' => 'Not in a family',
            ], 404);
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
