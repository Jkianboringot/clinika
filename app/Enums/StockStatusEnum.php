<?php

namespace App\Enums;

enum StockStatusEnum: string
{
    case InStock  = 'in_stock';
    case Low      = 'low';
    case Critical = 'critical';
    case OutStock= 'out_stock';

    /** Critical = stock is at or below this share of the minimum (and still low). */
    public const CRITICAL_RATIO = 0.5;

    public static function fromStock(float $stock, ?float $minimum): self
    {
        if ($minimum === null || $stock >= $minimum) {
            return self::InStock;
        }

        return $stock <= $minimum * self::CRITICAL_RATIO
            ? self::Critical
            : self::Low;
    }

    public function label(): string
    {
        return match ($this) {
            self::InStock  => 'In Stock',
            self::Low      => 'Low Stock',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InStock  => 'green',
            self::Low      => 'yellow',
            self::Critical => 'red',
        };
    }
}