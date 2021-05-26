<?
	include 'prolog.php';

Class Wix {

	public $token = NULL;
	private $app_id = NULL;
	private $app_secret = NULL;


	function __construct($app_id = NULL, $app_secret = NULL) {
		if (empty($app_id) || empty($app_secret))
			return false;

		$this->app_id = $app_id;
		$this->app_secret = $app_secret;
	}


	function get_token() {
		$url = 'https://www.wix.com/oauth/access';

		$header = 'Content-Type: application/json';
		$body = ['grant_type' => 'client_credentials', 'scope' => 'CASHIER.GET_ACCESS', 'client_id' => $this->app_id, 'client_secret' => $this->app_secret];
		$opts = ['http' => ['method'	=> 'POST',
							'ignore_errors' => true,
							'header'	=> $header,
							'content'	=> json_encode($body)
							]];
		$ctx = stream_context_create($opts);
		$resp  = file_get_contents($url, false, $ctx);

		$token = json_decode($resp, true)['access_token'];

		if (empty($token))
			return false;

		return $token;
	}


	function trx_update($pars) {
		// PARS: wix_trx, rk_trx, status, [reasonCode]

		global $db;
		$st = $db->prepare('UPDATE wix_trx SET status=:status WHERE trx=:trx');
		$st->execute([':status' => $pars['status'], ':trx' => $pars['wix_trx']]);

		switch($pars['status']) {
			case 'APPROVED':
				$reasonCode = 0;

				break;
			case 'DECLINED':
				$reasonCode = 3000;

				break;
			default:
				$reasonCode = $pars['reasonCode'];
		}

		$url = 'https://cashier-services.wix.com/api/plugin/v1/transactions/'.$pars['wix_trx'];
		$header = ['Content-Type: application/json', 'Authorization: '.$this->get_token()];
		$body = ['status' => $pars['status'], 'pluginTransactionId' => $pars['rk_trx'], 'reasonCode' => $reasonCode];
		$opts = ['http' => ['method'	=> 'PUT',
							'ignore_errors' => true,
							'header'	=> $header,
							'content'	=> json_encode($body)
							]];
		$ctx = stream_context_create($opts);
		$resp  = file_get_contents($url, false, $ctx);

		$tmp = $url."\n".print_r($header, true)."\n".json_encode($body);
		file_put_contents('logs/trx_update', $tmp, FILE_APPEND | LOCK_EX);

		return $resp;
	}

/*
	function get_local() {
		$url = 'https://www.wixapis.com/site-properties/v4/properties?fields.paths=locale&fields.paths=businessName&metaSiteId=55169647-862f-4e21-b962-6cb9049e11d6';
		$header = ['Content-Type: application/json', 'Authorization: '.$this->get_token()];
		$opts = ['http' => ['method'	=> 'GET',
							'ignore_errors' => true,
							'header'	=> $header,
							]];
		$ctx = stream_context_create($opts);
		$resp  = file_get_contents($url, false, $ctx);

		$tmp = $url."\n".print_r($header, true)."\n".json_encode($body);
		file_put_contents('logs/get_local', $tmp, FILE_APPEND | LOCK_EX);

		return $resp;
	}
*/
}