<?php
/**
 * Класс для работы с OpenId-брокером
 * Rpxnow.com
 *
 */
class ZendExtra_OpenId_Broker_Rpxnow extends ZendExtra_OpenId_Broker_Abstract
{
    protected $_options =  array(
        'protocol'  => 'https',
        'host'      => 'rpxnow.com',
        'query'     => '/api/v2/auth_info',
        'method'    => 'POST',
        'params'    => array(
            'apiKey' => 'yourapikey',
            'format' => 'json'
        ),
        'identityFieldName' => 'identifier'
     );
     
     
    
    /**
     * Нормализует данные полученные от OpenId-провайдера
     * @param json $userData
     */
    protected function _normalizeUserData($userData)
    {
        return Zend_Json::decode($userData);
    }
    
    /**
     * Проверка на ошибки принятых данных
     */
    protected function _isValid($userData)
    {
        if(key_exists('err', $userData))
            return false;
        return true;
    }
}
?>
