<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Contracts\TodoRepositoryInterface;
use App\Http\Requests\StoreTodoRequest;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TodosExport;

class TodoController extends Controller
{
    protected $todoRepository;

    public function __construct(TodoRepositoryInterface $todoRepository)
    {
        $this->todoRepository = $todoRepository;
    }

    public function export(Request $request)
    {
        $filters = $request->only([
            'title',
            'assignee',
            'status',
            'priority',
            'due_date_start',
            'due_date_end',
            'time_tracked_min',
            'time_tracked_max',
        ]);

        $todos = $this->todoRepository->getFiltered($filters);

        return Excel::download(new TodosExport($todos), 'todos.xlsx');
    }

    public function chart(Request $request)
    {
        $type = $request->query('type');
        
        if (!in_array($type, ['status', 'priority', 'assignee'])) {
             return response()->json([
                'status' => 'error',
                'message' => 'Invalid chart type. Allowed types: status, priority, assignee',
            ], 400);
        }

        $data = $this->todoRepository->getChartData($type);

        return response()->json($data);
    }

    public function idx(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $result = $this->todoRepository->getPaginated($perPage);

        return response()->json([
            'status' => 'success',
            'source' => $result['source'],
            'todos' => $result['data'],
        ]);
    }

    public function store(StoreTodoRequest $request)
    {
        $todo = $this->todoRepository->create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Todo created successfully',
            'todo' => $todo,
        ], 201);
    }

    public function show($id)
    {
        $result = $this->todoRepository->findById($id);
        $todo = $result['data'];

        if (!$todo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Todo not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'source' => $result['source'],
            'todo' => $todo,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validation could be in a separate request class, but for update it might be partial.
        // For simplicity, let's allow partial updates and validate here or create UpdateTodoRequest.
        // Given the requirements, I'll allow everything to be optional for update.
        $data = $request->validate([
            'title' => 'string|max:255',
            'assignee' => 'nullable|string|max:255',
            'due_date' => 'date',
            'time_tracked' => 'numeric|min:0',
            'status' => 'in:pending,open,in_progress,completed',
            'priority' => 'in:low,medium,high',
        ]);

        $todo = $this->todoRepository->update($id, $data);

        if (!$todo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Todo not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Todo updated successfully',
            'todo' => $todo,
        ]);
    }

    public function destroy($id)
    {
        $deleted = $this->todoRepository->delete($id);

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Todo not found or could not be deleted',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Todo deleted successfully',
        ]);
    }
}
