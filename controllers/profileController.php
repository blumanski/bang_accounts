<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Account Module -> Profile COntroller
 */

Namespace Bang\Modules\Account;

Use Bang\Modules\Account\Models\Db,
	Bang\Modules\Account\Models\Mail,
	Bang\Tools\Awss3,
    Bang\Helper;

class profileController extends \Bang\SuperController implements \Bang\ControllerInterface
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
    	$this->View->addStyle($this->View->TemplatePath.'min/css/account/assets/scss/account.min.css', 0);

    	// every action function in this class needs a permission check
    	// in this case, I can add it to the constructor
    	$this->testPermisions();
    	
    	$this->Data	= new Db($di);
    	$this->Mail	= new Mail($di);
    	$this->Data->setMailInstance($this->Mail);
    	
    	$this->S3 = new Awss3();
    }
    
    /**
     * Process profile update
     */
    public function processprofileAction()
    {
    	$params = Helper::getRequestParams('post');
    	
    	if(is_array($params) && count($params) && isset($params['firstname'])) {
    	
    		if($this->Data->updateUserProfile($params) === true) {
    			 
    			$this->Session->setSuccess($this->Lang->get('account_settings_form_success'));
    			 
    			Helper::redirectTo('/account/profile/index/');
    			exit;
    	
    		} else {
    			// error should be set already
    			Helper::redirectTo('/account/profile/index/');
    			exit;
    		}
    		 
    	} else {
    		 
    		$this->Session->setSuccess($this->Lang->get('account_settings_form_no_data'));
    		Helper::redirectTo('/account/profile/index/');
    		exit;
    	}
    	
    	return false;
    }
    
    /**
     * Index Action - dont want to have one here
     */
    public function indexAction() 
    {
    	$this->View->addStyle($this->View->TemplatePath.'bower_components/dropzone/dist/basic.css', 0);
    	$this->View->addStyle($this->View->TemplatePath.'bower_components/dropzone/dist/dropzone.css', 1);
    	$this->View->addScript($this->View->TemplatePath.'bower_components/dropzone/dist/dropzone.js', 1);	
    	
    	$template = '';
    	
    	$current = $this->Data->getCurrentUserProfile();
    	if(is_array($current) && count($current)) {
    		$this->View->setTplVar('myprofile', $current);
    	}
    	
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'profile.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'profile.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'profile.php');
    	}
    	
    	// main template
    	$this->View->setModuleTpl('pageprofile', $template);
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
    public function testPermisions()
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
