<?php

namespace App\Nova;

use Laravel\Nova\Panel;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Rpj\Daterangepicker\DateHelper;
use Outl1ne\NovaTooltipField\Tooltip;
use App\Nova\Actions\EnrichmentAction;
use App\Nova\Actions\ExportXLS;
use App\Nova\Actions\GeojsonDownload;
use App\Nova\Filters\EnrichmentFilter;
use Rpj\Daterangepicker\Daterangepicker;
use Laravel\Nova\Http\Requests\NovaRequest;

class OsmFeaturesResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static $model = \App\Models\AdminArea::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     */
    public static $title;

    /**
     * The columns that should be searched.
     */
    public static $search;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $fields = [
            Text::make('Details')->displayUsing(function () {
                if (!$this->name) {
                    $name = '-';
                } else {
                    $name = wordwrap($this->name, 50, '<br>', true);
                }

                $link = $this->getOsmUrl();

                $osmIdLink
                    = <<<HTML
                        <a style='color:green;' href='{$link}' target='_blank'>
                            <span style='font-weight: bold;'>OSM ID:</span> {$this->osm_id}
                        </a>
                        HTML;

                $osmType
                    = <<<HTML
                            <span>
                                <span style='font-weight: bold;'>OSM Type:</span> {$this->osm_type}
                            </span>
                        HTML;

                return <<<HTML
                    $osmIdLink<br>
                    $osmType<br>
                    <span style='font-weight: bold;'>Name:</span> $name
                    HTML;
            })->asHtml()->hideWhenCreating()->hideWhenUpdating(),

            DateTime::make('Updated_at')
                ->displayUsing(
                    function ($value) {
                        return Carbon::parse($value)->toIso8601String();
                    }
                )->sortable(),
            Tooltip::make('Tags', 'tags')
                ->iconFromPath(public_path('images/pricetags-outline.svg'))
                ->content(
                    collect(json_decode($this->tags, true))->map(function ($value, $key) {
                        return "<span style='font-weight: bold; color: #3b82f6';>{$key}:</span> : {$value}";
                    })->implode('<br>')
                )
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->allowTooltipHTML(),
            Text::make('Wiki', function () {
                return $this->getWikiLinksAsHtml();
            })->asHtml()->hideWhenCreating()->hideWhenUpdating()->textAlign('center'),
            Number::make('Score', 'score')
                ->displayUsing(function ($value) {
                    //return a star rating
                    $stars = '';

                    if ($value == 0 || $value == null) {
                        return 'No rating';
                    }
                    for ($i = 0; $i < $value; $i++) {
                        $stars .= '⭐';
                    }

                    return $stars;
                })->sortable()->filterable(),
        ];

        if ($this->enrichment) {
            $fields[] = Panel::make('Enrichments', $this->enrichmentsFields($request));
        }

        return $fields;
    }

    /**
     * Get the cards available for the request.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new Filters\WikiDataFilter(),
            new Filters\WikiMediaFilter(),
            new Filters\WikiPediaFilter(),
            new Filters\OsmTypeFilter(),
            new Daterangepicker('updated_at', DateHelper::ALL),
            new EnrichmentFilter(),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            (new EnrichmentAction())->canRun(function () {
                return true;
            }),
            (new ExportXLS())->canRun(function () {
                return true;
            }),
            (new GeojsonDownload())->canRun(function () {
                return true;
            }),
        ];
    }

    protected function enrichmentsFields(NovaRequest $request)
    {
        $enrichment = $this->enrichment;
        $data = $enrichment ? json_decode($enrichment->data, true) : null;

        $fields = [];

        if ($data) {
            $fields[] = DateTime::make('Last Update Wikipedia', function () use ($data) {
                return $data['last_update_wikipedia'] ?? '';
            })->onlyOnDetail();

            $fields[] = DateTime::make('Last Update Wikidata', function () use ($data) {
                return $data['last_update_wikidata'] ?? '';
            })->onlyOnDetail();

            $fields[] = Textarea::make('Abstract', function () use ($data) {
                $abstractIt = $data['abstract']['it'] ?? '';
                $abstractEn = $data['abstract']['en'] ?? '';
                return "IT: $abstractIt\n\nEN: $abstractEn";
            })->onlyOnDetail();

            $fields[] = Textarea::make('Description', function () use ($data) {
                $descriptionIt = $data['description']['it'] ?? '';
                $descriptionEn = $data['description']['en'] ?? '';
                return "IT: $descriptionIt\n\nEN: $descriptionEn";
            })->onlyOnDetail();

            $fields[] = Text::make('Images', function () use ($data) {
                $images = $data['images'] ?? null;
                if ($images && !empty($images)) {
                    $thumbnails = [];

                    $this->processImageSource($images['wikipedia_images'] ?? null, $thumbnails);
                    $this->processImageSource($images['wikidata_images'] ?? null, $thumbnails);
                    $this->processImageSource($images['wikimedia_images'] ?? null, $thumbnails);

                    if (empty($thumbnails)) {
                        return '-';
                    }

                    return '<div style="display:flex; flex-wrap:wrap; max-width:520px;">' . implode('', array_filter($thumbnails)) . '</div>';
                } else {
                    return '-';
                }
            })->asHtml()->onlyOnDetail();
        }

        return $fields;
    }

    /**
     * Generates HTML for a single image thumbnail.
     *
     * @param  string  $sourceUrl
     * @param  string  $thumbUrl
     * @return string
     */
    private function generateImageThumbnailHtml(string $sourceUrl, string $thumbUrl): string
    {
        if ($sourceUrl) {
            return "<a href='{$sourceUrl}' target='_blank'><img src='{$thumbUrl}' style='width:50px; height:50px; margin:2px; border-radius:50%;'></a>";
        }
        return '';
    }

    /**
     * Processes a source of images (e.g., Wikipedia, Wikidata) and adds thumbnails to the list.
     *
     * @param  array|null  $imageSourceData
     * @param  array  &$thumbnails
     * @return void
     */
    private function processImageSource(?array $imageSourceData, array &$thumbnails): void
    {
        if (empty($imageSourceData)) {
            return;
        }

        // Check if it's a single image or an array of images
        if (isset($imageSourceData['source_url'])) {
            // Single image
            $sourceUrl = $imageSourceData['source_url'];
            $thumbUrl = $imageSourceData['thumb_url'] ?? $sourceUrl;
            $thumbnails[] = $this->generateImageThumbnailHtml($sourceUrl, $thumbUrl);
        } else {
            // Multiple images
            foreach ($imageSourceData as $image) {
                $sourceUrl = $image['source_url'] ?? '';
                $thumbUrl = $image['thumb_url'] ?? $sourceUrl;
                $thumbnails[] = $this->generateImageThumbnailHtml($sourceUrl, $thumbUrl);
            }
        }
    }
}
