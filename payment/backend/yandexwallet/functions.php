<?php
/**
* Общие функции для оплаты на Яндекс.Кошелек
*
* @package Платежный модуль Яндекс Деньги
* @author Dmitry Petukhov (https://user.diafan.ru/user/weissfl)
* @copyright Copyright (c) 2018 by Dmitry Petukhov
* @license MIT License (https://en.wikipedia.org/wiki/MIT_License)
*/
function getProtocol() {
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
        return 'https';
    }
    else {
        return 'http';
    }
}

function show_message($message)
{
    echo '<div style="margin:50px auto; background-color: #E0FFFF; width:580px; font-size:20px; text-align:center; padding:50px;"><p style="color:#E74C3C">'.$message.'</p></div>';
}

function get_cart_link($pay)
{
    $protocol = getProtocol();
    return "$protocol://$_SERVER[SERVER_NAME]/".DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='site' AND trash='0' AND element_type='element' AND element_id IN (SELECT id FROM {site} WHERE module_name='%s' AND [act]='1' AND trash='0')", $pay['module_name']).'/step2/show'.$pay['element_id'].'/?code='.(!empty($_SESSION['cart_code']) ? $_SESSION['cart_code'] : '0');
}

function process_exception($e, $diafan, $pay)
{
    $message = $e->getMessage();
    show_message('Оплата не произведена. '.$message);
    if($e->getCode()<500)
    {
        Custom::inc('includes/mail.php');
        send_mail($diafan->configmodules('emailconfadmin', 'shop') ? $diafan->configmodules('email_admin', 'shop') : EMAIL_CONFIG, 'Ошибка Яндекс.Денег', "Возникла проблема при оплате заказа №$pay[element_id] на сайте $_SERVER[SERVER_NAME]. $message");
    }
    else
    {
        show_message('Вернитесь на <a href="'.get_cart_link($pay).'">страницу оплаты</a> и повторите платеж через некоторое время.');
    }
    exit;
}