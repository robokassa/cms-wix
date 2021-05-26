<?
	include 'prolog.php';

	$log_fname = 'logs/wix-sale.txt';
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

	$trx = $data['wixTransactionId'];

	if (!in_array($data['order']['description']['currency'], ['RUB', 'KZT'])) {

		$out['status'] = 'DECLINED';
		$out['reasonCode'] = 3003;
		$out['errorMessage'] = 'Currency is not supported';

		http_response_code(400);
	} else {
		$query = 'SELECT * FROM wix_trx WHERE trx=:trx';
		$st = $db->prepare($query);
		$st->execute([':trx' => $trx]);

		if ($row = $st->fetch()) {
			if ($row['status'] != 'New') {
				http_response_code(400);
				$out['errorMessage'] = 'Transaction is not actual';
			} else {
				$inserted_id = $row['id'];
				$plugin_id = $row['plugin_id'];
			}
		} else {
			$query = 'INSERT INTO wix_trx (trx, data) VALUES (:trx, :data)';
			$st = $db->prepare($query);
			$st->execute([':trx' => $trx, ':data' => $input]);

			$inserted_id = $db->lastInsertId();
			$plugin_id = uniqid($inserted_id.'.', true);

			$query = 'UPDATE wix_trx SET plugin_id="'.$plugin_id.'" WHERE id='.$inserted_id;
			$db->query($query);
		}


		$out['redirectUrl'] = 'https://wix.robokassa.ru/payment-form.php?id='.$inserted_id.'&trx='.$trx;
		$out['pluginTransactionId'] = $plugin_id;

		http_response_code(201);
	}

	echo json_encode($out);


