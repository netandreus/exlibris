<?php
class ZendExtra_OpenId_Provider_Twitter extends ZendExtra_OpenId_Provider_Abstract {
        
    /**
     * Нужна ли дополнительная форма 
     * после регистрации
     */
    protected $_needForm = true;
    
    /**
     * Символическое имя
     */
    protected $_name = 'Twitter';
    
    /**
     * Префикс для имен пользователей, зарегистрированных через
     * этого провайдера
     */
    protected $_usernamePrefix = 'tw';
    
    
    /**
     * Возвращает username openid-пользователя
     */
    public function generateUsername($preferredUsername = NULL)
    {
        // Пробуем достать желаемое имя из запроса
        $userData = $this->_broker->getUserData();

        // Ники в контакте могут совпадать, так что они не являются уникальными
        $identity = $userData['profile'][$this->_broker->getIdentityFieldName()];
        if(strpos($identity, 'http://twitter.com/') !== false) {
            $id = str_replace('http://twitter.com/', '', $identity);
            $username = $this->_usernamePrefix.self::DELMITTER.$id;
        } else {
            throw new Exception('Wrong Facebook identity', 500);
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
       $openidData = $this->_broker->getUserData();
       if(key_exists('profile', $openidData) && key_exists('photo', $openidData['profile'])) {
           $this->_copyAvatar($openidData['profile']['photo']);
       }
    }
    
    /**
     * Копирует аватар пользователя из Vkontakte в систему
     */
    protected function _copyAvatar($avatarUrl)
    {   
        //Читаем картинку и сохраняем в jpeg в нужную дирректорию
        $im = @imagecreatefromjpeg($avatarUrl);//JPEG:                                   
        if ($im == false) $im = @imagecreatefromgif($avatarUrl);//GIF                
        if ($im == false) $im = @imagecreatefrompng($avatarUrl);//png                                        
        if ($im == false) {
            return;
        }
        // Определяем мета-данные картинки
        $usersService = ZendExtra_Controller_Action_Helper_Service::get('Users', 'users');
        $user = $usersService->getCurrentUser();

        $defaultService = ZendExtra_Controller_Action_Helper_Service::get('Default', 'default');
        $extension = $defaultService->getExtensionFromUrl($avatarUrl);
        $imageData = array(
            'user_id' => $user['id'],
            'object_type' => ObjectToItem::OBJECT_TYPE_USER,
            'object_id' => $user['id'],
            'width' => imagesx($im),
            'height' => imagesy($im),
            'mime_type' => $defaultService -> getMimeTypeFromExtension($extension),
            'type' => Photo::TYPE_AVATAR
        );
        $image = new Photo();
        $image->fromArray($imageData);
        if($image->save() != FALSE) {
            return false;
        }
        // Копируем картинку
        $savePath = ROOT_PATH.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'pictures'.DIRECTORY_SEPARATOR.ObjectToItem::OBJECT_TYPE_USER.DIRECTORY_SEPARATOR.$image['id'].'.'.$extension;
        if(!imagejpeg($im, $savePath)) {
            ImageDestroy($im);
            $image->delete();
            return false;
        }
        ImageDestroy($im);
        return true;
    }

}
?>