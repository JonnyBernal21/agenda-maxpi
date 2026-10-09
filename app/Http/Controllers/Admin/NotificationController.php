<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->notifications->feed($request->user()));
    }

    public function markRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keys' => ['nullable', 'array'],
            'keys.*' => ['string', 'max:80'],
            'all' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('all')) {
            $this->notifications->markAll($request->user());
        } else {
            $this->notifications->markRead($request->user(), $validated['keys'] ?? []);
        }

        return response()->json($this->notifications->feed($request->user()));
    }
}
