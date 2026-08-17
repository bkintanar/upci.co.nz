<?php

namespace App\Policies;

/**
 * Without this class Filament falls back to Response::allow() for an
 * unpolicied model (see vendor/filament/filament/src/helpers.php), which let
 * any local user reach AnnouncementsRelationManager via /admin/departments/{id}
 * and author content rendered through v-html on the public site.
 */
class DepartmentAnnouncementPolicy extends NationalOnlyPolicy {}
