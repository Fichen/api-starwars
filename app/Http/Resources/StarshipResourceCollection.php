<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StarshipResourceCollection extends ResourceCollection
{
    public static $wrap = null;
    protected $responseAPI;

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
        $this->collection->transform(function ($starship, $value) {
            foreach ($this->responseAPI['results'] as $result) {
                if ($result['url'] == env('ENDPOINT_STARSHIP') . $starship->resource->getAttributes()['id'] . '/') {
                    $result['url'] =  route('starships.show', ['id' => $starship->resource->getAttributes()['id'] ]);
                    $starshipResource =  new  StarshipResource(
                        (object) array_merge($starship->getAttributes(), $result)
                    );
                    return $starshipResource;
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
            'starships.index',
            ['page' => explode('=', parse_url($url, PHP_URL_QUERY))[1]]
        );
    }
}
