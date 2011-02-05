<?php
class ZendExtra_OpenId_Provider_MailRu extends ZendExtra_OpenId_Provider_Abstract {

    /**
     * Символическое имя
     */
    protected $_name = 'MailRu';

    /**
     * Префикс для имен пользователей, зарегистрированных через
     * этого провайдера
     */
    protected $_usernamePrefix = 'ml';

    /**
     * Массив распарсенных переменных из idenity
     */
    protected $_identityParsedArray = NULL;


    public function __construct($broker) {
        parent::__construct($broker);
        $userData = $this->_broker->getUserData();
        $this->_parseIdentity($userData['profile']['identity']);
    }

    protected function _parseIdentity($identity)
    {
        $tmp = str_replace('http://my.mail.ru/', '', $identity);
        $tmp = explode('/', $tmp);
        if(count($tmp) > 1) {
            $this->_identityParsedArray = array(
                'username' => $tmp[1],
                'domain'   => $tmp[0]
            );
        }
    }

    /**
     * Возвращает username openid-пользователя
     */
    public function generateUsername()
    {
        $username = $this->_usernamePrefix
        .self::DELMITTER.$this->_identityParsedArray['username']
        .self::DELMITTER.$this->_identityParsedArray['domain'];
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
    public function onRegister()
    {
        $avatarUrl = $this->_getAvatarUrl();
        $this->_copyAvatar($avatarUrl);

    }

    /**
     * Определяет путь к аватару пользователя
     * по OpenId-idenity
     */
    protected function _getAvatarUrl()
    {
        $avatarUrl = 'http://avt.foto.mail.ru/'.$this->_identityParsedArray['domain'].'/'.$this->_identityParsedArray['username'].'/_avatar.jpg';
        return $avatarUrl;
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
            return false;
        }

        // Определяем мета-данные картинки
        $user = (array)Zend_Auth::getInstance()->getIdentity();

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