<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
use App\Models\CalendarEvent;
use App\Models\ShoppingListItem;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;

        $shopping = ShoppingListItem::where('family_id', $familyId)
            ->where('is_in_cart', true)
            ->orderBy('last_used_at', 'desc')
            ->limit(4)
            ->get();

        $events = CalendarEvent::where('family_id', $familyId)
            ->where('date', '>=', Carbon::today())
            ->orderBy('date', 'asc')
            ->limit(4)
            ->get();

        $tasks = Task::where('family_id', $familyId)
            ->where('is_completed', false)
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        $birthdays = Birthday::where('family_id', $familyId)
            ->get()
            ->sortBy('days_until_birthday')
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
