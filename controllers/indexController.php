<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Account -> System Controller
 * The controller handles system actions such as login. logout, password reset
 * 
 * 1. Forgot Password
 * 2. Login
 * 3. Logout
 * 4. ResetPassword
 */

Namespace Bang\Modules\Account;

Use Bang\Modules\Account\Models\Db,
	Bang\Modules\Account\Models\Mail,
    Bang\Helper;

class indexController extends \Bang\SuperController implements \Bang\ControllerInterface
{
	/**
	 * Modules DB Model
	 * @var object
	 */
	private $Data;
	
	/**
	 * View object
	 * @var object
	 */
	private $View;
	
	/**
	 * ErrorLog object
	 * @var object
	 */
	private $ErrorLog;
	
	/**
	 * Instance of Language object
	 * @var object
	 */
	private $Lang;
	
	/**
	 * Keeps the template overwrite folder path
	 * If a template is available in the template folder, it allows
	 * to load that template instead of the internal one.
	 * So, one can easily change templates from the template without touching
	 * the module code.
	 * @var string
	 */
	private $Overwrite;
	
	/**
	 * Instance of Mail object
	 * @var object
	 */
	private $Mail;
	
	/*
	 * Set up the class environment
	 * @param object $di
	 */
    public function __construct(\stdClass $di)
    {
        $this->path     		= dirname(dirname(__FILE__));
    	// assign class variables
    	$this->ErrorLog 		= $di->ErrorLog;
    	$this->View				= $di->View;
    	$this->Session          = $di->Session;
    	$this->Lang				= $di->View->Lang;
    	
    	// Get the current language loaded
    	$currentLang = $this->View->Lang->LangLoaded;
    	
    	$this->Overwrite = $this->getBackTplOverwrite();

    	// Add module language files to language array
    	$this->View->Lang->addLanguageFile($this->path.'/lang/'.$currentLang);
    	$this->View->addStyle($this->View->TemplatePath.'min/css/account.min.css', 0);
    	$this->View->addScript($this->View->TemplatePath.'min/js/account.js', 0);

    	$this->Data	= new Db($di);
    	$this->Mail	= new Mail($di);
    	$this->Data->setMailInstance($this->Mail);
    }
    
    /**
     * Index Action - dont want to have one here
     */
    public function indexAction() {
    	Helper::redirectTo('/account/error/'); // 404 error
    	exit;
    }

    /**
     * Reset the password using a code which is getting send via email
     * 1. Test if $_get code is available
     * 2. If so, show form with password reset
     * 2. If false, set error and redirect to message
     * 3. Test if post data is available
     * 4. If so, validate and call data model
     * 5. If model returns true, set success message and redirect to message
     * 5. If model returns false, redirect to message (message was already set in data model)
     */
    public function resetAction()
    {
    	$this->View->setMainIndex('simple.php');
    	
    	$params    = Helper::getRequestParams('get');
    	$post      = Helper::getRequestParams('post');
    
    	// 3.
    	if(isset($post['code']) && !empty($post['code'])) {
    		
    		// get token from session
    		$token = $this->Session->getToken();
    		
    		if(isset($post['token']) && !empty($token) && $token == $post['token']) {
    		
    			$this->Session->resetToken();
    			
    			// 4.
    			if($this->Data->resetAction($post) === true) {
    				// 5. true
    				$this->Session->setSuccess($this->Lang->get('account_reset_password_form_complete'));
    			
    				Helper::redirectTo('/account/index/login/');
    				exit;
    			
    			} else {
    			
    				// 5. false - message is already set
    				Helper::redirectTo('/account/index/reset/code/'.$post['code'].'/');
    				exit;
    			}
    			
    		} else {
    			
    			$this->Session->setError($this->Lang->get('app_token_error'));
    			Helper::redirectTo('/account/index/reset/');
    			exit;
			}
    	}
    	 
    	// 1.
    	if(isset($params['code']) && !empty($params['code'])) {
    
    		$this->View->setTplVar('code', $params['code']);
    	} 
    	
    	
    	$this->Session->setToken();
    	$token = $this->Session->getToken();
    	$this->View->setTplVar('token', $token);
    	
    	// 2. true
    	 
    	$template = '';
    	
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'reset.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'reset.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'reset.php');
    	}
    	 
    	// main template
    	$this->View->setModuleTpl('bump', $template);
    }
    
    /**
     * Forgot password action
     * 1. General - Show Form to enter email address
     * 2. if post data, validate form and call data model
     * 3. data model returns true, set success message and redirect to message page
     * 3. data model returns false, set error message and show form with error message on top
     * @note Including the google recapctha validation
     */
    public function forgotAction()
    {
    	$this->View->setMainIndex('forgot.php');
    	 
    	$params = Helper::getRequestParams('post');
    	 
    	// 2.
    	if(is_array($params) && count($params)) {
    
    		$ip = Helper::getClientIp();
    			
    		// validate the google captcha right here
    		if(isset($params['g-recaptcha-response']) && !empty($params['g-recaptcha-response'])) {
    			 
    			$recaptcha = new \ReCaptcha\ReCaptcha(CONFIG['recaptcha']['secretkey']);
    			$resp = $recaptcha->verify($params['g-recaptcha-response'], $ip);
    			if ($resp->isSuccess()) {
    				 
    				// get token from session
    				$token = $this->Session->getToken();
    				
    				if(isset($params['token']) && !empty($token) && $token === $params['token']) {
    					 
    					$this->Session->resetToken();
    					
    					if(isset($params['email'])) {
    					
    						if($this->Data->sendPasswordResetCode($params['email']) === true) {
    					
    							// 3. true
    							$this->Session->setSuccess($this->Lang->get('account_forgot_email_sending_success'));
    							Helper::redirectTo('/account/index/reset/');
    							exit;
    					
    						} else {
    					
    							// 3. false
    							$this->Session->setError($this->Lang->get('account_forgot_email_sending_failed'));
    						}
    					}
    					
    				} else {
    			
	    				$this->Session->setError($this->Lang->get('app_token_error'));
	    				Helper::redirectTo('/account/index/login/');
	    				exit;
	    			}
    				 
    			} else {
    
    				$errors = $resp->getErrorCodes();
    			}
    			 
    		} else {
    
    			$this->Session->setError($this->Lang->get('account_captcha_error'));
    		}
    	}
    	
    	$this->Session->setToken();
    	$token = $this->Session->getToken();
    	$this->View->setTplVar('token', $token);
    	 
    	// 1.
    	$this->View->addScript('https://www.google.com/recaptcha/api.js', 1);
    	$this->View->setTplVar('sitekey', CONFIG['recaptcha']['sitekey']);
    	 
    	// load the template
    	$template  = $this->View->loadTemplate($this->path.'/templates/forgot.php');
    	// main template
    	$this->View->setModuleTpl('forgot', $template);
    }
    
    /**
     * User login
     * 1. Test post paramaters
     * 2. If so, call data model and try to login
     * 3. If true, redirect to a page such as dashboard or /
     * 4. Show login template if it wasn't redirected
     *
     * @return mixed
     */
    public function loginAction()
    {
    	$this->View->setMainIndex('login.php');
    	
    	$params = Helper::getRequestParams('post');
    	// 1.
    	if(is_array($params) && count($params)) {
    		
    		// get token from session
    		$token = $this->Session->getToken();
    		
    		if(isset($params['token']) && !empty($token) && $token == $params['token']) {
    			
    			$this->Session->resetToken();
    			
    			// 2.
    			if($this->Data->login($params) === true) {
    				
    				// 3.
    				Helper::redirectTo('/dashboard/index/index/');
    				exit;
    			
    			} else {
    				
    				$this->Session->setError($this->Lang->get('account_login_failed_error'));
    				Helper::redirectTo('/account/index/login/');
    				exit;
    			}
    			
    		} else {
    			
    			$this->Session->setError($this->Lang->get('account_login_error_token'));
    			Helper::redirectTo('/account/index/login/');
    			exit;
    		}
    	}
    	 
    	$this->Session->setToken();
    	$token = $this->Session->getToken();
    	$this->View->setTplVar('token', $token);
    	
    	// 4.
    	$template = '';
    
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login.php');
    	}
    	 
    	// main template
    	$this->View->setModuleTpl('login', $template);
    }
    
    /**
     * Logout
     */
    public function logoutAction()
    {
    	$this->Session->logout('/account/index/login/');
    	exit;
    }
    
    /**
     * Change the language for this session
     */
    public function changelangAction()
    {
    	$this->changeLanguage();
    	 
    }
    

    /**
     * Permission test
     * 1. Test if user is logged in
     */
    public function testPermisions($login = false)
    {
    	// 1. if user is not logegd in, redirect to login with message
    	if($this->Session->loggedIn() === false || $this->Session->hasPermission(1) !== true) {
    		$this->Session->setError($this->Lang->get('application_notlogged_in'));
    		Helper::redirectTo('/account/index/login/');
    		exit;
    	}
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
