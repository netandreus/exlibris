<?php
class ZendExtra_OpenId_Provider_Yandex extends ZendExtra_OpenId_Provider_Abstract {
        
    /**
     * Символическое имя
     */
    protected $_name = 'Yandex';
    
    /**
     * Префикс для имен пользователей, зарегистрированных через
     * этого провайдера
     */
    protected $_usernamePrefix = 'ya';
    
    /**
     * Массив распарсенных переменных из idenity
     */
    protected $_identityParsedArray = NULL;
    
    
    public function __construct($broker) {
        parent::__construct($broker);
        $userData = $this->_broker->getUserData();
        $this->_parseIdentity($userData['profile'][$this->_broker->getIdentityFieldName()]);
    }
    
    protected function _parseIdentity($identity)
    {
        $tmp = str_replace('http://openid.yandex.ru/', '', $identity);
        $tmp = str_replace('/', '', $tmp);
        $this->_identityParsedArray = array(
            'username' => $tmp,
            'domain'   => 'yandex'
        );
    }
    
    /**
     * Возвращает username openid-пользователя
     */
    public function generateUsername()
    {
        $username = $this->_usernamePrefix
        .self::DELMITTER.$this->_identityParsedArray['username'];
        return $username;
    }
    
    public function generateEmail()
    {
        $email = $this->_identityParsedArray['username'].'@'.$this->_identityParsedArray['domain'].'.ru';
        return $email;
    }
    
    /**
     * Возвращает данные для заполнения пользователя
     */
    public function generateUserData() {
        // Получаем необходимые парметры из запроса
        $langs = Zend_Controller_front::getInstance()->getRequest()->getHeader('Accept-Language');
        $languageCode = substr($langs, 0,2);
        $data = array(
            'password'      => $this->generatePassword(Users_Service_Adapter_OpenId::PASSWORD_LENGTH),
            'email'         => $this->generateEmail(),
            'role'          => User::ROLE_REGISTERED,
            'balance'       => 0,
            'sex_id'        => 14, // not defined
            'currency_id'   => 1, // USD
            'language_code' => $languageCode,
            'created_at'    => date('Y-m-d H:i:s', time()),
            'username'      => $this->generateUsername(),
            'name'          => $this->generateUsername(),
            'openid_identity' => $this->_broker->getIdentityUrl()
        );
        return $data;
    }
    
    /**
     * Хук. Вызывается после завершения openId-регистрации
     * для каких-либо доп. действий. Переопределяется в наследниках
     */
    public function onRegister() {}
}
?>