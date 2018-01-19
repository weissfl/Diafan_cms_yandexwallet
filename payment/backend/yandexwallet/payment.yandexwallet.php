<?php
/**
* Обрабатывает перенаправление от сервера Яндекс.Деньги с разрешением пользователя на платеж, проводит платеж.
*
* @package Платежный модуль Яндекс Деньги
* @author Dmitry Petukhov (https://user.diafan.ru/user/weissfl)
* @copyright Copyright (c) 2018 by Dmitry Petukhov
* @license MIT License (https://en.wikipedia.org/wiki/MIT_License)
*/

require_once 'lib/api.php';
require_once 'functions.php';
use \YandexMoney\API;

if (! defined('DIAFAN'))
{
	$path = __FILE__; $i = 0;
	while(! file_exists($path.'/includes/404.php'))
	{
        if($i == 10) {exit;}
        $i++;
		$path = dirname($path);
	}
	include $path.'/includes/404.php';
}
$pay = $this->diafan->_payment->check_pay($_SESSION['payment_id'],'yandexwallet');
if(!empty($_GET['error']))
{
    show_message(($_GET['error']=='access_denied' ? 'Вы отклонили запрос авторизации. ' : '').(!empty($_GET['error_description']) ? $_GET['error_description'] : '').' Можете вернуться на <a href="'.get_cart_link($pay).'">страницу оплаты</a> и попробовать еще раз.</p>');
}
elseif(!empty($_GET['code']))
{
    try
    {
        $protocol = getProtocol();
        //запрос токена        
        $access_token_response = API::getAccessToken($pay[params][client_id], $_GET['code'], "$protocol://.$_SERVER[SERVER_NAME]/payment/get/yandexwallet", $pay[params][secret]);
        if(!empty($access_token_response->error))
        {
            if($access_token_response->error == 'invalid_grant')
            {
                $admin_mail = ($this->diafan->configmodules('emailconfadmin', 'shop') ? $this->diafan->configmodules('email_admin', 'shop') : EMAIL_CONFIG);
                show_message('Неверный либо просроченный временный токен авторизации. Вернитесь на <a href="'.get_cart_link($pay).'">страницу оплаты</a> и повторите платеж. Если ошибка повторяется, сообщите по адресу <a href="mailto:'.$admin_mail.'">'.$admin_mail.'</a>');
                exit;
            }
            throw new Exception($access_token_response->error.'. '.$access_token_response->error_description);
        }
        $access_token = $access_token_response->access_token;        

        //запрос платежа
        $api = new API($access_token);
        if(!empty($pay['params']['fee']))
        {
            $pay['summ'] += round($pay['summ']*$pay['params']['fee']/100,2);
        }
        $request_payment = $api->requestPayment(array(
            'pattern_id' => 'p2p',
            'to' => $pay['params']['receiver'],
            'amount' => $pay['summ'],
            'comment' => $_SERVER['SERVER_NAME'].' заказ №'.$pay['element_id'],
            'message' => $_SERVER['SERVER_NAME'].' заказ №'.$pay['element_id'],
        ));

        if(property_exists($request_payment, 'error'))
        {
            switch($request_payment->error)
            {
                case 'not_enough_funds' :                    
                    show_message('В вашем кошельке недостаточно денег. Пополните счет, вернитесь на <a href="'.get_cart_link($pay).'">страницу оплаты</a> и повторите платеж');
                    exit;
                case 'account_blocked' :
                    $cart_link = get_cart_link($pay);
                    show_message("Ваш счет заблокирован. Для разблокировки счета перейдите по адресу <a target='_blank' href='$request_payment->account_unblock_uri'>$request_payment->account_unblock_uri</a>. После разблокировки вернитесь на <a href='$cart_link'>страницу оплаты</a> и повторите платеж");
                    exit;
                case 'ext_action_required' :
                    $cart_link = get_cart_link($pay);
                    show_message("В настоящее время данный тип платежа не может быть проведен. Для получения возможности проведения таких платежей вам необходимо перейти на страницу по адресу <a target='_blank' href='$request_payment->ext_action_uri'>$request_payment->ext_action_uri</a> и следовать инструкции на данной странице. После выполнения необходимых действий вернитесь на <a href='$cart_link'>страницу оплаты</a> и повторите платеж");
                    exit;
                default:
                    throw new Exception($request_payment->error.'. '.$request_payment->error_description);
            }
        }
        
        //проведение платежа
        do
        {
            $process_payment = $api->processPayment(array(
                "request_id" => $request_payment->request_id,
            ));
            if(!empty($process_payment->next_retry))
            {
                sleep(intval($process_payment->next_retry/1000));
            }
        }
        while($process_payment->status == 'in_progress');
        if($process_payment->status == 'success')
        {
            $this->diafan->_payment->success($pay);
        }
        throw new Exception($process_payment->error.'. '.$process_payment->error_description);
    }
    catch (Exception $e)
    {
        process_exception($e, $this->diafan, $pay);
    }
}