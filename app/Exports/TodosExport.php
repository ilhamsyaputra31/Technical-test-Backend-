<?php

namespace App\Exports;

use App\Models\Todo;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class TodosExport implements FromArray, WithHeadings
{
    protected $todos;

    public function __construct(Collection $todos)
    {
        $this->todos = $todos;
    }

    public function headings(): array
    {
        return [
            'Title',
            'Assignee',
            'Due Date',
            'Time Tracked',
            'Status',
            'Priority',
        ];
    }

    public function array(): array
    {
        $data = $this->todos->map(function ($todo) {
            return [
                $todo->title,
                $todo->assignee,
                $todo->due_date,
                $todo->time_tracked,
                $todo->status,
                $todo->priority,
            ];
        });

        // Summary Row
        $data->push([
            'Total: ' . $this->todos->count(),
            '',
            '',
            $this->todos->sum('time_tracked'),
            '',
            '',
        ]);

        return $data->toArray();
    }
}
