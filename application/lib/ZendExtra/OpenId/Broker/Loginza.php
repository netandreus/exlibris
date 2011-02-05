<?php
/**
 * Класс для работы с OpenId-брокером
 * Loginza.ru
 *
 */
class ZendExtra_OpenId_Broker_Loginza extends ZendExtra_OpenId_Broker_Abstract
{
    protected $_options =  array(
        'protocol'  => 'http',
        'host'      => 'loginza.ru',
        'query'     => '/api/authinfo',
        'method'    => 'POST',
        'params'    => array(),
        'identityFieldName' => 'identity'
    );
    

    
    /**
     * Нормализует данные полученные от OpenId-провайдера
     * @param json $userData
     */
    protected function _normalizeUserData($userData)
    {
        return array('profile' => Zend_Json::decode($userData));
    }
    
    /**
     * Проверка на ошибки принятых данных
     */
    protected function _isValid($userData)
    {
        return true;
    }
    
}
?>