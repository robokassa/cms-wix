<?
	include 'prolog.php';

	$log_fname = 'logs/payment-form.txt';
	include 'grabber.php';

	$query = 'SELECT * FROM wix_trx WHERE id=:id AND trx=:trx LIMIT 1';
	$st = $db->prepare($query);
	$st->execute([':id' => $_GET['id'], ':trx' => $_GET['trx']]);
	$row = $st->fetch();
	if (!$row) {
		die('Error #63');
	}

	$data = json_decode($row['data'], true);

//	$receipt['sno'] = 'usn_income';
//	$receipt['payment_method'] = 'full_payment';
//	$receipt['payment_object'] = 'payment';
//	$receipt['tax'] = 'none';

	foreach($data['order']['description']['items'] as $item) {
		$total += $item['quantity'] * $item['price'];
	}

	if (floatval($data['order']['description']['charges']['shipping']))
		$total += floatval($data['order']['description']['charges']['shipping']);


	if (floatval($data['order']['description']['charges']['discount'])) {
		$discount_koef = ($total - $data['order']['description']['charges']['discount']) / $total;
	} else {
		$discount_koef = 1;
	}

	foreach($data['order']['description']['items'] as $item) {
		$arrReceipt['items'][] = ['name' => $item['name'], 'quantity' => $item['quantity'], 'sum' => $item['quantity'] * ($item['price'] / 100) * $discount_koef, 'tax' => $data['merchantCredentials']['tax']];
	}

	if (floatval($data['order']['description']['charges']['shipping']))
		$arrReceipt['items'][] = ['name' => 'Доставка', 'quantity' => 1, 'sum' => floatval($data['order']['description']['charges']['shipping']) / 100 * $discount_koef, 'tax' => $data['merchantCredentials']['tax']];

//	$arrReceipt['sno'] = 'usn_income';

	$form['receipt'] = urlencode(json_encode($arrReceipt));

	$form['robo_shop'] = $data['merchantCredentials']['shopname'];
	$form['invoice']['total'] = floatval($data['order']['description']['totalAmount'] / 100);
	$form['invoice']['id'] = $row['id'];
	$form['invoice']['description'] = 'Оплата заказа на сайте от '.date('Y-m-d', strtotime($row['ts']));;
	$form['is_test'] = $data['mode'] == 'sandbox' ? 1 : 0;
	$form['shp_id'] = $row['id'];
	$form['shp_trx'] = $row['trx'];

//	$src_sign = $row['robo_shop'].':'.$data['invoice']['total'].':'.$data['invoice']['name'].($row['robo_fisk'] ? ':'.$receipt : '').':'.$pass_1.':shp_id='.$code;
//	$src_sign = $form['robo_shop'].':'.$form['total'].':'.$data['invoice']['name'].($row['robo_fisk'] ? ':'.$receipt : '').':'.$pass_1.':shp_id='.$code;
//	$src_sign = $form['robo_shop'].':'.$form['total'].':'.$form['invoice']['name'].''.':'.$pass_1.':shp_id='.$form['shop_id'];
	$src_sign = $form['robo_shop'].':'.$form['invoice']['total'].':'.$form['invoice']['id'].':'.$form['receipt'].':'.$data['merchantCredentials']['api_key_1'].':shp_id='.$form['shp_id'].':shp_trx='.$form['shp_trx'];
	$form['sign'] = md5($src_sign);

?>
<html lang='ru'>
<head>
	<title>Форма оплаты покупки через Робокасса</title>
</head>
<body onload="document.forms[0].submit()">
	<form action='https://auth.robokassa.ru/Merchant/Index.aspx' method="POST">
		<input type="hidden" name="MerchantLogin" value="<?= $form['robo_shop'] ?>">
		<input type="hidden" name="OutSum" value="<?= $form['invoice']['total'] ?>">
		<input type="hidden" name="InvId" value="<?= $form['invoice']['id'] ?>">
		<input type="hidden" name="Description" value="<?= $form['invoice']['description'] ?>">
		<input type="hidden" name="SignatureValue" value="<?= $form['sign'] ?>">
		<input type="hidden" name="isTest" value="<?= $form['is_test'] ?>">
		<input type="hidden" name="shp_id" value="<?= $form['shp_id'] ?>">
		<input type="hidden" name="shp_trx" value="<?= $form['shp_trx'] ?>">
		<input type="hidden" name="Receipt" value="<?= $form['receipt'] ?>">
<?
	/*
		<? if (!empty($data['email'])): ?>
			<input type="hidden" name="Email" value="<?= $data['email'] ?>">
		<? endif; ?>
	*/
?>
		<input type="submit" value="Оплатить">
	</form>

</body>
</html>