<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\SendNotificationService;
use App\Services\StatusTrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notifications', description: 'Notification delivery API')]
class NotificationController extends Controller
{
    public function __construct(
        private readonly SendNotificationService $sendNotificationService,
        private readonly StatusTrackerService $statusTrackerService,
    ) {
    }

    #[OA\Post(
        path: '/api/v1/notifications/send',
        summary: 'Send mass notifications',
        tags: ['Notifications'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['channel', 'message', 'recipients'],
                properties: [
                    new OA\Property(property: 'channel', type: 'string', enum: ['sms', 'email']),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(
                        property: 'recipients',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                    ),
                    new OA\Property(property: 'type', type: 'string', enum: ['transactional', 'marketing']),
                    new OA\Property(property: 'idempotency_key', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 202, description: 'Notifications accepted'),
            new OA\Response(response: 409, description: 'Duplicate idempotency key'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->sendNotificationService->send(
            channel: \App\Enums\NotificationChannel::from($validated['channel']),
            message: $validated['message'],
            recipients: $validated['recipients'],
            type: $validated['type'] ?? null,
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );

        if (($result['duplicate'] ?? false) === true) {
            unset($result['duplicate']);

            return response()->json([
                'message' => 'Duplicate idempotency key',
                'data' => $result,
            ], 409);
        }

        return response()->json([
            'message' => 'Notifications accepted for delivery',
            'data' => $result,
        ], 202);
    }

    #[OA\Get(
        path: '/api/v1/notifications/history',
        summary: 'Get subscriber notification history',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'subscriber_id',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'History retrieved'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscriber_id' => ['required', 'string', 'max:255'],
        ]);

        $history = $this->statusTrackerService->getHistoryForSubscriber($validated['subscriber_id']);

        return response()->json([
            'data' => NotificationResource::collection($history),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/notifications/{id}/status',
        summary: 'Get notification status',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status retrieved'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function status(string $id): JsonResponse
    {
        $notification = $this->statusTrackerService->getStatus($id);

        if ($notification === null) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'data' => new NotificationResource($notification),
        ]);
    }
}
