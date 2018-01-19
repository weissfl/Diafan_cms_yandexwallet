<?php
use \YandexMoney\ExternalPayment;
/**
* Формирует данные для формы приема платежей на Яндекс.Кошелек
*
* @package Платежный модуль Яндекс Деньги
* @author Dmitry Petukhov (https://user.diafan.ru/user/weissfl)
* @copyright Copyright (c) 2018 by Dmitry Petukhov
* @license MIT License (https://en.wikipedia.org/wiki/MIT_License)
*/
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

class Payment_yandexwallet_model extends Diafan
{
    public function get($params,$pay)
    {
        if($pay['status'] == 'pay')
        {
            $this->diafan->_payment->success($pay, 'redirect');
        }
        if(!empty($_GET['code']))
        {
            $_SESSION['cart_code'] = $_GET['code'];
        }
        if(!empty($params['fee']))
        {
            $pay['summ'] += round($pay['summ']*$params['fee']/100,2);
        }
        $pay['params'] = $params;
        if($params['paymentType']=='PC')
        {
            $_SESSION['payment_id']=$pay['id'];
            return $pay;
        }
        else
        {
            include_once 'lib/external_payment.php';
            include_once 'functions.php';
            try
            {
                $external_payment = new ExternalPayment($params['instance_id']);
                if(empty($_GET['auth']))
                {
                    $request_response = $external_payment->request(array(
                        'pattern_id'=>'p2p',
                        'to'=>$params['receiver'],
                        'amount'=>$pay['summ'],
                        'message'=>$_SERVER['SERVER_NAME'].' заказ №'.$pay['element_id']
                    ));
                    if($request_response->status == 'success') {
                        $_SESSION['request_id'] = $request_response->request_id;
                    }
                    else {
                        throw new Exception($request_response->error);
                    }
                }

                $cart_link = get_cart_link($pay);
                do
                {
                    $process_response = $external_payment->process(array(
                        'request_id' => $_SESSION['request_id'],
                        'ext_auth_success_uri' => $cart_link.'&auth=success',
                        'ext_auth_fail_uri' => $cart_link.'&auth=fail'
                    ));
                    switch($process_response->status)
                    {
                        case 'success':
                            $this->diafan->_payment->success($pay);
                        case 'ext_auth_required':
                            $pay['process_response'] = $process_response;
                            return $pay;
                        case 'in_progress' :
                            if($process_response->next_retry) {sleep(intval($process_payment->next_retry/1000));} else {sleep(5);}
                            break;
                        case 'refused':
                        default:
                            if(!empty($_GET['auth']) && $_GET['auth']=='fail')
                            {
                                show_message('Банк отказал в авторизации - возможно, вы ввели неверные данные. Попробуйте <a href="'.$cart_link.'">вернуться на страницу оплаты</a> и повторить процедуру.');
                                exit;
                            }
                            else
                            {
                                throw new Exception($process_response->error.'. '.$process_response->error_description);
                            }
                    }
                }
                while($process_response->status == 'in_progress');
            }
            catch(Exception $e)
            {
                process_exception($e, $this->diafan, $pay);
            }
        }
    }
}