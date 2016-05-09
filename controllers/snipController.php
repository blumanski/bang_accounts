<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-03-20
 *
 * SnipController
 * The snip controller is returning rendered snippets of code
 * This can be any kind of content
 * The snippets can get called via view and from any template.
 * 
 */

Namespace Bang\Modules\Account;

Use Bang\Modules\Account\Models\Db,
	Bang\Modules\Account\Models\Mail,
    Bang\Helper;

class snipController extends \Bang\SuperController
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

    	// create module data model instance
    	
    	$this->Data	= new Db($di);
    	$this->Mail	= new Mail($di);
    	$this->Data->setMailInstance($this->Mail);
    }
    
    /**
     * Return login template
     * 1. Load login template
     * 2. return template
     */
    public function loginAction()
    {
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login.php')) {
    		return $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login.php');
    	} else {
    		return $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login.php');
    	}
    }
    
    /**
     * Forgot password action
     * 1. Load template
     * 2. return it
     * @note Including the google recapctha validation
     */
    public function forgotAction()
    {
    	// 1.
    	$this->View->addScript('https://www.google.com/recaptcha/api.js', 1);
    	$this->View->setTplVar('sitekey', CONFIG['recaptcha']['sitekey']);
    
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'forgot.php')) {
    		return $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'forgot.php');
    	} else {
    		return $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'forgot.php');
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
