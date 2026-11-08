<?php

namespace App\Repositories;

use App\Models\AdminNotification;
use App\Support\ModelCache;

class NotificationRepository
{
    /** Top-bar data: unseen count + the latest few (cached until a notification changes). */
    public function summary(int $latest = 8): array
    {
        return ModelCache::remember(ModelCache::NOTIFICATIONS, "summary:$latest", fn () => [
            'unseen' => AdminNotification::whereNull('read_at')->count(),
            'latest' => AdminNotification::latest('id')->take($latest)->get(['id', 'unique_id', 'type', 'title', 'body', 'order_id', 'read_at', 'created_at']),
        ]);
    }

    public function paginate(array $filters)
    {
        $key = 'list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList(ModelCache::NOTIFICATIONS, $key, fn () => AdminNotification::query()
            ->when(($filters['show'] ?? '') === 'unseen', fn ($q) => $q->whereNull('read_at'))
            ->when(($filters['show'] ?? '') === 'seen', fn ($q) => $q->whereNotNull('read_at'))
            ->with('order:id,unique_id,order_number,status,total,customer_name')
            ->latest('id')
            ->paginate(config('shop.per_page.admin')));
    }
}
