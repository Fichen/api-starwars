<?php

namespace Tests\Feature;

use App\Http\Resources\StarshipResource;
use App\Http\Resources\StarshipResourceCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

class StarshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     *
     *
     * @return void
     */
    public function test_structure_specific_starship_item()
    {
        $response = $this->get('/api/starships/2');

        $response->assertJson(function (AssertableJson $json) {
            $json->hasAll('qty', 'name','model', 'manufacturer', 'cost_in_credits', 'length', 'max_atmosphering_speed', 'crew', 'passengers',
             'cargo_capacity', 'consumables', 'hyperdrive_rating', 'MGLT', 'starship_class', 'pilots', 'films', 'created', 'edited', 'url');
            }
        )->assertStatus(200);

    }

    public function test_starship_list_on_page_2()
    {
        $response = $this->get('/api/starships?page=2');

        $response->assertJsonStructure([
            'count',
            'next',
            'previous',
            'results' => [
                '*' => [
                    'qty',
                    'name',
                    'model',
                    'manufacturer',
                    'cost_in_credits',
                    'length',
                    'max_atmosphering_speed',
                    'crew',
                    'passengers',
                    'cargo_capacity',
                    'consumables',
                    'hyperdrive_rating',
                    'MGLT',
                    'starship_class',
                    'pilots',
                    'films',
                    'created',
                    'edited',
                    'url'
                ]
            ]
        ])->assertStatus(200);

    }

    public function test_transform_url_attribute_on_starship_item()
    {
        $responseAPI = json_decode( file_get_contents( base_path() . '/tests/Feature/inputs/starships_item_id_2.json'));
        $expected = env('BASE_URL') . '/api/starships/2';
        $params = ['id'=> 2, 'qty' => 5];
        $starship = new \App\Models\Starship($params);

        $responseData = new  StarshipResource(
            (object) array_merge($starship->getAttributes(), (array) $responseAPI)
        );

        $this->assertEquals($expected, $responseData->resource->url, 'Cannot transform url from external API');
    }

    public function test_set_starship_quantity()
    {
        $expectedQuantity = 3;
        $response = $this->patchJson(env('BASE_URL'). '/api/starships/2', ['qty' => $expectedQuantity]);

        $response->assertExactJson(['detail' => 'Model id 2 updated successfully']);
        $response->assertStatus(201);
        $this->assertDatabaseHas('starships', [
            'id' => 2,
            'qty' => $expectedQuantity
        ]);
    }

    public function test_increment_starship_quantity()
    {
        $expectedQuantity = 15;
        $actualParams = ['id' => 2, 'qty' => 5];
        $starship = new \App\Models\Starship($actualParams);

        $starship->save();
        $response = $this->patchJson(env('BASE_URL'). '/api/starships/increment/2', ['incrementBy' => 10]);

        $response->assertExactJson(['detail' => 'Model id 2 incremented successfully']);
        $response->assertStatus(201);
        $this->assertDatabaseHas('starships', [
            'id' => 2,
            'qty' => $expectedQuantity
        ]);
    }

    public function test_decrement_starship_quantity()
    {
        $expectedQuantity = 3;
        $actualParams = ['id' => 2, 'qty' => 10];
        $starship = new \App\Models\Starship($actualParams);

        $starship->save();
        $response = $this->patchJson(env('BASE_URL'). '/api/starships/decrement/2', ['decrementBy' => 7]);

        $response->assertExactJson(['detail' => 'Model id 2 decremented successfully']);
        $response->assertStatus(201);
        $this->assertDatabaseHas('starships', [
            'id' => 2,
            'qty' => $expectedQuantity
        ]);
    }
}
