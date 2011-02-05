<?php
class ZendExtra_OpenId_Provider_Google extends ZendExtra_OpenId_Provider_Abstract {
    
    /**
     * Префикс для имен пользователей, зарегистрированных через
     * этого провайдера
     */
    protected $_usernamePrefix = 'gl';
    
    /**
     * Символическое имя
     */
    protected $_name = 'Google';
    
    public function generateUsername($preferredUsername = NULL)
    {
        $userData = $this->_broker->getUserData();
        if($preferredUsername != NULL) {
            $username = $this->_usernamePrefix
                    .ZendExtra_OpenId_Provider_Abstract::DELMITTER
                    .$preferredUsername;
        } else {
            if(key_exists('email', $userData['profile'])) {
                $tmp = explode('@', $userData['profile']['email']);
                $username = $this->_usernamePrefix
                    .ZendExtra_OpenId_Provider_Abstract::DELMITTER
                    .$tmp[0];
            } else {
                throw new Exception('Google authId provider without email is not implemented', 500);
            }
        }
        return $username;
    }
    
    public function getEmail()
    {
        $userData = $this->_broker->getUserData();
        if(key_exists('email', $userData['profile'])) {
            return $userData['profile']['email'];
        } else {
            return false;
        }
    }
    
    public function generateUserData() {
        
        // Получаем необходимые парметры из запроса
        $langs = Zend_Controller_Front::getInstance()->getRequest()->getHeader('Accept-Language');
        $languageCode = substr($langs, 0,2);
        $userData = $this->_broker->getUserData();
        $data = array(
            'password'        => $this->generatePassword(Users_Service_Adapter_OpenId::PASSWORD_LENGTH),
            'email'           => $this->getEmail(),
            'role'            => User::ROLE_REGISTERED,
            'balance'         => 0,
            'sex_id'          => 14, // not defined
            'currency_id'     => 1, // USD
            'language_code'   => $languageCode,
            'created_at'      => date('Y-m-d H:i:s', time()),
            'username'        => (key_exists('preferredUsername', $userData['profile']))? $this->generateUsername($userData['profile']['preferredUsername']) : $this->generateUsername(),
            'name'            => (key_exists('name', $userData['profile'])&& key_exists('formatted', $userData['profile']['name']))? $userData['profile']['name']['formatted'] : $this->generateUsername(),
            'openid_identity' => $this->_broker->getIdentityUrl()
        );
        return $data;
    }
    
    public function onRegister() {}
}
?>