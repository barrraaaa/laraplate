<?php

namespace App\Modules\Tenancy\Exceptions;

use RuntimeException;

/**
 * The last owner of an organization can neither leave nor be removed —
 * ownership must be transferred first (PRD 5.3: ownership safeguards).
 */
class LastOwnerRemovalBlockedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The last owner cannot be removed from an organization. Transfer ownership first.'
        );
    }
}
