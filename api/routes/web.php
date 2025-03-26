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
| Swagger documentation generated using DarkaOnLine
| https://github.com/DarkaOnLine/SwaggerLume
| Run to regenerate docs: php artisan swagger-lume:generate
|
|
*/

$router->get('/', function () use ($router) {
	return $router->app->version();
});

$router->get('/v2', function () use ($router) {
	return redirect('/v2/documentation');
});

$router->group(['prefix' => 'v2'], function () use ($router) {

	$router->get('collection',  ['uses' => 'CollectionController@showAllCollections']);
	$router->get('collection/{id}', ['uses' => 'CollectionController@showOneCollection']);

	$router->get('occurrence',  ['uses' => 'OccurrenceController@showAllOccurrences']);
	$router->get('occurrence/annotation', ['uses' => 'OccurrenceAnnotationController@showAllAnnotations']);
	$router->get('occurrence/{id}', ['uses' => 'OccurrenceController@showOneOccurrence']);
	$router->get('occurrence/{id}/media', ['uses' => 'OccurrenceController@showOneOccurrenceMedia']);
	$router->get('occurrence/{id}/identification', ['uses' => 'OccurrenceController@showOneOccurrenceIdentifications']);
	$router->get('occurrence/{id}/annotation', ['uses' => 'OccurrenceAnnotationController@showOccurrenceAnnotations']);
	$router->get('occurrence/{id}/reharvest', ['uses' => 'OccurrenceController@oneOccurrenceReharvest']);
	$router->post('occurrence/skeletal', ['uses' => 'OccurrenceController@skeletalImport']);

	$router->get('installation',  ['uses' => 'InstallationController@showAllPortals']);
	$router->get('installation/status', ['uses' => 'InstallationController@portalStatus']);
	$router->get('installation/{id}', ['uses' => 'InstallationController@showOnePortal']);
	$router->get('installation/{id}/handshake',  ['uses' => 'InstallationController@portalHandshake']);
	$router->get('installation/{id}/occurrence',  ['uses' => 'InstallationController@showOccurrences']);

	$router->get('inventory',  ['uses' => 'InventoryController@showAllInventories']);
	$router->get('inventory/{id}', ['uses' => 'InventoryController@showOneInventory']);
	$router->get('inventory/{id}/taxa', ['uses' => 'InventoryController@showOneInventoryTaxa']);
	$router->get('inventory/{id}/package', ['uses' => 'InventoryPackageController@oneInventoryDataPackage']);

	$router->get('media',  ['uses' => 'MediaController@showAllMedia']);
	$router->get('media/{id}', ['uses' => 'MediaController@showOneMedia']);
	$router->post('media', ['uses' => 'MediaController@insert']);
	$router->patch('media/{id}', ['uses' => 'MediaController@update']);
	$router->delete('media/{id}', ['uses' => 'MediaController@delete']);

	$router->get('morphology', ['uses' => 'MorphologyController@showAllCharacters']);
	$router->get('morphology/{id}', ['uses' => 'MorphologyController@showOneCharacter']);
	$router->get('morphology/{id}/attribute', ['uses' => 'MorphologyController@showCharacterAttributes']);

	$router->get('taxonomy', ['uses' => 'TaxonomyController@showAllTaxaSearch']);
	$router->get('taxonomy/{id}', ['uses' => 'TaxonomyController@showOneTaxon']);
	//$router->get('taxonomy/{id}/description',  ['uses' => 'TaxonomyController@showAllDescriptions']);
	//$router->get('taxonomy/{id}/description/{id}',  ['uses' => 'TaxonomyDescriptionController@showOneDescription']);

	$router->get('exsiccata', ['uses' => 'ExsiccataController@showAllExsiccata']);
	$router->get('exsiccata/{identifier}', ['uses' => 'ExsiccataController@showExsiccata']);
	$router->get('exsiccata/{identifier}/number', ['uses' => 'ExsiccataController@showOneExsiccataNumbers']);
	$router->get('exsiccata/{identifier}/number/{numberIdentifier}', ['uses' => 'ExsiccataController@showOneExsiccataNumbersIdentifier']);
	$router->get('exsiccati/{identifier}/number/{numberIdentifier}/occurrence', ['uses' => 'ExsiccataController@showOccurrencesByExsiccataNumber']);
});
