<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelSingledbTenancy\Traits\HasTenant;

/**
 * Test model to demonstrate HasTenant trait usage.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Roberts\LaravelSingledbTenancy\Models\Tenant $tenant
 */
class Post extends Model
{
    use HasFactory;
    use HasTenant;

    protected $fillable = [
        'title',
        'content',
        'tenant_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
    ];
}
