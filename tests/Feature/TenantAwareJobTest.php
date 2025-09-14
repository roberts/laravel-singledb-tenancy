<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Roberts\LaravelSingledbTenancy\Concerns\TenantAware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\Models\Post;
use Roberts\LaravelSingledbTenancy\Tests\TestCase;

class TenantAwareJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    /** @test */
    public function it_restores_tenant_context_in_a_queued_job()
    {
        $tenant = Tenant::factory()->create();

        tenant_context()->set($tenant);

        TestJob::dispatch();

        Queue::assertPushed(TestJob::class, function ($job) use ($tenant) {
            return $job->tenantId === $tenant->id;
        });

        (new TestJob)->handle();

        $this->assertDatabaseHas('posts', [
            'tenant_id' => $tenant->id,
            'title' => 'Created by a job',
        ]);
    }
}

class TestJob implements ShouldQueue
{
    use TenantAware;

    public function handle()
    {
        Post::create(['title' => 'Created by a job']);
    }
}
