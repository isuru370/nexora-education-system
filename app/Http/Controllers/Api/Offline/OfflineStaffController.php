<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Offline\SyncOfflineRequest;
use App\Services\Api\Offline\OfflineStaffService;
use Illuminate\Http\JsonResponse;

/**
 * Staff app offline mode: today's pack, and the sync of what was recorded
 * without internet (attendance + payments).
 */
class OfflineStaffController extends Controller
{
    public function __construct(
        protected OfflineStaffService $offline
    ) {}

    public function pack(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->offline->pack(),
        ]);
    }

    public function sync(SyncOfflineRequest $request): JsonResponse
    {
        $results = $this->offline->sync($request->validated('device_id'), $request->validated('items'));
        $count = fn (string $status) => collect($results)->where('status', $status)->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'synced' => $count('synced'),
                'duplicate' => $count('duplicate'),
                'conflict' => $count('conflict'),
                'failed' => $count('failed'),
                'retry' => $count('retry'),
            ],
            'results' => $results,
        ]);
    }
}
