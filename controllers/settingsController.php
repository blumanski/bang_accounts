<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * Account Module -> Settings Controller
 */

Namespace Bang\Modules\Account;

Use Bang\Modules\Account\Models\Db,
	Bang\Modules\Account\Models\Mail,
    Bang\Helper;

class settingsController extends \Bang\SuperController implements \Bang\ControllerInterface
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
    }
    
    /**
     * Save current users settings
     */
    public function processSaveSetingsAction()
    {
    	$params = Helper::getRequestParams('post');
    	 
    	if(is_array($params) && count($params) && isset($params['language'])) {
		    
    		if($this->Data->updateUserSettings($params) === true) {
    			
    			$this->Session->setSuccess($this->Lang->get('account_settings_form_success'));
    			
    			// set the language and timezone to the new updated settings.
    			$this->Session->setToUser('timezone', $params['timezone']);
    			$this->Session->setToUser('lang', $params['language']);
    			$this->View->setLoadedLanguage($params['language']);
    			
    			Helper::redirectTo('/account/settings/index/');
    			exit;
    			 
    		} else {
    			// error should be set already
    			Helper::redirectTo('/account/settings/index/');
    			exit;
    		}
    	
    	} else {
    	
    		$this->Session->setSuccess($this->Lang->get('account_settings_form_no_data'));
    		Helper::redirectTo('/account/settings/index/');
    		exit;
    	}
    	 
    	return false;
    }
    
    /**
     * Index Action - dont want to have one here
     */
    public function indexAction() {
    	
    	$template = '';
    	
    	
    	$tzlist = \Bang\Helper::getTiemzoneList();
    	$this->View->setTplVar('zone', $tzlist);
    	
    	$current = $this->Session->getUser();
    	
    	if(is_array($current) && count($current)) {
    		if(isset($current['lang'])) { 
    			$this->View->setTplVar('language', $current['lang']);
    		}
    		if(isset($current['timezone'])) {
    			$this->View->setTplVar('timezone', $current['timezone']);
    		}
    	}
    	
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'settings.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'settings.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'settings.php');
    	}
    	
    	// main template
    	$this->View->setModuleTpl('settings', $template);
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
