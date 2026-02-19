<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;

        $pending = Task::where('family_id', $familyId)
            ->pending()
            ->get();

        $completed = Task::where('family_id', $familyId)
            ->completed()
            ->get();

        return response()->json([
            'pending' => $pending,
            'completed' => $completed,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $task = Task::create([
            'family_id' => $request->user()->family_id,
            'title' => $request->title,
            'is_completed' => false,
            'completed_at' => null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($task, 201);
    }

    public function complete(Request $request, $id)
    {
        $task = Task::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $task->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        return response()->json($task);
    }

    public function destroy(Request $request, $id)
    {
        $task = Task::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }
}
