<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/v2', function () use ($router) {
	return redirect('/v2/documentation');;
});

$router->group(['prefix' => 'v2'], function () use ($router) {

	$router->get('collection',  ['uses' => 'CollectionController@showAllCollections']);
	$router->get('collection/{id}', ['uses' => 'CollectionController@showOneCollection']);

	$router->get('occurrence/search',  ['uses' => 'OccurrenceController@showAllOccurrences']);
	//Temporarily keep following route until new documentation is created. The one above will be keep so that I follows GBIF API layout
	$router->get('occurrence',  ['uses' => 'OccurrenceController@showAllOccurrences']);
	$router->get('occurrence/{id}', ['uses' => 'OccurrenceController@showOneOccurrence']);
	$router->get('occurrence/{id}/media', ['uses' => 'OccurrenceController@showOneOccurrenceMedia']);
	$router->get('occurrence/{id}/identification', ['uses' => 'OccurrenceController@showOneOccurrenceIdentifications']);
	$router->get('occurrence/{id}/annotation', ['uses' => 'OccurrenceAnnotationController@showOccurrenceAnnotations']);
	$router->get('occurrence/{id}/reharvest', ['uses' => 'OccurrenceController@oneOccurrenceReharvest']);
	$router->get('occurrence/annotation/search', ['uses' => 'OccurrenceAnnotationController@showAllAnnotations']);

	$router->get('installation',  ['uses' => 'InstallationController@showAllPortals']);
	$router->get('installation/ping', ['uses' => 'InstallationController@pingPortal']);
	$router->get('installation/{id}', ['uses' => 'InstallationController@showOnePortal']);
	$router->get('installation/{id}/touch',  ['uses' => 'InstallationController@portalHandshake']);
	$router->get('installation/{id}/occurrence',  ['uses' => 'InstallationController@showOccurrences']);

	$router->get('inventory',  ['uses' => 'InventoryController@showAllInventories']);
	$router->get('inventory/{id}', ['uses' => 'InventoryController@showOneInventory']);
	$router->get('inventory/{id}/taxa', ['uses' => 'InventoryController@showOneInventoryTaxa']);
	$router->get('inventory/{id}/package', ['uses' => 'InventoryPackageController@oneInventoryDataPackage']);

	$router->get('media',  ['uses' => 'MediaController@showAllMedia']);
	$router->get('media/{id}', ['uses' => 'MediaController@showOneMedia']);
	//$router->get('mediaadd', ['uses' => 'MediaController@insertOccurrenceMedia']);
	//$router->delete('media/{id}', ['uses' => 'MediaController@delete']);
	//$router->put('media/{id}', ['uses' => 'MediaController@update']);

	$router->get('taxonomy',  ['uses' => 'TaxonomyController@showAllTaxa']);
	$router->get('taxonomy/{id}', ['uses' => 'TaxonomyController@showOneTaxon']);
	//$router->get('taxonomy/{id}/description',  ['uses' => 'TaxonomyController@showAllDescriptions']);
	//$router->get('taxonomy/{id}/description/{id}',  ['uses' => 'TaxonomyDescriptionController@showOneDescription']);
});
