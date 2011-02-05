<?php
abstract class ZendExtra_OpenId_Provider_Abstract {
    
    /**
     * Разделитель при формировании username
     */
    const DELMITTER = '_';
    
    /**
     * Нужна ли дополнительная форма 
     * после регистрации
     */
    protected $_needForm = false;
    
    /**
     * Префикс для имен пользователей, зарегистрированных через
     * openId (переопределяется в наследниках)
     */
    protected $_usernamePrefix = 'ext';
    
    /**
     * Брокер этого провайдера
     */
    protected $_broker = NULL;
    
    /**
     * Генерирует данные для пользователя системы на
     * основе OpenId данных в зависимости от провайдера
     */
    abstract public function generateUserData();
    
    /**
     * Устанвока брокера
     */
    public function __construct($broker)
    {
        $this->_broker = $broker;
    } 
            
    /**
     * Генерирует форму для заполнения доп данных
     * @return Zend_Form $form Форма для завершения регистрации
     */
    public function getForm($params)
    {
        $form = new Zend_Form();
                
        // Устанавливаем декораторы
        $form->setAction($form->getView()->url($params,'auth_openid_complete'))
             ->setAttrib('id', 'zend_form');
        
        //Email
        $email = new Zend_Form_Element_Text('email');
        $email -> setRequired(true)
               -> addValidator('EmailAddress',false,array('domain' => false))
               -> addValidator(new ZendExtra_Validate_Email())
               -> setOptions(array('domain' => false))
               -> setLabel('Email:')
               ->setDescription($form->getView()->translate('Enter email for recieving password'))
               -> setAttrib('onBlur', 'validate(this);');
        $form->addElement($email);
        
        // Кнопка "Отправить"
        $submit = new Zend_Form_Element_Text('submit');
        $html = '<dt></dt><dd><a class="btn-base btn-normal" href="javascript:;" onClick=\'$("#zend_form").submit();\'><span></span>'.$form->getView()->translate('submit').'</a></dd>';
        $submit -> setDecorators(array('decorator' => array('br' => new ZendExtra_Form_Decorator_HtmlCode(array('tag' => $html, 'placement' => Zend_Form_Decorator_Abstract::PREPEND)))))
                ->setOrder(100);
        $form->addElement($submit);  
        
        return $form;
    }
    
    /*
     * Возвращает символическое имя
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Возвращает занчение, нужна ли форма
     * для запроса доп. данных пользователя
     * @return bool $needForm
     */
    public function isNeedForm()
    {
        return $this->_needForm;
    }
    
    /**
     * Генерация случайного пароля
     */
    public function generatePassword($maxChars)
    {
        $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
        // Определяем количество символов в $chars
        $size = StrLen ( $chars ) - 1;
        // Определяем пустую переменную, в которую и будем записывать символы.
        $password = null;
        // Создаём пароль.
        while ( $maxChars -- )
            $password .= $chars [rand ( 0, $size )];
        return $password;
    }
    
    /**
     * Хук. Вызывается после завершения openId-регистрации
     * для каких-либо доп. действий. Переопределяется в наследниках
     */
    abstract public function onRegister();
    
}
?>