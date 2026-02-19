<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BirthdayController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;

        $birthdays = Birthday::where('family_id', $familyId)
            ->get()
            ->sortBy('days_until_birthday')
            ->values();

        return response()->json($birthdays);
    }

    public function store(Request $request)
    {
        $request->validate([
            'person_name' => 'required|string|max:100',
            'birth_date' => 'required|date|before_or_equal:today',
        ]);

        $birthday = Birthday::create([
            'family_id' => $request->user()->family_id,
            'person_name' => $request->person_name,
            'birth_date' => Carbon::parse($request->birth_date),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($birthday, 201);
    }

    public function show(Request $request, $id)
    {
        $birthday = Birthday::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        return response()->json($birthday);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'person_name' => 'required|string|max:100',
            'birth_date' => 'required|date|before_or_equal:today',
        ]);

        $birthday = Birthday::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $birthday->update([
            'person_name' => $request->person_name,
            'birth_date' => Carbon::parse($request->birth_date),
        ]);

        return response()->json($birthday);
    }

    public function destroy(Request $request, $id)
    {
        $birthday = Birthday::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $birthday->delete();

        return response()->json(['message' => 'Birthday deleted']);
    }
}
