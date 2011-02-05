<?php
/**
 * @category   Zend
 * @package    Zend_Auth
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZendExtra_Auth_Result extends Zend_Auth_Result 
{
    /**
     * General Failure
     */
    const FAILURE                        =  0;

    /**
     * Failure due to identity not being found.
     */
    const FAILURE_IDENTITY_NOT_FOUND     = -1;

    /**
     * Failure due to identity being ambiguous.
     */
    const FAILURE_IDENTITY_AMBIGUOUS     = -2;

    /**
     * Failure due to invalid credential being supplied.
     */
    const FAILURE_CREDENTIAL_INVALID     = -3;    
    
    /**
     * Can not register/auth with this email
     * Email is not uniqual
     */
    const FAILURE_DUPLICATE_EMAIL        = -4;
    
    /**
     * Failure due to uncategorized reasons.
     */
    const FAILURE_UNCATEGORIZED          = -5;

    /**
     * Authentication success.
     */
    const SUCCESS                        =  1;

    /**
     * Authentification success,
     * but it needs extra data about user
     */
    const SUCCESS_NEED_EXTRA_DATA        =  2;
    
    /**
     * Sets the result code, identity, and failure messages
     *
     * @param  int     $code
     * @param  mixed   $identity
     * @param  array   $messages
     * @return void
     */
    public function __construct($code, $identity, array $messages = array())
    {
        $code = (int) $code;

        if ($code < self::FAILURE_UNCATEGORIZED) {
            $code = self::FAILURE;
        }
        
        $this->_code     = $code;
        $this->_identity = $identity;
        $this->_messages = $messages;
    }
    
    public function setIdentity(array $data)
    {
        $this->_identity = $data;
    }
}
