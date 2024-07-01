<?php

namespace Rpj\Daterangepicker;

use Exception;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Rpj\Daterangepicker\DateHelper as Helper;

class Daterangepicker extends Filter
{
    private Carbon|null $minDate = null;
    private Carbon|null $maxDate = null;
    private array|null $ranges = null;

    public function __construct(
        private string $column,
        private string $default = Helper::ALL,
    ) {
        $this->setName($this->column ? Str::replace('_', ' ', Str::title($this->column)) . ' range picker' : '');

        $this->setMaxDate(Carbon::today());

        $this->setMinDate(Carbon::parse('1970-01-01'));
    }

    /**
     * Get the key for the filter.
     * 
     * @return string
     */
    public function key(): string
    {
        return $this->column;
    }

    /**
     * Set the filter name.
     * 
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'daterangepicker';

    public $name = 'Last updated Range';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        [$start, $end] = Helper::getParsedDatesGroupedRanges($value);

        if ($start && $end) {
            return $query->whereBetween($this->column, [$start, $end]);
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(NovaRequest $request)
    {
        if (!$this->ranges) {
            $this->setRanges(Helper::defaultRanges());
        }

        return $this->ranges;
    }

    /**
     * Set the default options for the filter.
     *
     * @return array|mixed
     */
    public function default()
    {
        [$start, $end] = Helper::getParsedDatesGroupedRanges($this->default);

        return $start->format('Y-m-d') . ' to ' . now()->format('Y-m-d');
    }

    public function setMinDate(Carbon $minDate): self
    {
        $this->minDate = $minDate;

        if ($this->maxDate && $this->minDate->gt($this->maxDate)) {
            throw new Exception('Date range picker: minDate must be lower or equals than maxDate.');
        }

        return $this;
    }

    public function setMaxDate(Carbon $maxDate): self
    {
        $this->maxDate = $maxDate;

        if ($this->minDate && $this->maxDate->lt($this->minDate)) {
            throw new Exception('Date range picker: maxDate must be greater or equals than minDate.');
        }

        return $this;
    }

    /**
     * @param Carbon[] $periods
     */
    public function setRanges(array $ranges): self
    {
        $result = [];
        $result = collect($ranges)->mapWithKeys(function (array $item, string $key) {
            return [$key => (collect($item)->map(function (Carbon $date) {
                return $date->format('Y-m-d');
            }))];
        })->toArray();

        $this->ranges = $result;

        return $this;
    }

    /**
     * Convert the filter to its JSON representation.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'minDate' => $this->minDate?->format('Y-m-d'),
            'maxDate' => $this->maxDate?->format('Y-m-d'),
        ]);
    }
}