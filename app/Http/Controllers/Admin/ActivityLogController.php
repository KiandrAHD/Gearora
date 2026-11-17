<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('permission', PermissionName::DashboardView->value);

        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->filled('q'), fn ($query) => $query->where('action', 'like', '%'.$request->string('q').'%'))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity.index', ['logs' => $logs]);
    }
}
