<?php

namespace App\Modules\Projects\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';
}
