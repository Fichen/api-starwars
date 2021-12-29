<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\VehicleResourceCollection;
use App\Http\Resources\SwapiResponseError;
use App\Http\Resources\SwapiResponseOk;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;

class VehicleController extends Controller
{
     /**
     * Display a listing of the resource.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = $request->query('page', null);
        $responseData = null;
        $queryString = !is_null($page) && is_numeric($page) && $page > 0 ? '/?page=' . $page : '';
        $hasError = !is_numeric($page) && !is_null($page) || $page == 0 ? true : false;

        if ($hasError) {
            return $this->responseNotFoundError();
        }

        if (Cache::has('vehicles_page_' . $page)) {
            $responseData = Cache::get('vehicles_page_' . $page);
            return response()->json(
                $responseData,
                200
            )
                ->header('Content-Type', 'application/json')
                ->header('Vary', 'Accept')
                ->header('Allow', 'GET, HEAD, OPTIONS');
        }

        $responseAPI = Http::get('https://swapi.dev/api/vehicles' . $queryString);

        if ($responseAPI->status() == 404) {
            return $this->responseNotFoundError();
        }

        $ids = $this->loadExternalResponseToLocal($responseAPI->json()['results']);
        $responseData =  new VehicleResourceCollection(Vehicle::findMany($ids), $responseAPI->json());
        Cache::put('vehicles_page_' . $page, $responseData, now()->addSeconds(env('CACHE_TTL')));

        return response()->json(
            $responseData,
            200
        )
            ->header('Content-Type', 'application/json')
            ->header('Vary', 'Accept')
            ->header('Allow', 'GET, HEAD, OPTIONS');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        $responseData = null;

        if (Cache::has('vehicle_item_' . $id)) {
            $responseData = Cache::get('vehicle_item_' . $id);
            return response()->json(
                $responseData,
                200
            )
                ->header('Content-Type', 'application/json')
                ->header('Vary', 'Accept')
                ->header('Allow', 'GET, HEAD, OPTIONS');
        }

        $responseAPI = $this->getFromExternalVehicleItemByID($id);
        if ($responseAPI->status() == 404) {
            return $this->responseNotFoundError();
        }

        $vehicle = $this->getVehicleAndCreateIfNotExists($id);

        $responseData = new  VehicleResource(
            (object) array_merge($vehicle->getAttributes(), $responseAPI->json())
        );
        Cache::put('vehicle_item_' . $id, $responseData, now()->addSeconds(env('CACHE_TTL')));

        return response()->json(
            $responseData,
            $responseAPI->status()
        )
            ->header('Content-Type', 'application/json')
            ->header('Vary', 'Accept')
            ->header('Allow', 'GET, HEAD, OPTIONS');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $qty = $request->json()->get('qty', null);

        if (is_null($qty) || !is_numeric($qty)) {
            $reponseError = new SwapiResponseError([]);
            $reponseError->additional(["detail" => "qty must be numeric and greater than 0"]);
            return response()->json($reponseError, 400);
        } elseif (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        $responseAPI = $this->getFromExternalVehicleItemByID($id);
        if ($responseAPI->status() == 404) {
            return $this->responseNotFoundError();
        }

        $vehicle = $this->getVehicleAndCreateIfNotExists($id);

        $vehicle->qty = $qty;
        if ($vehicle->saveOrFail()) {
            $responseData = new SwapiResponseOk([]);
            $responseData->additional(['detail' => 'Model id ' . $id . ' updated successfully']);
            return response()->json(
                $responseData,
                201
            );
        }
    }

    public function increment(Request $request, $id)
    {
        $incrementBy = $request->json()->get('incrementBy', null);

        if (is_null($incrementBy) || !is_numeric($incrementBy)) {
            $reponseError = new SwapiResponseError([]);
            $reponseError->additional(["detail" => "incrementBy must be numeric and greater than 0"]);
            return response()->json($reponseError, 400);
        } elseif (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        //Could happend that not exists into db but it is a valid ID. Check first against external API
        $responseAPI = $this->getFromExternalVehicleItemByID($id);
        if ($responseAPI->status() == 404) {
            return $this->responseNotFoundError();
        }

        $vehicle = $this->getVehicleAndCreateIfNotExists($id);
        $vehicle->qty += $incrementBy;
        if ($vehicle->saveOrFail()) {
            $responseData = new SwapiResponseOk([]);
            $responseData->additional(['detail' => 'Model id ' . $id . ' incremented successfully']);
            return response()->json(
                $responseData,
                201
            );
        }
    }

    public function decrement(Request $request, $id)
    {
        $decrementBy = $request->json()->get('decrementBy', null);

        if (is_null($decrementBy) || !is_numeric($decrementBy)) {
            $reponseError = new SwapiResponseError([]);
            $reponseError->additional(["detail" => "decrementBy must be numeric and greater than 0"]);
            return response()->json($reponseError, 400);
        } elseif (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        //Could happend that not exists into db but it is a valid ID. Check first against external API
        $responseAPI = $this->getFromExternalVehicleItemByID($id);
        if ($responseAPI->status() == 404) {
            return $this->responseNotFoundError();
        }

        $vehicle = $this->getVehicleAndCreateIfNotExists($id);
        $vehicle->qty -= $decrementBy;

        if ($vehicle->qty < 0) {
            $vehicle->qty = 0;
        }

        if ($vehicle->saveOrFail()) {
            $responseData = new SwapiResponseOk([]);
            $responseData->additional(['detail' => 'Model id ' . $id . ' decremented successfully']);
            return response()->json(
                $responseData,
                201
            );
        }
    }

    public function getFromExternalVehicleItemByID($id): \Illuminate\Http\Client\Response
    {
        $responseAPI =  Http::get('https://swapi.dev/api/vehicles/' . $id);

        if ($responseAPI->status() == 404) {
            $this->responseNotFoundError();
        }

        return $responseAPI;
    }

    public function getVehicleAndCreateIfNotExists($id): \App\Models\Vehicle
    {
        $vehicle = Vehicle::find($id);
        if (is_null($vehicle)) {
            return Vehicle::create([
                'id' => $id,
                'qty' => 0
            ]);
        }
        return $vehicle;
    }

    public function loadExternalResponseToLocal($results)
    {
        $ids = [];
        foreach ($results as $apiVehicle) {
            $id = getIDFromURL($apiVehicle['url']);
            $this->getVehicleAndCreateIfNotExists($id);
            $ids[] = $id;
        }
        return $ids;
    }

    protected function responseNotFoundError()
    {
        return response()->json(
            new SwapiResponseError([]),
            404
        )
            ->header('Content-Type', 'application/json')
            ->header('Vary', 'Accept')
            ->header('Allow', 'GET, HEAD, OPTIONS');
    }
}
