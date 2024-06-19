<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\EnrichmentService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnrichmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $model;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Execute the job.
     */
    public function handle(EnrichmentService $enrichmentService): void
    {
        $enrichmentService->enrich($this->model);
    }
}