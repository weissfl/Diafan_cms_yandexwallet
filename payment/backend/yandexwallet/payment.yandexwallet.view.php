<?php
/**
* Шаблон платежной формы для оплаты на Яндекс.Кошелек
*
* @package Платежный модуль Яндекс Деньги
* @author Dmitry Petukhov (https://user.diafan.ru/user/weissfl)
* @copyright Copyright (c) 2018 by Dmitry Petukhov
* @license MIT License (https://en.wikipedia.org/wiki/MIT_License)
*/
require_once 'functions.php';
if (! defined('DIAFAN'))
{
	$path = __FILE__; $i = 0;
	while(! file_exists($path.'/includes/404.php'))
	{
		if($i == 10) exit; $i++;
		$path = dirname($path);
	}
	include $path.'/includes/404.php';
}

if($result['params']['paymentType']=='PC')
{
echo $result['text'];
$protocol = getProtocol();
?>
<form action="https://money.yandex.ru/oauth/authorize" method="post">
    <input type="hidden" name="client_id" value="<?php echo $result['params']['client_id']?>"/>
    <input type="hidden" name="response_type" value="code"/>
    <input type="hidden" name="redirect_uri" value="<?php echo $protocol, '://', $_SERVER['SERVER_NAME']?>/payment/get/yandexwallet"/>
    <input type="hidden" name="scope" value='payment.to-account("<?php echo $result['params']['receiver']?>").limit(,<?php echo $result['summ']?>)'/>
<?php // $result['element_id'] - номер заказа $result['summ'] - сумма заказа?>
    <input type="submit" name="submit-button" value="Оплатить"/>
</form>
<?php
}
else
{
    echo '<h4>Сейчас необходимо перейти на защищенную страницу ввода платежных данных.</h4><form action="'.$result['process_response']->acs_uri.'" method="post">';
    foreach($result['process_response']->acs_params as $name=>$value)
    {
        echo '<input type="hidden" name="'.$name.'" value="'.$value.'"/>';
    }
    // $result['element_id'] - номер заказа $result['summ'] - сумма заказа
    echo $result['text'];
    echo '<input type="submit" value="Перейти"/></form>';
}