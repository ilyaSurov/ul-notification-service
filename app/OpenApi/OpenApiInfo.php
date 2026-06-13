<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Notification Service API",
 *     description="Microservice for mass SMS and Email notifications"
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Notification Service"
 * )
 */
final class OpenApiInfo
{
}
