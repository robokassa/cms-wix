<?
	$log_fname = 'logs/wix-paybutton.txt';
	include 'grabber.php';

	include 'prolog.php';
?>
	<form>
		Сумма: <input type='text' size="20"> <button onclick="alert('Ok'); return false;">Оплатить</button>
	</form>