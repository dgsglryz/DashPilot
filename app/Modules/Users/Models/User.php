<?php
declare(strict_types=1);

namespace App\Modules\Users\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Tasks\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assignedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'assigned_developer_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function resolvedAlerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'resolved_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
