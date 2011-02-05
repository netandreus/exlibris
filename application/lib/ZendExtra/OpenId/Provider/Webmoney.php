<?php
class ZendExtra_OpenId_Provider_Webmoney extends ZendExtra_OpenId_Provider_Abstract {
           
    /**
     * Нужна ли дополнительная форма 
     * после регистрации
     */
    protected $_needForm = true;
    
    /**
     * Символическое имя
     */
    protected $_name = 'Webmoney';
    
    /**
     * Префикс для имен пользователей, зарегистрированных через
     * этого провайдера
     */
    protected $_usernamePrefix = 'wm';
    
    
    /**
     * Возвращает username openid-пользователя
     */
    public function generateUsername($preferredUsername = NULL)
    {
        // Пробуем достать желаемое имя из запроса
        $userData = $this->_broker->getUserData();

        // Ники в контакте могут совпадать, так что они не являются уникальными
        $identity = $userData['profile'][$this->_broker->getIdentityFieldName()];
        if(strpos($identity, 'wmkeeper.com') !== false) {
            $tmp = str_replace('https://', '', $identity);
            $tmp = str_replace('http://', '', $tmp);
            $tmp = explode('.wmkeeper.com/', $tmp);
            $id = $tmp[0];
            $username = $this->_usernamePrefix.self::DELMITTER.$id;
        } else {
            throw new Exception('Wrong Webmoney identity', 500);
        }
        return $username;
    }
    
    /**
     * Возвращает данные для заполнения пользователя
     */
    public function generateUserData() {
        // Получаем необходимые парметры из запроса
        $langs = Zend_Controller_front::getInstance()->getRequest()->getHeader('Accept-Language');
        $languageCode = substr($langs, 0,2);
        $data = array(
            'password'        => $this->generatePassword(Users_Service_Adapter_OpenId::PASSWORD_LENGTH),
            'email'           => NULL,
            'role'            => User::ROLE_REGISTERED,
            'balance'         => 0,
            'sex_id'          => 14, // not defined
            'currency_id'     => 1, // USD
            'language_code'   => $languageCode,
            'created_at'      => date('Y-m-d H:i:s', time()),
            'username'        => $this->generateUsername(),
            'name'            => $this->generateUsername(),
            'openid_identity' => $this->_broker->getIdentityUrl()
        );
        return $data;
    }
    
    /**
     * Хук. Вызывается после завершения openId-регистрации
     * для каких-либо доп. действий. Переопределяется в наследниках
     */
    public function onRegister()
    {
    }
}
?>