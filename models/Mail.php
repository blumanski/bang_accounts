<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Outgoing mails
 */

Namespace Bang\Modules\Account\Models;

Class Mail
{
	/**
	 * ErrorLog object
	 * @var object
	 */
	private $ErrorLog;
	
	/**
	 * Session instance
	 * @var object
	 */
	private $Session;
	
	/**
	 * Instance of Language object
	 * @var object
	 */
	private $Lang;

	
	public function __construct(\stdClass $di)
	{
        $this->ErrorLog 		= $di->ErrorLog;
        $this->Session	 		= $di->Session;
        $this->Lang				= $di->View->Lang;
	}
	
	/**
	 * Send reset code to email address.
	 * @param array $userdata
	 * @param string $code
	 */
	public function dispatchResetMail(array $userData, string $code) : bool
	{
		if(is_array($userData) && count($userData)) {
			
			if(isset($userData['username']) && isset($userData['email'])) {
				
					try {
					
					$transport = \Swift_SmtpTransport::newInstance(CONFIG['mailer']['host'], CONFIG['mailer']['port'])
					->setUsername(CONFIG['mailer']['username'])
					->setPassword(CONFIG['mailer']['password'])
					;
					
					$mailer 	= \Swift_Mailer::newInstance($transport);
					$message	= \Swift_Message::newInstance()
					->setSubject($this->Lang->get('account_forgot_email_subject'))
					->setFrom(array(CONFIG['app']['senderemail'] => CONFIG['app']['senderemail']))
					->setTo(array($userData['email'] => ucfirst($userData['username'])))
					->addPart('<p>'.$this->Lang->getCombine(
							'account_forgot_email_htmlbody', 
							array(PROT.CONFIG['app']['backendurl'].DIRECTORY_SEPARATOR.'account'.DIRECTORY_SEPARATOR.
							'index'.DIRECTORY_SEPARATOR.'reset'.DIRECTORY_SEPARATOR.'code'.
							DIRECTORY_SEPARATOR.$code)), 'text/html')
					;
					
					if($mailer->send($message) > 0) {
						return true;
					}
				
				} catch (\Swift_TransportException $e) {
					
					$this->Session->setError($this->Lang->get('account_reset_password_form_exception'));
					$this->ErrorLog->logError('Mail', $e->getMessage(), __METHOD__ .' - Line: '. __LINE__);
					
				} catch (\Exception $a) {

					$this->Session->setError($this->Lang->get('account_reset_password_form_exception'));
					$this->ErrorLog->logError('Mail', $e->getMessage(), __METHOD__ .' - Line: '. __LINE__);
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Send registration email after admin added a new user to the system
	 * @param array $userdata
	 * @param string $code
	 */
	public function dispatchRegisterMail(array $userData, string $code) : bool
	{
		if(is_array($userData) && count($userData)) {
				
			if(isset($userData['username']) && isset($userData['email'])) {
	
				$transport = \Swift_SmtpTransport::newInstance(CONFIG['mailer']['host'], CONFIG['mailer']['port'])
				->setUsername(CONFIG['mailer']['username'])
				->setPassword(CONFIG['mailer']['password'])
				;
	
				$mailer 	= \Swift_Mailer::newInstance($transport);
				$message	= \Swift_Message::newInstance()
				->setSubject($this->Lang->getCombine(
							'account_user_create_form_mail_register_subject', 
							array(CONFIG['app']['domain'], $userData['username'])))
				->setFrom(array(CONFIG['app']['senderemail'] => CONFIG['app']['senderemail']))
				->setTo(array($userData['email'] => ucfirst($userData['username'])))
				->addPart('<p>'.$this->Lang->getCombine(
							'account_user_create_form_mail_register_body', 
							array($userData['username'], PROT.CONFIG['app']['backendurl'].DIRECTORY_SEPARATOR.'account'.
							DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'reset'.DIRECTORY_SEPARATOR.'code'.
							DIRECTORY_SEPARATOR.$code)), 'text/html')
				;
	
				if($mailer->send($message) > 0) {
					return true;
				}
			}
		}
	
		return false;
	}
	
	/**
	 * Send email after a password was reset
	 * @param array $data
	 */
	public function sendPasswordChangeNotification(array $user) : bool
	{
	    if(is_array($user) && count($user)) {
	        	
	        if(isset($user['username']) && isset($user['email'])) {
	    
	            $transport = \Swift_SmtpTransport::newInstance(CONFIG['mailer']['host'], CONFIG['mailer']['port'])
	            ->setUsername(CONFIG['mailer']['username'])
	            ->setPassword(CONFIG['mailer']['password'])
	            ;
	    
	            $mailer 	= \Swift_Mailer::newInstance($transport);
	            $message	= \Swift_Message::newInstance()
	            ->setSubject($this->Lang->get('account_reset_password_form_success_email_subject'))
	            ->setFrom(array(CONFIG['app']['senderemail'] => CONFIG['app']['senderemail']))
	            ->setTo(array($user['email'] => $user['username']))
	            ->addPart('<p>'.$this->Lang->getCombine(
	            		'account_reset_password_form_success_email_body', 
	            		array(ucfirst($user['username']))).'</p>', 'text/html')
	            ;
	    
	            if($mailer->send($message) > 0) {
	                return true;
	            }
	        }
	    }
	    
	    return false;
	}
	
	/**
	 * Must be in all classes
	 * @return array
	 */
	public function __debugInfo() {
	
		$reflect	= new \ReflectionObject($this);
		$varArray	= array();
	
		foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
			$propName = $prop->getName();
			 
			if($propName !== 'DI') {
				//print '--> '.$propName.'<br />';
				$varArray[$propName] = $this->$propName;
			}
		}
	
		return $varArray;
	}
}