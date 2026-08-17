<?php

namespace App\Policies;

/**
 * National-only. Note this policy does NOT gate the Filament page — a custom
 * Page has no model and never consults a policy (canAccess() hard-returns
 * true). ManageSiteSettings overrides canAccess() itself. This exists so the
 * model is not policy-less, which Filament would treat as "allow".
 */
class SiteSettingPolicy extends NationalOnlyPolicy {}
