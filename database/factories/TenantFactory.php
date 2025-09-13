<?php

namespace Roberts\LaravelSingledbTenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'domain' => $this->faker->unique()->domainWord().'.test',
        ];
    }

    /**
     * Create a suspended (soft deleted) tenant.
     */
    public function suspended(): static
    {
        return $this->afterCreating(function (Tenant $tenant) {
            $tenant->delete();
        });
    }

    /**
     * Create a tenant with a specific domain.
     */
    public function withDomain(string $domain): static
    {
        return $this->state([
            'domain' => $domain,
        ]);
    }

    /**
     * Create a tenant with a specific slug.
     */
    public function withSlug(string $slug): static
    {
        return $this->state([
            'slug' => $slug,
        ]);
    }
}
