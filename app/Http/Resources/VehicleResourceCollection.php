<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class VehicleResourceCollection extends ResourceCollection
{
    protected $responseAPI;
    public static $wrap = null;

    public function __construct($resource, $responseAPI)
    {
        $this->responseAPI = $responseAPI;
        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this->collection->transform(function ($vehicle) {
            foreach ($this->responseAPI['results'] as $result) {
                if ($result['url'] == env('ENDPOINT_VEHICLE') . $vehicle->resource->getAttributes()['id'] . '/') {
                    $result['url'] = route('vehicles.show', ['id' => $vehicle->resource->getAttributes()['id'] ]);
                    $vehicleResource =  new  VehicleResource(
                        (object) array_merge($vehicle->getAttributes(), $result)
                    );
                    return $vehicleResource;
                }
            }
        });

        return [
            'count' => $this->responseAPI['count'],
            'next' =>  $this->transformUrl($this->responseAPI['next']),
            'previous' => $this->transformUrl($this->responseAPI['previous']),
            'results' => $this->collection,

        ];
    }

    protected function transformUrl($url = null)
    {
        if (is_null($url)) {
            return null;
        }

        return route(
            'vehicles.index',
            ['page' => explode('=', parse_url($url, PHP_URL_QUERY))[1]]
        );
    }
}
