<?php
declare(strict_types=1);

namespace App\Modules\Sites\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Site stores operational data for each managed property (WP, Shopify, etc.).
 *
 * Planned relations:
 * - belongsTo Client
 * - hasMany SiteChecks, Alerts, Tasks, Reports
 */
class Site extends Model
{
    //
}
