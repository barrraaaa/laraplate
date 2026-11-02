<?php

namespace App\Modules\Tenancy\Enums;

/**
 * Baseline organization roles (PRD 5.4). Granular permissions are layered
 * on top of these by the authorization module; the role is the curated
 * source of truth stored on memberships.
 */
enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';
}
