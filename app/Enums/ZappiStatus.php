<?php

declare(strict_types=1);

namespace App\Enums;

enum ZappiStatus: string
{
    case CHARGING = 'C2';
    case BOOSTING = 'B2';
    case EV_READY = 'B1';
    case WAITING_FOR_EXPORT = 'A';
    case DSR = 'C1';
    case PAUSED = 'F';
    
    /**
     * Check if the status indicates active charging
     */
    public function isCharging(): bool
    {
        return $this === self::CHARGING || $this === self::BOOSTING;
    }
    
    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::CHARGING => 'Charging',
            self::BOOSTING => 'Boosting',
            self::EV_READY => 'EV Ready',
            self::WAITING_FOR_EXPORT => 'Waiting for Export',
            self::DSR => 'DSR',
            self::PAUSED => 'Paused',
        };
    }
    
    /**
     * Try to create from value with fallback
     */
    public static function fromValueOrNull(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }
        
        return self::tryFrom($value);
    }
}

