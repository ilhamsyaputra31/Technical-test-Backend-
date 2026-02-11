<?php

namespace App\Repositories\Eloquent;

use App\Models\Todo;
use App\Repositories\Contracts\TodoRepositoryInterface;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TodoRepository implements TodoRepositoryInterface
{
    protected $model;

    public function __construct(Todo $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        $key = 'todos.all';
        if (Cache::tags(['todos'])->has($key)) {
            return [
                'data' => Cache::tags(['todos'])->get($key),
                'source' => 'Redis Cache'
            ];
        }

        $data = $this->model->all();
        Cache::tags(['todos'])->put($key, $data, 3600);

        return [
            'data' => $data,
            'source' => 'Database'
        ];
    }

    public function getPaginated($perPage)
    {
        $page = request()->input('page', 1);
        $key = "todos.page.{$page}.perPage.{$perPage}";

        if (Cache::tags(['todos'])->has($key)) {
            return [
                'data' => Cache::tags(['todos'])->get($key),
                'source' => 'Redis Cache'
            ];
        }

        $data = $this->model->paginate($perPage);
        Cache::tags(['todos'])->put($key, $data, 3600);

        return [
            'data' => $data,
            'source' => 'Database'
        ];
    }

    public function getFiltered(array $filters)
    {
        $query = $this->model->newQuery();

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['assignee'])) {
            $assignees = explode(',', $filters['assignee']);
            $query->whereIn('assignee', $assignees);
        }

        if (!empty($filters['status'])) {
            $statuses = explode(',', $filters['status']);
            $query->whereIn('status', $statuses);
        }

        if (!empty($filters['priority'])) {
            $priorities = explode(',', $filters['priority']);
            $query->whereIn('priority', $priorities);
        }

        if (!empty($filters['due_date_start']) && !empty($filters['due_date_end'])) {
            $query->whereBetween('due_date', [$filters['due_date_start'], $filters['due_date_end']]);
        }

        if (isset($filters['time_tracked_min']) && isset($filters['time_tracked_max'])) {
            $query->whereBetween('time_tracked', [$filters['time_tracked_min'], $filters['time_tracked_max']]);
        }

        return $query->get();
    }

    public function getChartData($type)
    {
        switch ($type) {
            case 'status':
                $data = $this->model->select('status', \DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');
                
                return [
                    'status_summary' => [
                        'pending' => $data['pending'] ?? 0,
                        'open' => $data['open'] ?? 0,
                        'in_progress' => $data['in_progress'] ?? 0,
                        'completed' => $data['completed'] ?? 0,
                    ]
                ];

            case 'priority':
                $data = $this->model->select('priority', \DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->pluck('count', 'priority');
                
                return [
                    'priority_summary' => [
                        'low' => $data['low'] ?? 0,
                        'medium' => $data['medium'] ?? 0,
                        'high' => $data['high'] ?? 0,
                    ]
                ];

            case 'assignee':
                $assignees = $this->model->select('assignee')->distinct()->pluck('assignee');
                $summary = [];

                foreach ($assignees as $assignee) {
                    if (!$assignee) continue;

                    $total = $this->model->where('assignee', $assignee)->count();
                    $pending = $this->model->where('assignee', $assignee)->where('status', 'pending')->count();
                    $completedTime = $this->model->where('assignee', $assignee)
                        ->where('status', 'completed')
                        ->sum('time_tracked');

                    $summary[$assignee] = [
                        'total_todos' => $total,
                        'total_pending_todos' => $pending,
                        'total_timetracked_completed_todos' => $completedTime,
                    ];
                }

                return ['assignee_summary' => $summary];

            default:
                return [];
        }
    }

    public function findById($id)
    {
        $key = "todos.id.{$id}";

        if (Cache::tags(['todos'])->has($key)) {
            return [
                'data' => Cache::tags(['todos'])->get($key),
                'source' => 'Redis Cache'
            ];
        }

        $data = $this->model->find($id);
        
        if ($data) {
            Cache::tags(['todos'])->put($key, $data, 3600);
        }

        return [
            'data' => $data,
            'source' => 'Database'
        ];
    }

    public function create(array $data)
    {
        $todo = $this->model->create($data);
        Cache::tags(['todos'])->flush();
        return $todo;
    }

    public function update($id, array $data)
    {
        $record = $this->model->find($id);
        if ($record) {
            $record->update($data);
            Cache::tags(['todos'])->flush();
            return $record;
        }
        return null;
    }

    public function delete($id)
    {
        $deleted = $this->model->destroy($id);
        if ($deleted) {
            Cache::tags(['todos'])->flush();
        }
        return $deleted;
    }
}
