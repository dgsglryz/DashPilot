<?php
declare(strict_types=1);

namespace App\Modules\Reports\Models;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report stores monthly rollups used for client-facing PDFs/emails.
 */
class Report extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'site_id',
        'report_month',
        'uptime_percentage',
        'avg_load_time',
        'total_backups',
        'security_scans',
        'incidents_count',
        'pdf_path',
        'generated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'client_id' => 'int',
        'site_id' => 'int',
        'report_month' => 'date',
        'uptime_percentage' => 'decimal:2',
        'avg_load_time' => 'decimal:2',
        'total_backups' => 'int',
        'security_scans' => 'int',
        'incidents_count' => 'int',
        'generated_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
