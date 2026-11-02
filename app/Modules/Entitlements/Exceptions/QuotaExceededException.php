<?php

namespace App\Modules\Entitlements\Exceptions;

use RuntimeException;

/**
 * Thrown when a write would push usage past a plan quota (PRD 5.6:
 * limit enforcement). Rendered as validation feedback, never a 500.
 *
 * @property string $metric
 */
class QuotaExceededException extends RuntimeException
{
    public function __construct(public string $metric)
    {
        parent::__construct("This action would exceed your plan's {$metric} limit.");
    }
}
