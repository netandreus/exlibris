<?php
/**
 * Класс для работы с OpenId-брокером
 */
abstract class ZendExtra_OpenId_Broker_Abstract
{
    
   /**
    * Параметры Zend_Http_Client
    */
   protected $_clientConfig = array(
       'maxredirects' => 1, 
       'timeout'      => 30, 
       'useragent'    => 'Mozilla/5.0 (X11; U; Linux x86_64; ru; rv:1.9.0.19) Gecko/2010040121 Ubuntu/9.04 (jaunty) Firefox/3.0.19'
   );
   
   /**
    * Заголовки запроса клиента к брокеру
    */
   protected $_clientHeaders = array(
       'Accept' => '   text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
       'Accept-Language' => 'ru,en-us;q=0.7,en;q=0.3',
       'Accept-Encoding' => 'gzip,deflate',
       'Accept-Charset' => 'windows-1251,utf-8;q=0.7,*;q=0.7',
       'Keep-Alive' => '300',
       'Connection' => 'keep-alive'
   );
   
    /**
     * Опции брокера поу-молчанию
     */
    protected $_options =  array(
        'protocol'  => NULL,
        'host'      => NULL,
        'query'     => NULL,
        'method'    => 'POST',
        'params'    => array()
    );
    
    /**
    * Http-клиент, делающий запросы к брокеру
    * @var Zend_Http_Client
    */
   protected $_client = NULL;
   
    /**
     * Текущий OpenId-провайдер
     * @var ZendExtra_OpenId_Provider_Abstract
     */
    protected $_provider = NULL;
    
   /**
    * Массив данных, полученых от OpenId-провайдера
    * через брокер
    */
   protected $_userData = NULL;
   
    /**
     * Нормализует данные полученные от OpenId-провайдера
     */
    abstract protected function _normalizeUserData($userData);
    
    /**
     * Проверка на ошибки принятых данных
     */
    abstract protected function _isValid($data);
    
    
    /**
     * Возвращает опции брокера
     */
    public function getOptions()
    {
        return $this->_options;
    }
    
    /**
     * Возвращает опцию с именем name
     */
    public function getOption($name)
    {
        return $this->_options[$name];
    }
    
    /**
     * Устанавливает перемекнную запроса к OpenId-Api
     */
    public function setParam($paramName, $value) {
        $this->_options['params'][$paramName] = $value;
    }
    
    public function setClient(Zend_Http_Client $client)
    {
        $this->_client = $client;
    }
    
    /**
     * Возвращает Http-клиент
     * @return Zend_Http_Client
     */
    public function getClient()
    {
        return $this->_client;
    }
   
    /**
     * Осуществляет запроса к OpenId-провайдеру
     */
    protected function _request()
    {
        try { 
            $responce = $this->_client-> request($this->_options['method']);
            $body = $responce ->getBody(); 
        } catch (Exception $e) {
            throw new Exception('OpenId provider does not respond for request'.$this->_options['query'], '500');
        }
        $userData = $this->_normalizeUserData($body);
        if(!$this->_isValid($userData))
            throw new Exception('Error recieved from OpenId-provider', 500);
        return $userData;
    }
    
    /**
     * Проводит аутентификацию через клиента
     */
    public function authenticate($adapterParams)
    {
        $this->setParam('token', $adapterParams['token']);
        
        if($this->_client == NULL) {
            // Инициализация клиента для брокера
           $this->_client =  new Zend_Http_Client($this->getUri(), $this->_clientConfig);
           $this->_client->setHeaders($this->_clientHeaders);
        } else {
            $this->_client->setUri($this->_broker->getUri());
        }
        
        // Получение данных
        try {
            $this->_userData = $this->_request();
            $this->setProviderByIdentifier();
            if($this->getProvider()->isNeedForm()) {
                $result = new ZendExtra_Auth_Result(ZendExtra_Auth_Result::SUCCESS_NEED_EXTRA_DATA, $this->_userData);
            } else {
                $result = new ZendExtra_Auth_Result(ZendExtra_Auth_Result::SUCCESS, $this->_userData);
            }
        } catch (Exception $e) {
            $result = new ZendExtra_Auth_Result(ZendExtra_Auth_Result::FAILURE, NULL, array($e->getMessage()));
        }
        
        return $result;
    }
    

   
    /**
     * Возвращает uri для запроса данных о пользователе у OpenId-брокера
     * @return string $uri
     */
    public function getUri()
    {
        $uri = $this->_options['protocol'].'://'.$this->_options['host'].$this->_options['query'];
        if(key_exists('params', $this->_options) && count($this->_options['params'] > 0))
            $uri .= '?';
        foreach ($this->_options['params'] as $name => $value)
            $uri .= '&'.$name.'='.$value;
        return $uri;
    }
    
    public function getIdentityFieldName()
    {
        return $this->_options['identityFieldName'];
    }
    
    public function getIdentityUrl()
    {
        return $this->_userData['profile'][$this->getIdentityFieldName()];
    }
    
    /**
     * Устанавливает класс OpenId провайдера дял брокера
     * @return ZendExtra_OpenId_ProviderAbstract
     */
    public function setProviderByIdentifier()
    {
       $identifierFieldName = $this->getIdentityFieldName();
       if(!key_exists($identifierFieldName, $this->_userData['profile']))
           throw new Exception('Can not determine openid provider, because '.$identifierFieldName.' field does not exists in userData. Maybe your token is expired.', 500);
       
       $identifier = $this->_userData['profile'][$this->getIdentityFieldName()];

       if(strpos($identifier, 'google')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Google($this);
       } elseif(strpos($identifier, 'vkontakte')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Vkontakte($this);
       } elseif(strpos($identifier, 'mail.ru')) {
           $this->_provider = new ZendExtra_OpenId_Provider_MailRu($this);
       } elseif(strpos($identifier, 'yandex.ru')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Yandex($this);
       } elseif(strpos($identifier, 'facebook')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Facebook($this);
       } elseif(strpos($identifier, 'twitter')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Twitter($this);
       } elseif(strpos($identifier, 'myopenid')) {
           $this->_provider = new ZendExtra_OpenId_Provider_MyOpenid($this);
       } elseif(strpos($identifier, 'aol')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Aol($this);
       } elseif(strpos($identifier, 'yahoo')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Yahoo($this);
       } elseif(strpos($identifier, 'wmkeeper')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Webmoney($this);
       } elseif(strpos($identifier, 'loginza')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Loginza($this);
       } elseif(strpos($identifier, 'rambler')) {
           $this->_provider = new ZendExtra_OpenId_Provider_Rambler($this);
       } else {
           throw new Exception('Unknown openid provider', 500);
       }
    }
    
    /**
     * Устанавливает класс OpenId провайдера дял брокера
     * @return ZendExtra_OpenId_ProviderAbstract
     */
    public function setProviderByName($name)
    {
       if($name == 'Google') {
           $this->_provider = new ZendExtra_OpenId_Provider_Google($this);
       } elseif($name == 'Vkontakte') {
           $this->_provider = new ZendExtra_OpenId_Provider_Vkontakte($this);
       } elseif($name == 'MailRu') {
           $this->_provider = new ZendExtra_OpenId_Provider_MailRu($this);
       } elseif($name == 'Facebook') {
           $this->_provider = new ZendExtra_OpenId_Provider_Facebook($this);
       } elseif($name == 'Twitter') {
           $this->_provider = new ZendExtra_OpenId_Provider_Twitter($this);
       } elseif($name == 'MyOpenid') {
           $this->_provider = new ZendExtra_OpenId_Provider_MyOpenid($this);
       } elseif($name == 'Aol') {
           $this->_provider = new ZendExtra_OpenId_Provider_Aol($this);
       } elseif($name == 'Yahoo') {
           $this->_provider = new ZendExtra_OpenId_Provider_Yahoo($this);
       } elseif($name == 'Loginza') {
           $this->_provider = new ZendExtra_OpenId_Provider_Loginza($this);
       } elseif($name == 'Webmoney') {
           $this->_provider = new ZendExtra_OpenId_Provider_Webmoney($this);
       } elseif($name == 'Rambler') {
           $this->_provider = new ZendExtra_OpenId_Provider_Rambler($this);
       } else {
           throw new Exception('Unknown openid provider', 500);
       }
    }
    
    public function getUserData()
    {
        return $this->_userData;
    }
    
    public function setUserData(array $userData)
    {
        $this->_userData = $userData;
    }
    
    /**
     * Возвращает объекта OpenId провайдера для брокера
     * @return ZendExtra_OpenId_Provider_Abstract $provider OpenId провайдер
     */
    public function getProvider() {
        return $this->_provider;
    }
    
}
?>