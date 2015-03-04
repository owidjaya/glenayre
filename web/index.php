<?php
// web/index.php
session_start();
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__.'/../vendor/google/apiclient/src');
require_once __DIR__.'/../vendor/autoload.php';

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
use Google\Spreadsheet\SpreadsheetService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Google\Spreadsheet\UnauthorizedException;

$app = new Silex\Application();
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
// $app->register(new Silex\Provider\SessionServiceProvider());

$app['debug'] = true;
$key_file = __DIR__."/../config/key.json";

$title = 'glenayre_scouts_manure_2015_oscar';
$title = 'test';
$worksheetTitle= 'master';
$columns = [
	["name"=>"Street", "id"=>"street"],
	["name"=>"Number", "id"=>"number"],
	["name"=>"City", "id"=>"city"],
	["name"=>"Contact", "id"=>"contact"],
	["name"=>"Phone", "id"=>"phone"],
	["name"=>"Caller Comments", "id"=>"callercomments"],
	["name"=>"Order Method", "id"=>"ordermethod"],
	["name"=>"Mushroom", "id"=>"mushroom"],
	["name"=>"Steer", "id"=>"steer"],
	["name"=>"Topsoil", "id"=>"topsoil"],
	["name"=>"Payment Comment", "id"=>"paymentcomment"],
	["name"=>"Payment Amount", "id"=>"paymentamount"],
	["name"=>"Pickup or Delivery", "id"=>"pickupordelivery"],
	["name"=>"Pickup or Delivery Comment", "id"=>"pickupordeliverycomment"],
	["name"=>"General Comments", "id"=>"generalcomments"],
	["name"=>"Driver or Status", "id"=>"driverorstatus"],
	["name"=>"Email", "id"=>"email"]
];
$app->get('/', function () use ($columns, $app, $key_file) {
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
			$client = new Google_Client();
			$client->setAuthConfigFile($key_file);
			$client->addScope('https://spreadsheets.google.com/feeds');
			$client->setAccessToken($_SESSION['access_token']);
			return $app['twig']->render("manure_crud.twig", 
				array(
					"columns" => json_encode($columns)
					)
				);
	} else {
		return $app->redirect("/auth");
	}
});
$app->get('/auth', function () use ($app, $key_file) {
	$client = new Google_Client();
	$client->setAuthConfigFile($key_file);
	$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/auth');
	$client->addScope('https://spreadsheets.google.com/feeds');
	if (! isset($_GET['code'])) {
		$auth_url = $client->createAuthUrl();
		return $app->redirect($auth_url);
	} else {
		$client->authenticate($_GET['code']);
		$_SESSION['access_token'] = $client->getAccessToken();
		$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
		return $app->redirect($redirect_uri);
	}
});
$app->get('/sheetData', function() use($app, $title, $worksheetTitle,  $key_file){
	try{
		$token = json_decode($_SESSION['access_token']);
		$serviceRequest = new DefaultServiceRequest($token->access_token);
		ServiceRequestFactory::setInstance($serviceRequest);
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
		$spreadsheet = $spreadsheetFeed->getByTitle($title);
		$worksheetFeed = $spreadsheet->getWorksheets();
		$worksheet = $worksheetFeed->getByTitle($worksheetTitle);
		$cellFeed = $worksheet->getCellFeed();
		$listFeed = $worksheet->getListFeed();

		$listEntries = $listFeed->getEntries();
	}
	catch(UnauthorizedException $e){
		unset($_SESSION['access_token']);
		return $app->redirect("/auth");
	}
	$counter = 0;
	$output = array();

	$header = $listEntries[9]->getValues();
	foreach($header as $key=>$entry){
		if($key == "ignorethisfieldbutdonotremoveit")continue;
		$output[] = array("idx" => $key, "header" => $entry);
		if($entry == "email") break;
	}
	$header = $output;
	$rows = array();
	$output = array();
	for($i = 11; $i < count($listEntries); $i++){
		$vals = $listEntries[$i]->getValues();
		$rows[] = $vals;
	}
	return new JsonResponse(array("rows"=>$rows, "header" => $header));
});
$app->put('/', function(Request $request) use($app, $title, $worksheetTitle){
	try{
		$token = json_decode($_SESSION['access_token']);
		$serviceRequest = new DefaultServiceRequest($token->access_token);
		ServiceRequestFactory::setInstance($serviceRequest);
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
		$spreadsheet = $spreadsheetFeed->getByTitle($title);
		$worksheetFeed = $spreadsheet->getWorksheets();
		$worksheet = $worksheetFeed->getByTitle($worksheetTitle);
	}
	catch(UnauthorizedException $e){
		unset($_SESSION['access_token']);
		$app->redirect("/");
	}

	$cellFeed = $worksheet->getCellFeed();

	$listFeed = $worksheet->getListFeed();
	$listEntries = $listFeed->getEntries();
	$listEntry = $listEntries[$request->get("line_num")+11];
	$toUpdate = array();
	foreach($request->get("line") as $idx => $entry){
		$toUpdate[$idx] = $entry;
	}
	$listEntry->update($toUpdate);
	return new Response("success");
});
$app->post("/", function(Request $request) use($app, $title, $worksheetTitle){
	try{
		$token = json_decode($_SESSION['access_token']);
		$serviceRequest = new DefaultServiceRequest($token->access_token);
		ServiceRequestFactory::setInstance($serviceRequest);
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
		$spreadsheet = $spreadsheetFeed->getByTitle($title);
		$worksheetFeed = $spreadsheet->getWorksheets();
		$worksheet = $worksheetFeed->getByTitle($worksheetTitle);
	}
	catch(UnauthorizedException $e){
		unset($_SESSION['access_token']);
		$app->redirect("/");
	}

	$listFeed = $worksheet->getListFeed();
	$row = $request->get("line");
	$listFeed->insert($row);
	return new Response("success");
});
$app->delete("/", function(Request $request) use($app, $title, $worksheetTitle){
	try{
		$token = json_decode($_SESSION['access_token']);
		$serviceRequest = new DefaultServiceRequest($token->access_token);
		ServiceRequestFactory::setInstance($serviceRequest);
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
		$spreadsheet = $spreadsheetFeed->getByTitle($title);
		$worksheetFeed = $spreadsheet->getWorksheets();
		$worksheet = $worksheetFeed->getByTitle($worksheetTitle);
	}
	catch(UnauthorizedException $e){
		unset($_SESSION['access_token']);
		$app->redirect("/");
	}

	$listFeed = $worksheet->getListFeed();
	$listEntries = $listFeed->getEntries();
	$listEntry = $listEntries[$request->get("line_num")+11];
	$listEntry->delete();
	return new Response("success");

});
$app->run();
