<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Roberts\LaravelSingledbTenancy\Concerns\TenantAware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\Models\Post;

beforeEach(function () {
    Queue::fake();
});

describe('TenantAware Jobs', function () {
    describe('Job Context Preservation', function () {
        it('restores tenant context in a queued job', function () {
            $tenant = Tenant::factory()->create();
            tenant_context()->set($tenant);

            TestJob::dispatch();

            Queue::assertPushed(TestJob::class, fn ($job) => $job->tenantId === $tenant->id);

            (new TestJob)->handle();

            expect(Post::where('tenant_id', $tenant->id)
                ->where('title', 'Created by a job')
                ->where('content', 'This post was created by a background job.')
                ->exists())->toBeTrue();
        });
    });
});

class TestJob implements ShouldQueue
{
    use TenantAware;

    public function handle()
    {
        Post::create([
            'title' => 'Created by a job',
            'content' => 'This post was created by a background job.',
        ]);
    }
}
