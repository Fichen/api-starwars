<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Starship;
use App\Http\Resources\StarshipResource;
use App\Http\Resources\StarshipResourceCollection;
use App\Http\Resources\SwapiResponseError;
use App\Http\Resources\SwapiResponseOk;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StarshipController extends Controller
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

        if (Cache::has('starships_page_' . $page)) {
            $responseData = Cache::get('starships_page_' . $page);
            return response()->json(
                $responseData,
                200
            )
                ->header('Content-Type', 'application/json')
                ->header('Vary', 'Accept')
                ->header('Allow', 'GET, HEAD, OPTIONS');
        }

        $responseAPI = Http::get('https://swapi.dev/api/starships' . $queryString);

        if ($responseAPI->status() == 404) {
            return $this->responseNotFoundError();
        }

        $ids = $this->loadExternalResponseToLocal($responseAPI->json()['results']);
        $responseData =  new StarshipResourceCollection(Starship::findMany($ids), $responseAPI->json());
        Cache::put('starships_page_' . $page, $responseData, now()->addSeconds(env('CACHE_TTL')));

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

        if (Cache::has('starship_item_' . $id)) {
            $responseData = Cache::get('starship_item_' . $id);
            return response()->json(
                $responseData,
                200
            )
                ->header('Content-Type', 'application/json')
                ->header('Vary', 'Accept')
                ->header('Allow', 'GET, HEAD, OPTIONS');
        }

        $responseAPI = $this->getFromExternalStarshipItemByID($id);

        $starship = $this->getStarshipAndCreateIfNotExists($id);

        $responseData = new  StarshipResource(
            (object) array_merge($starship->getAttributes(), $responseAPI->json())
        );
        Cache::put('starship_item_' . $id, $responseData, now()->addSeconds(env('CACHE_TTL')));

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
            $reponseError->additional(["detail: qty must be numeric and greater than 0"]);
            return response()->json($reponseError, 400);
        } elseif (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        $this->getFromExternalStarshipItemByID($id);

        $starship = $this->getStarshipAndCreateIfNotExists($id);

        $starship->qty = $qty;
        if ($starship->saveOrFail()) {
            $responseData = new SwapiResponseOk([]);
            $responseData->additional(['detail: Model id ' . $id . ' updated successfully']);
            return response()->json(
                $responseData,
                200
            );
        }
    }

    public function increment(Request $request, $id)
    {
        $incrementBy = $request->json()->get('incrementBy', null);

        if (is_null($incrementBy) || !is_numeric($incrementBy)) {
            $reponseError = new SwapiResponseError([]);
            $reponseError->additional(["detail: incrementBy must be numeric and greater than 0"]);
            return response()->json($reponseError, 400);
        } elseif (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        //Could happend that not exists into db but it is a valid ID. Check first against external API
        $this->getFromExternalStarshipItemByID($id);

        $starship = $this->getStarshipAndCreateIfNotExists($id);
        $starship->qty += $incrementBy;
        if ($starship->saveOrFail()) {
            $responseData = new SwapiResponseOk([]);
            $responseData->additional(['detail: Model id ' . $id . ' incremented successfully']);
            return response()->json(
                $responseData,
                200
            );
        }
    }

    public function decrement(Request $request, $id)
    {
        $decrementBy = $request->json()->get('decrementBy', null);

        if (is_null($decrementBy) || !is_numeric($decrementBy)) {
            $reponseError = new SwapiResponseError([]);
            $reponseError->additional(["detail: decrementBy must be numeric and greater than 0"]);
            return response()->json($reponseError, 400);
        } elseif (!is_numeric($id)) {
            return $this->responseNotFoundError();
        }

        //Could happend that not exists into db but it is a valid ID. Check first against external API
        $this->getFromExternalStarshipItemByID($id);

        $starship = $this->getStarshipAndCreateIfNotExists($id);
        $starship->qty -= $decrementBy;

        if ($starship->qty < 0) {
            $starship->qty = 0;
        }

        if ($starship->saveOrFail()) {
            $responseData = new SwapiResponseOk([]);
            $responseData->additional(['detail: Model id ' . $id . ' decremented successfully']);
            return response()->json(
                $responseData,
                200
            );
        }
    }

    public function getFromExternalStarshipItemByID($id): \Illuminate\Http\Client\Response
    {
        $responseAPI =  Http::get('https://swapi.dev/api/starships/' . $id);

        if ($responseAPI->status() == 404) {
            $this->responseNotFoundError();
        }

        return $responseAPI;
    }

    public function getStarshipAndCreateIfNotExists($id): \App\Models\Starship
    {
        $starship = Starship::find($id);
        if (is_null($starship)) {
            return Starship::create([
                'id' => $id,
                'qty' => 0
            ]);
        }
        return $starship;
    }

    public function loadExternalResponseToLocal($results)
    {
        $ids = [];
        foreach ($results as $apiStarship) {
            $id = StarshipResource::getIDFromURL($apiStarship['url']);
            $this->getStarshipAndCreateIfNotExists($id);
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
