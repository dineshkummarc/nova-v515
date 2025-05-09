<?php

namespace Laravel\Nova\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Lenses\Lens;
use Laravel\Nova\TrashedStatus;

class LensViewResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        $lens = $this->authorizedLensForRequest($request);

        $query = $request->newSearchQuery();

        if ($request->resourceSoftDeletes()) {
            (TrashedStatus::tryFrom($request->trashed) ?? TrashedStatus::DEFAULT)->applySoftDeleteConstraint($query);
        }

        $paginator = $lens->query($request, $query);

        if ($paginator instanceof Builder) {
            $paginator = $paginator->simplePaginate($request->perPage());
        }

        return [
            'name' => $lens->name(),
            'resources' => $resources = $request->toResources($paginator->getCollection()), // @phpstan-ignore method.notFound
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
            'per_page' => $paginator->perPage(),
            'per_page_options' => $request->resource()::perPageOptions(),
            'softDeletes' => $request->resourceSoftDeletes(),
            'hasId' => $resources->pluck('id')
                        ->reject(function ($field) {
                            return is_null($field->value);
                        })->isNotEmpty(),
            'polling' => $lens::$polling,
            'pollingInterval' => $lens::$pollingInterval * 1000,
            'showPollingToggle' => $lens::$showPollingToggle,
        ];
    }

    /**
     * Get authorized resource for the request.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizedLensForRequest(LensRequest $request): Lens
    {
        return $request->lens();
    }
}
