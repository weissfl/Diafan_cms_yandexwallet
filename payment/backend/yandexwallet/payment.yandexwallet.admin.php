<?php
/**
* Настройки приема платежей на Яндекс.Кошелек
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

class Payment_yandexwallet_admin
{
    public $config;
    private $diafan;

    public function __construct(&$diafan)
    {
        $this->diafan = &$diafan;
        $this->config = array(
            'name' => 'Яндекс.кошелек',
            'params' => array(
                'receiver' => array(
                    'name' => 'Номер кошелька',
                    'help' => 'На этот кошелек будут приходить платежи'
                ),
                'client_id' => array(
                    'name' => 'Идентификатор приложения',
                    'help' => 'Скопируйте идентификатор приложения из настроек вашего приложения на сайте яндекса'
                ),
                'paymentType' => array(
                    'type' => 'function',
                ),
                'instance_id' => array(
                    'type' => 'function',
                ),
                'secret' => array(
                    'name' => 'OAuth2 client_secret',
                    'help' => 'Скопируйте секретное слово из настроек вашего приложения на сайте яндекса'
                ),                
                'fee' => array(
                    'name' => 'Процент за операцию',
                    'type' => 'function',
                    'help' => 'Сумма к оплате будет увеличена на указанный процент. Яндекс взимает за перевод с кошелька 0.5%, за перевод с банковской карты 2%. Если вы хотите возложить плату за перевод на пользователя, полностью или частично, введите сюда нужное количество процентов. Если вы взимаете процент, уведомите покупателя об этом в поле "Описание".',
                ),
            )
        );
    }

    public function edit_variable_paymentType($value)
    {
        $ver = VERSION_CMS;
        if($ver{0}<6)
        {
        ?>
        <tr class="tr_payment" payment="yandexwallet" style="display:none">
            <td class="td_first">
                Способ оплаты
            </td>
            <td>
                <select name="yandexwallet_paymentType">
                    <option value="PC"<?php if($value=='PC') echo ' selected="selected"' ?>>Яндекс.кошелек</option>
                    <option value="AC"<?php if($value=='AC') echo ' selected="selected"' ?>>Банковская карта</option>
                </select>
                <span class="help_img"><img src="/adm/img/quest.gif" title='Откуда покупатель будет перечислять деньги.'></span>
            </td>
        </tr>
        <?php
        }
        else
        {
        ?>
        <div class="unit tr_payment" payment="yandexwallet" style="display:none">
            <div class="infofield">
                Способ оплаты
                <i class="tooltip fa fa-question-circle" title="Откуда покупатель будет перечислять деньги."></i>
            </div>
            <select name="yandexwallet_paymentType">
                <option value="PC"<?php if($value=='PC') echo ' selected="selected"' ?>>Яндекс.кошелек</option>
                <option value="AC"<?php if($value=='AC') echo ' selected="selected"' ?>>Банковская карта</option>
            </select>
        </div>
        <?php
        }
    }

    public function save_variable_fee()
    {
        return $this->diafan->filter($_POST,'float','yandexwallet_fee');
    }

    public function edit_variable_instance_id($value)
    {
        echo '<input type="hidden" name="yandexwallet_instance_id" value="'.(!empty($value) ? 'true':'false').'" />';
    }

    public function save_variable_instance_id()
    {
        if($_POST['yandexwallet_paymentType'] == 'AC')
        {
            if($_POST['yandexwallet_instance_id']=='false' && !empty($_POST['yandexwallet_client_id']))
            {
                include_once 'lib/external_payment.php';
                $response = \YandexMoney\ExternalPayment::getInstanceId($_POST['yandexwallet_client_id']);
                if($response->status == 'success') {
                    return $response->instance_id;
                }
                else {
                    throw new Exception($response->error);
                }
            }
            else
            {
                $params = unserialize(DB::query_result('SELECT params FROM {payment} WHERE id=%d LIMIT 1', $this->diafan->id));
                return $params['instance_id'];
            }
        }        
   }
    
}

