# Swapi
## Extending data model with proxies
SWAPI provides information about the Star Wars universe. Extend this API to manage
the inventory of starships and vehicles.
### Scope
- consume the information from SWAPI: https://swapi.dev/documentation for starships and vehicles
- extend the data model and implement functionality to keep track of the amount of units for starships and vehicles. This
can be achieved by adding the count property.
- allow to get the total number of units for a specific starship or vehicle
Example: get how many Death Stars are in the inventory of starships
- allow to set the total number of units for a specific starship or vehicle
Example: set the number Death Stars in the inventory of starships
 - allow to increment the the total number of units for a specific starship or vehicle
Example: increment by x units the number Death Stars in the inventory for starships
- allow to decrement the the total number of units for a specific starship or vehicle
Example: decrement by x units the number Death Stars in the inventory for starships
Dillinger is a cloud-enabled, mobile-ready, offline-storage compatible,
AngularJS-powered HTML5 Markdown editor.

## Approach
Was decided to implement a extended data model between the real API and the new one. The main razon for this decision was that this application does not own the vast majority of the data, so knowing that the developement of theses APIs could finishing in complex solution for updating the local data model. What I assumed here is that data could change (I know the probabbly not 😛, but it was a good exercise think about that). I have intended to reproduce the behaviour of the official API.
The amount of units for a specific items is showed as a attached attribute of its with the name "qty".

The endpoints for starships and vehicles are mostly the same, the main visible change are the attributes from the model.

## Server requirements
- Docker
- Docker-compose

## Install
- Clone the repository from [here](https://github.com/Fichen/api-starwars)
- Run
```sh
docker-compose up -d --build
docker exec -ti api-starwars_api_1 bash
composer dump-autoload
php artisan migrate
php artisan migrate:refresh --env=testing --database=sqlite
exit
 ```

> Note The base url to access the app is [http://127.56.0.1:8080](http://127.56.0.1:8080).
>
> The Docker image has all the base code as well. If you wish you can look for it on this [link](https://hub.docker.com/repository/docker/fichtenbaum/laravel-swapi)

## How to run it?
- Show a starship item http://127.56.0.1:8080/api/starships/[ID]. Here you can check for starship quantity (qty attribute). For example on your browser or with Postman with a get request to [http://127.56.0.1:8080/api/starships/2](http://127.56.0.1:8080/api/starships/2)
- Show a not found item. [http://127.56.0.1:8080/api/starships/1](http://127.56.0.1:8080/api/starships/1)
- Show all starships with pagination. For example [http://127.56.0.1:8080/api/starships?page=2](http://127.56.0.1:8080/api/starships?page=2)
- For setting on 7 units for a specific starship, you need to execute a PATCH request to, for example http://127.56.0.1:8080/api/starships/2 with json body {"qty": 7}
- For incrementing on 5 units for a specific starship, you need to execute a PATCH request to, for example http://127.56.0.1:8080/api/starships/increment/2 with json body {"incrementBy": 5}
- For decrementing on 2 units for a specific starship, you need to execute a PATCH request to, for example http://127.56.0.1:8080/api/starships/decrement/2 with json body {"decrementBy": 2}

As I comment before, for vehicles is the same behaviour.You only need to change the url path to http://127.56.0.1:8080/api/vehicles

There are some automated test cases for starship endpoints. If you wish you can run it doing:
```sh
docker exec -t api-starwars_api_1 vendor/bin/phpunit
```
