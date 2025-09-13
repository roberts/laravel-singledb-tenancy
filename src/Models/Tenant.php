<?php

namespace Roberts\LaravelSingledbTenancy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $domain
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tenant) {
            // Auto-generate slug if not provided
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    /**
     * Resolve a tenant by domain.
     */
    public static function resolveByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)->first();
    }

    /**
     * Scope for active (non-deleted) tenants.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if tenant is active (not soft deleted).
     */
    public function isActive(): bool
    {
        return ! $this->trashed();
    }

    /**
     * Suspend the tenant (soft delete).
     */
    public function suspend(): bool
    {
        return $this->delete();
    }

    /**
     * Reactivate the tenant (restore from soft delete).
     */
    public function reactivate(): bool
    {
        return $this->restore();
    }

    /**
     * Generate URL for this tenant.
     */
    public function url(string $path = ''): string
    {
        $protocol = request()->isSecure() ? 'https://' : 'http://';

        return $protocol.$this->domain.($path ? '/'.ltrim($path, '/') : '');
    }

    /**
     * Get the factory for this model.
     */
    protected static function newFactory()
    {
        return \Roberts\LaravelSingledbTenancy\Database\Factories\TenantFactory::new();
    }
}
