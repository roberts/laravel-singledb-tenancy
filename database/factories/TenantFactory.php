<?php

namespace Roberts\LaravelSingledbTenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, string>
     * @phpstan-ignore-next-line method.childReturnType
     */
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
        return $this->afterCreating(function ($tenant) {
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
