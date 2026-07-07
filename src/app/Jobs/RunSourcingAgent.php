<?php

namespace App\Jobs;

use App\Models\SourcingRun;
use App\Services\Sourcing\SourcingAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs one supplier-sourcing pass for a SourcingRun on the queue.
 *
 * SerializesModels stores only the model key, so the worker re-fetches a
 * fresh SourcingRun row at run time. The heavy lifting (and all status
 * bookkeeping) lives in SourcingAgentService::run(); this job is the thin
 * queue wrapper — connection/queue routing, retry policy, and a failure
 * safety net.
 */
class RunSourcingAgent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** One retry is enough for transient external-API hiccups in the evaluation phase. */
    public int $tries = 2;

    /** Must exceed the worst case: two LLM calls + one web search. */
    public int $timeout = 240;

    public function __construct(
        public SourcingRun $run,
        public array $options = [],
    ) {
        $this->onConnection(config('sourcing.queue.connection'));
        $this->onQueue(config('sourcing.queue.queue_name'));
    }

    /**
     * Wait a minute before the single retry.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60];
    }

    public function handle(SourcingAgentService $service): void
    {
        $request = $this->run->sourcingRequest()->firstOrFail();

        $service->run($request, $this->run, $this->options);
    }

    /**
     * Safety net after all retries are exhausted. SourcingAgentService::run()
     * normally marks the row 'failed' itself and rethrows; this only steps in
     * if the row somehow escaped that (e.g. the throwable came from outside
     * run(), such as loading the request). Idempotent — leaves an already
     * failed row untouched.
     */
    public function failed(Throwable $e): void
    {
        $this->run->refresh();

        if ($this->run->status === 'failed') {
            return;
        }

        $this->run->fill([
            'status'      => 'failed',
            'error'       => $e->getMessage(),
            'finished_at' => now(),
        ])->save();
    }
}
