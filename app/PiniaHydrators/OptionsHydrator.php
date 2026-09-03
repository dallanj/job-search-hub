<?php

namespace App\PiniaHydrators;

use App\Enums\ApplicationStatus;
use App\Enums\TaskPriority;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class OptionsHydrator
{
    /** @return array<int, array{id: int, name: string}> */
    public function companies(Request $request): array
    {
        return $request->user()->companies()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /** @return array<int, array{value: string, label: string}> */
    public function applicationStatuses(): array
    {
        return array_map(
            fn (ApplicationStatus $status): array => [
                'value' => $status->value,
                'label' => Str::headline($status->value),
            ],
            ApplicationStatus::cases(),
        );
    }

    /** @return array<int, array{value: int, label: string}> */
    public function taskPriorities(): array
    {
        return array_map(
            fn (TaskPriority $priority): array => [
                'value' => $priority->value,
                'label' => Str::headline($priority->name),
            ],
            TaskPriority::cases(),
        );
    }
}
