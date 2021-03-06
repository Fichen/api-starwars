<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SwapiResponseError extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (count($this->additional) > 0) {
            return $this->additional;
        }

        return [
            "detail" => "Not found",
        ];
    }
}
