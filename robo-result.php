<?
//	Header("Content-Type: application/json");

	include 'prolog.php';

	$log_fname = 'logs/robo-result.txt';
	include 'grabber.php';

	$query = 'SELECT * FROM wix_trx WHERE id=:id AND trx=:trx LIMIT 1';
	$st = $db->prepare($query);
	$st->execute([':id' => $_POST['shp_id'], ':trx' => $_POST['shp_trx']]);
	$row = $st->fetch();
	if (!$row) {
		die('Error #64');
	}

	$data = json_decode($row['data'], true);
	$wix = new Wix($cfg['app']['id'], $cfg['app']['secret']);

	switch($_GET['a']) {
		case 'result':
			$res = $wix->trx_update(['wix_trx' => $row['trx'], 'rk_trx' => $row['plugin_id'], 'status' => 'APPROVED']);

			echo "OK".$_POST['InvId'];

			break;
		case 'success':
			Header('Location: '.$data['order']['returnUrls']['successUrl']);
			$res = $wix->trx_update(['wix_trx' => $row['trx'], 'rk_trx' => $row['plugin_id'], 'status' => 'APPROVED']);

			break;
		case 'fail':
			Header('Location: '.$data['order']['returnUrls']['errorUrl']);
			$res = $wix->trx_update(['wix_trx' => $row['trx'], 'rk_trx' => $row['plugin_id'], 'status' => 'DECLINED']);

			break;

	}

