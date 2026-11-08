<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Support\ModelCache;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationRepository $notifications)
    {
    }

    public function index(Request $request)
    {
        $show = in_array($request->query('show'), ['unseen', 'seen'], true) ? $request->query('show') : null;

        return view('admin.notifications.index', [
            'notifications' => $this->notifications->paginate(array_filter(['show' => $show]))->withQueryString(),
            'show' => $show,
            'unseen' => $this->notifications->summary()['unseen'],
        ]);
    }

    /** Polled by the top bar every 30 s: counts + the dropdown list (all from cache). */
    public function summary(OrderRepository $orders)
    {
        $summary = $this->notifications->summary();

        return response()->json([
            'unseen' => $summary['unseen'],
            'pending' => $orders->statusCounts()['pending'] ?? 0,
            'latest_id' => $summary['latest']->first()?->id,
            'latest_title' => $summary['latest']->first()?->title,
            'html' => view('admin.notifications.dropdown-items', ['items' => $summary['latest']])->render(),
        ])->header('Cache-Control', 'no-store');
    }

    /** Opening a notification marks it seen and goes to its order. */
    public function open(string $notification)
    {
        $model = AdminNotification::findByUidOrFail($notification);
        if (! $model->read_at) {
            $model->update(['read_at' => now()]);
        }

        return $model->order && ! $model->order->trashed()
            ? redirect()->route('admin.orders.show', $model->order->unique_id)
            : redirect()->route('admin.notifications.index')->with('error', 'That order is no longer available.');
    }

    /** Seen ⇄ unseen. */
    public function toggle(string $notification)
    {
        $model = AdminNotification::findByUidOrFail($notification);
        $model->update(['read_at' => $model->read_at ? null : now()]);

        return request()->expectsJson()
            ? response()->json(['ok' => true, 'seen' => (bool) $model->read_at, 'unseen' => $this->notifications->summary()['unseen']])
            : back()->with('success', $model->read_at ? 'Marked as seen.' : 'Marked as unseen.');
    }

    public function markAll()
    {
        // One query for all of them, then refresh the cached counts once.
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);
        ModelCache::bump(ModelCache::NOTIFICATIONS);

        return request()->expectsJson()
            ? response()->json(['ok' => true, 'unseen' => 0])
            : back()->with('success', 'All notifications marked as seen.');
    }
}
