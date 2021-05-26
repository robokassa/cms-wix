<?
	include 'prolog.php';

	$log_fname = 'logs/wix-onboard.txt';
	include 'grabber.php';

	$jwt_token = str_replace('JWT=', '', apache_request_headers()['Digest']);
	$input = file_get_contents('php://input');

	try{
		$jwtObj = JWT::decode($jwt_token, $cfg['app']['public_key'], ['RS256']);

		if ($jwtObj->data->SHA256 != hash('sha256', $input))
			throw new Exception('JWT check error');

	} catch(Exception $e) {
		http_response_code(400);

		die('Missing request signature');
	}

	Header("Content-Type: application/json");

	$data = json_decode($input, true);

	$out['credentials'] = $data['credentials'];
	$out['mode'] = $data['mode'];
	$out['accountId'] = $data['credentials']['shopname'];
	$out['accountName'] = $data['credentials']['shopname'];

	echo json_encode($out);


