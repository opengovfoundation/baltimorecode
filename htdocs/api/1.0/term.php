<?php

header("HTTP/1.0 200 OK");
header('Content-type: application/json');

# Include the PHP declarations that drive this page.
require $_SERVER['DOCUMENT_ROOT'].'/../includes/page-head.inc.php';

$api = new API;
$api->list_all_keys();

# Make sure that the key is the correct (safe) length.
if ( strlen($_GET['key']) != 16 )
{
	json_error('Invalid API key.');
	die();
}

# Localize the key, filtering out unsafe characters.
$key = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);

# If no key has been passed with this query, or if there are no registered API keys.
if ( empty($key) || (count($api->all_keys) == 0) )
{
	json_error('API key not provided. Please register for an API key.');
	die();
}
elseif (!isset($api->all_keys->$key))
{
	json_error('Invalid API key.');
	die();
}

$filter_args = array(
	'term' => FILTER_SANITIZE_STRING,
	'query' => FILTER_SANITIZE_STRING,
	'parent_id' => FILTER_VALIDATE_INT,
	'page' => FILTER_VALIDATE_INT,
	'per_page' => FILTER_VALIDATE_INT
);

$query = filter_input_array(INPUT_GET, $filter_args);

$term = new Term();

$response->terms = $term->get_terms($query);


/*
# If the request contains a specific list of fields to be returned.
if (isset($_GET['fields']))
{
	# Turn that list into an array.
	$returned_fields = explode(',', urldecode($_GET['fields']));
	foreach ($returned_fields as &$field)
	{
		$field = trim($field);
	}

	# It's essential to unset $field at the conclusion of the prior loop.
	unset($field);

	# Step through our response fields and eliminate those that aren't in the requested list.
	foreach($response as $field => &$value)
	{
		if (in_array($field, $returned_fields) === false)
		{
			unset($response->$field);
		}
	}
}
*/

# Include the API version in this response, by pulling it out of the path.
$tmp = explode('/', $_SERVER['SCRIPT_NAME']);
$response->api_version = $tmp[2];

if (isset($callback))
{
	echo $callback.' (';
}
echo json_encode($response);
if (isset($callback))
{
	echo ');';
}
