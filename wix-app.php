<?
	Header("Content-Type: application/json");

	$log_fname = 'logs/wix-app.txt';
	include 'grabber.php';

	$headers = apache_request_headers();

	echo "Adding app to your site...";


//	$pars = parse_url($headers['Referer'], PHP_URL_QUERY);
//	parse_str($pars, $referal);
