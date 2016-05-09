<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Group controller class for module account
 * 
 * The class contains action classes for account administration
 * 
 * 1. Add New Group
 * 2. List groups
 * 3. Delete Group
 * 4. Edit Group
 * 
 */

Namespace Bang\Modules\Account;

Use Bang\Modules\Account\Models\Db,
	Bang\Modules\Account\Models\Mail,
    Bang\Helper;

class groupController extends \Bang\SuperController implements \Bang\ControllerInterface
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

		// All action methods need a logged in user
    	$this->testPermisions();
    	
    	$this->Data	= new Db($di);
    	$this->Mail	= new Mail($di);
    	$this->Data->setMailInstance($this->Mail);
    }
    
    /**
     * List all groups
     */
    public function indexAction()
    {
    	$groups = $this->Data->getPermissionGroups();
    	
    	$this->View->addStyle($this->View->TemplatePath.'js/plugins/data-tables/css/jquery.dataTables.min.css', 1);
    	$this->View->addScript($this->View->TemplatePath.'js/plugins/data-tables/js/jquery.dataTables.min.js', 1);
    	
    	if(!is_array($groups) || !count($groups)) {
    	
    		$groups = array();
    		$this->Session->setWarning($this->Lang->get('account_listing_no_data_found'));
    	}
    	
    	$this->View->setTplVar('groups', $groups);
    	
    	$template = '';
    	
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'grouplist.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'grouplist.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'grouplist.php');
    	}
    	
    	// main template
    	$this->View->setModuleTpl('grouplist', $template);
    }
    
    /**
     * Load edit group form
     */
    public function editGroupAction()
    {
    	$params = Helper::getRequestParams('get');
    	
    	if(is_array($params) && count($params) && isset($params['groupid']) && (int)$params['groupid'] > 0) {
    	
    		$group = $this->Data->getGroupDataById((int)$params['groupid']);
    		
    		if(is_array($group) && count($group)) {
    			
    			$perms	= $this->Data->getPermissionOptions();
    			$this->View->setTplVar('perms', $perms);
    			
    			$this->View->setTplVar('group', $group);
    			 
    			$template = '';
    			
    			if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'editgroup.php')) {
    				$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'editgroup.php');
    			} else {
    				$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'editgroup.php');
    			}
    			
    			// main template
    			$this->View->setModuleTpl('editgroup', $template);
    			
    		} else {
    			
    			$this->Session->setSuccess($this->Lang->get('account_group_edit_error_nodata'));
    			Helper::redirectTo('/account/group/index/');
    		}
    			
    	} else {
    		
    		$this->Session->setSuccess($this->Lang->get('account_group_edit_error_noid'));
    		Helper::redirectTo('/account/group/index/');
    	}
    	
    }

    /**
     * Add New Group Form
     */
    public function newGroupAction()
    {
    	$perms	= $this->Data->getPermissionOptions();
    	$this->View->setTplVar('perms', $perms);
    	
    	$template = '';
    	
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'creategroup.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'creategroup.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'creategroup.php');
    	}
    	
    	// main template
    	$this->View->setModuleTpl('creategroup', $template);
    }
    
    /**
     * Process edit group form
     */
    public function processEditGroupFormAction()
    {
    	$params = Helper::getRequestParams('post');
    	
    	if(is_array($params) && count($params) && isset($params['groupid'])) {
			
    		if($this->Data->updatePermissionGroup($params) === true) {
    			
    			$this->Session->setSuccess($this->Lang->get('account_group_edit_success'));
    			Helper::redirectTo('/account/group/index/');
    			exit;
    			
    		} else {
    			// error should be set already
    			Helper::redirectTo('/account/group/editgroup/groupid/'.$params['groupid'].'/');
    			exit;
    		}
    		
    		
    	} else {
    		
    		$this->Session->setSuccess($this->Lang->get('account_group_edit_error_noid'));
    		Helper::redirectTo('/account/group/index/');
    		exit;
    	}
    	
    	return false;
    }
    
    /**
     * Process creeate group form data
     */
    public function processAddGroupAction()
    {
    	$params = Helper::getRequestParams('post');
    	
    	// post is coming through
    	if(is_array($params) && count($params) && isset($params['name'])) {
    		 
    		if($this->Data->addNewPermissionGroup($params) === true) {
    	
    			$this->Session->setSuccess($this->Lang->get('account_user_edit_form_base_success'));
    			Helper::redirectTo('/account/group/index/');
    			exit;
    	
    		} else {
    	
    			Helper::redirectTo('/account/group/editgroup/userid/'.(int)$params['userid'].'/');
    			exit;
    		}
    		 
    	} else {
    	
    		$this->Session->setSuccess($this->Lang->get('account_user_edit_form_base_error_nodatagiven'));
    		Helper::redirectTo('/account/group/index/');
    		exit;
    	}
    	
    	return false;
    	
    }
    
    /**
     * Delete group
     */
    public function deleteGroupAction()
    {
    	$params = Helper::getRequestParams('get');
    	 
    	if(is_array($params) && count($params) && isset($params['groupid']) && (int)$params['groupid'] > 0) {
    			
    		if($this->Data->deletePermissionGroup((int)$params['groupid']) === true) {
    			 
    			$this->Session->setSuccess($this->Lang->get('account_group_delete_success'));
    			Helper::redirectTo('/account/group/index/');
    			exit;
    			 
    		} else {
    			// error should be set already
    			Helper::redirectTo('/account/group/index/groupid/'.$params['groupid'].'/');
    			exit;
    		}
    	
    	} else {
    	
    		$this->Session->setSuccess($this->Lang->get('account_group_delete_fail'));
    		Helper::redirectTo('/account/group/index/');
    		exit;
    	}
    	 
    	return false;
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
