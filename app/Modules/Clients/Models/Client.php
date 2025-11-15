<?php
declare(strict_types=1);

namespace App\Modules\Clients\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Client represents an agency customer with one or more managed sites.
 *
 * Key relationships (to be implemented later):
 * - hasMany Sites
 * - belongsTo assigned developer (User)
 */
class Client extends Model
{
    //
}
