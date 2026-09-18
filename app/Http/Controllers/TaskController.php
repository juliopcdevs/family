<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $familyId = $request->user()->family_id;

        $pending = Task::with('user:name')
            ->where('family_id', $familyId)
            ->pending()
            ->get()
            ->map(fn ($t) => $this->formatTask($t));

        $completed = Task::with('user:name')
            ->where('family_id', $familyId)
            ->completed()
            ->get()
            ->map(fn ($t) => $this->formatTask($t));

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

        $task->load('user:name');

        return response()->json($this->formatTask($task), 201);
    }

    public function complete(Request $request, $id)
    {
        $task = Task::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $task->update([
            'is_completed' => !$task->is_completed,
            'completed_at' => !$task->is_completed ? now() : null,
        ]);

        $task->load('user:name');

        return response()->json($this->formatTask($task));
    }

    public function destroy(Request $request, $id)
    {
        $task = Task::where('family_id', $request->user()->family_id)
            ->findOrFail($id);

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }

    private function formatTask(Task $task): array
    {
        $data = $task->toArray();
        $data['creator_name'] = $task->user?->name ?? 'Desconocido';
        unset($data['user']);

        return $data;
    }
}
