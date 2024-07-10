<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\EnrichmentService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class EnrichmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use IsMonitored;



    protected $model;
    protected $onlyMedia;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $model, bool $onlyMedia = false)
    {
        $this->model = $model;

        $this->onlyMedia = $onlyMedia;
    }

    /**
     * Execute the job.
     */
    public function handle(EnrichmentService $enrichmentService): void
    {
        $enrichmentService->enrich($this->model, $this->onlyMedia);
    }
}
