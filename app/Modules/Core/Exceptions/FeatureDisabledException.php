<?php

namespace App\Modules\Core\Exceptions;

use RuntimeException;

/**
 * Thrown when application code references a feature module that has been
 * disabled in config/saas.php. This is deliberate: enabling a module
 * back on must be a configuration change, never a silent no-op.
 */
class FeatureDisabledException extends RuntimeException
{
    public function __construct(string $feature)
    {
        parent::__construct(sprintf(
            'The [%s] feature module is disabled. Enable it in config/saas.php -> features before using it.',
            $feature,
        ));
    }
}
