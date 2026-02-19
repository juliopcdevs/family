<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $events = CalendarEvent::where('family_id', $familyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'date' => 'required|date',
        ]);

        $event = CalendarEvent::create([
            'family_id' => $request->user()->family_id,
            'title' => $request->title,
            'date' => Carbon::parse($request->date),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($event, 201);
    }

    public function show(Request $request, $id)
    {
        $event = CalendarEvent::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        return response()->json($event);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'date' => 'required|date',
        ]);

        $event = CalendarEvent::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $event->update([
            'title' => $request->title,
            'date' => Carbon::parse($request->date),
        ]);

        return response()->json($event);
    }

    public function destroy(Request $request, $id)
    {
        $event = CalendarEvent::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $event->delete();

        return response()->json(['message' => 'Event deleted']);
    }
}
