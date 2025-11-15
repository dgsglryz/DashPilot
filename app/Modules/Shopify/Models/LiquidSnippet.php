<?php
declare(strict_types=1);

namespace App\Modules\Shopify\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LiquidSnippet stores reusable Liquid snippets for the Shopify editor.
 */
class LiquidSnippet extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'category',
        'description',
        'code',
        'is_public',
        'usage_count',
        'user_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'bool',
        'usage_count' => 'int',
    ];

    /**
     * Author that created the snippet.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

