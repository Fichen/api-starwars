<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            //'id' => $this->id,
            'qty' => $this->qty,
            'name' => $this->name,
            'model' => $this->model,
            'manufacturer' => $this->manufacturer,
            'cost_in_credits' => $this->cost_in_credits,
            'length' => $this->length,
            'max_atmosphering_speed' => $this->max_atmosphering_speed,
            'crew' => $this->crew,
            'passengers' => $this->passengers,
            'cargo_capacity' => $this->cargo_capacity,
            'consumables' => $this->consumables,
            'vehicle_class' => $this->vehicle_class,
            'pilots' => $this->pilots,
            'films' => $this->films,
            'created' => $this->created,
            'edited' => $this->edited,
            'url' => route('vehicles.show', ['id' => $this->id]),
        ];
    }
}
