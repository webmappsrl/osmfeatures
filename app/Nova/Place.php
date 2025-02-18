<?php

namespace App\Nova;

use Laravel\Nova\Panel;
use Wm\MapPoint\MapPoint;
use Laravel\Nova\Fields\Text;
use App\Nova\OsmFeaturesResource;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use App\Nova\Filters\ElevationFilter;
use App\Nova\Filters\EnrichmentFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class Place extends OsmFeaturesResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Place>
     */
    public static $model = \App\Models\Place::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'osm_id';

    public static function newModel()
    {
        $model = parent::newModel();
        $model->setKeyName('id');

        return $model;
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
        'class',
        'subclass',
        'osm_id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $osmfeaturesFields = parent::fields($request);

        $specificFields = [
            Text::make('Class')
                ->sortable(),
            Text::make('Subclass')
                ->sortable(),
            Text::make('Elevation')->sortable()->displayUsing(
                function ($value) {
                    if ($value) {
                        return $value . ' m';
                    } else {
                        return ' ';
                    }
                }
            ),
            MapPoint::make('geom')->withMeta([
                'center' => [42, 10],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'minZoom' => 8,
                'maxZoom' => 17,
                'defaultZoom' => 14,
            ])->onlyOnDetail(),
        ];

        return array_merge($osmfeaturesFields, $specificFields);
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
        $osmfeaturesFilters = parent::filters($request);

        $specificFilters = [
            new Filters\ClassFilter(),
            new Filters\SubclassFilter(),
            ElevationFilter::make()
                ->dividerLabel('<>')
                ->inputType('number')
                ->placeholder('From', 'To')
                ->fromAttributes(['min' => 0])
                ->toAttributes(['max' => 10000]),
        ];

        return array_merge($osmfeaturesFilters, $specificFilters);
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


    protected function enrichmentsFields(NovaRequest $request)
    {
        $enrichment = $this->enrichment;
        if ($enrichment) {
            $data = json_decode($enrichment->data, true);
        }

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
                $imageKeys = ['wikimedia_images', 'wikidata_images', 'wikipedia_images'];
                $thumbnails = [];

                foreach ($imageKeys as $key) {
                    if (isset($data['images'][$key]) && is_array($data['images'][$key])) {
                        foreach ($data['images'][$key] as $image) {
                            $sourceUrl = $image['source_url'] ?? '';
                            $thumbUrl = $image['thumb_url'] ?? $image['source_url'] ?? '';
                            $thumbnails[] = "<a href=\"{$sourceUrl}\" target=\"_blank\"><img src=\"{$thumbUrl}\" style=\"width:50px; height:50px; margin:2px; border-radius:50%;\"></a>";
                        }
                    }
                }
                if (!empty($thumbnails)) {
                    return '<div style="display:flex; flex-wrap:wrap; max-width:520px;">' . implode('', $thumbnails) . '</div>';
                } else {
                    return '-';
                }
            })->asHtml()->onlyOnDetail();
        }

        return $fields;
    }
}
