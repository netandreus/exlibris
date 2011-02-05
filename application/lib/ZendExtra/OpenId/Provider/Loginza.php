<?php
class ZendExtra_OpenId_Provider_Loginza extends ZendExtra_OpenId_Provider_Abstract {
           
    /**
     * Символическое имя
     */
    protected $_name = 'Loginza';
    
    /**
     * Префикс для имен пользователей, зарегистрированных через
     * этого провайдера
     */
    protected $_usernamePrefix = 'lg';
    
    
    /**
     * Возвращает username openid-пользователя
     */
    public function generateUsername($preferredUsername = NULL)
    {
        // Пробуем достать желаемое имя из запроса
        $userData = $this->_broker->getUserData();

        // Ники в контакте могут совпадать, так что они не являются уникальными
        $identity = $userData['profile'][$this->_broker->getIdentityFieldName()];
        if(strpos($identity, 'loginza.ru/') !== false) {
            $id = str_replace('.loginza.ru/', '', $identity);
            $id = str_replace('http://', '', $id);
            $username = $this->_usernamePrefix.self::DELMITTER.$id;
        } else {
            throw new Exception('Wrong Loginza identity', 500);
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
        $userData = $this->_broker->getUserData();
        $data = array(
            'password'        => $this->generatePassword(Users_Service_Adapter_OpenId::PASSWORD_LENGTH),
            'email'           => NULL,
            'role'            => User::ROLE_REGISTERED,
            'balance'         => 0,
            'sex_id'          => 14, // not defined
            'currency_id'     => 1, // USD
            'language_code'   => $languageCode,
            'created_at'      => date('Y-m-d H:i:s', time()),
            'username'        => (key_exists('nickname', $userData['profile']))? $this->generateUsername($userData['profile']['nickname']) : $this->generateUsername(),
            'name'            => (key_exists('name', $userData['profile']) && key_exists('full_name', $userData['profile']['name']))? $userData['profile']['name']['full_name'] : $this->generateUsername(),
            'openid_identity' => $this->_broker->getIdentityUrl(),
            'email'           => $userData['profile']['email']
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