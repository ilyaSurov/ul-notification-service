<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case Transactional = 'transactional';
    case Marketing = 'marketing';

    public function priority(): int
    {
        return match ($this) {
            self::Transactional => 10,
            self::Marketing => 0,
        };
    }
}
