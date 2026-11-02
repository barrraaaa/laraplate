<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued variant of the framework's email verification notification
 * (PRD 5.7: transactional email must not block the request).
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue {}
