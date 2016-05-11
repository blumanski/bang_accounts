<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * User controller class for module account
 * 
 * The class contains action classes for account administration
 * 
 * 1. Add New User
 * 2. Edit User
 * 3. User Listing
 * 4. Add Permission Group
 * 5. List Permission Groups
 * 6. Edit Permission Group
 * 
 */

Namespace Bang\Modules\Account;

Use Bang\Modules\Account\Models\Db,
	Bang\Modules\Account\Models\Mail,
    Bang\Helper;

class userController extends \Bang\SuperController implements \Bang\ControllerInterface
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
    
    // ------------------ User Listing -----------------------------------------
    // -------------------------------------------------------------------------
    /**
     * -> List users
     * -> Deactivate Users
     * -> Activate Users
     */
    
    /**
     * Index Action - the default entry point to this controller
     * An API may uses more explecit methods to let users access the data
     * @see \Bang\controllerInterface::indexAction()
     */
    public function indexAction()
    {
    	// test permissions
    	$this->testPermisions();
    	
        $accounts = $this->Data->getUserAccounts();
        
    	$this->View->addStyle($this->View->TemplatePath.'js/plugins/data-tables/css/jquery.dataTables.css', 1);
    	$this->View->addScript($this->View->TemplatePath.'js/plugins/data-tables/js/jquery.dataTables.min.js', 1);
    	
    	if(!is_array($accounts) || !count($accounts)) {
    		
    		$accounts = array();
    		$this->Session->setWarning($this->Lang->get('account_listing_no_data_found'));
    		
    	}
    	
    	$this->View->setTplVar('accounts', $accounts);
    	
    	$template = '';
    	
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'accountlist.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'accountlist.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'accountlist.php');
    	}
    	 
    	// main template
    	$this->View->setModuleTpl('accountlist', $template);
    }
    
    /**
     * Reactivate user
     */
    public function activateUserAction()
    {
    	$this->testPermisions();
    
    	$params = Helper::getRequestParams('get');
    
    	if(is_array($params) && count($params)) {
    		if(isset($params['userid']) && (int)$params['userid'] > 0) {
    			if($this->Data->reactivateUser($params['userid']) === true) {
    				$this->Session->setSuccess($this->Lang->get('account_user_activate_success_message'));
    				Helper::redirectTo('/account/user/index/');
    				exit;
    			}
    		}
    	}
    
    	$this->Session->setError($this->Lang->get('account_user_activate_error_message'));
    	Helper::redirectTo('/account/user/index/');
    	exit;
    }
    
    /**
     * Delete/Remove/Deactivate a user
     * The user is not deleted but set to deleted.
     * The account could get reactivated later on
     * This will also save who deleted the user
     */
    public function deleteUserAction()
    {
    	$this->testPermisions();
    	 
    	$params = Helper::getRequestParams('get');
    	 
    	if(is_array($params) && count($params)) {
    		if(isset($params['userid']) && (int)$params['userid'] > 0) {
    			if($this->Data->deleteUser($params['userid']) === true) {
    				$this->Session->setSuccess($this->Lang->get('account_user_delete_success_message'));
    				Helper::redirectTo('/account/user/index/');
    				exit;
    			}
    		}
    	}
    	 
    	$this->Session->setError($this->Lang->get('account_user_delete_error_message'));
    	Helper::redirectTo('/account/user/index/');
    	exit;
    }
    
    
    // ------------------ Edit User  -------------------------------------------
    // -------------------------------------------------------------------------
    
	/**
	 * Process the user edit form data
	 */    
    public function processUserBaseFormAction()
    {
    	// 1. user has to be logged in and the right user/group permissions
    	$this->testPermisions();
    	// 2.
    	$params = Helper::getRequestParams('post');

    	// post is coming through
    	if(is_array($params) && count($params) && isset($params['userid'])) {
    		 
    		if($this->Data->updateUserBaseInformation($params) === true) {
    			 
    			$this->Session->setSuccess($this->Lang->get('account_user_edit_form_base_success'));
    			Helper::redirectTo('/account/user/index/');
    			exit;
    
    		} else {
    
    			//$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_savefail'));
    			Helper::redirectTo('/account/user/edituser/userid/'.(int)$params['userid'].'/');
    			exit;
    		}
    		 
    	} else {
    
    		$this->Session->setSuccess($this->Lang->get('account_user_edit_form_base_error_nodatagiven'));
    		Helper::redirectTo('/account/user/index/');
    		exit;
    
    	}
    
    	return false;
    }
    
    /**
     * Load edit user form
     * Load the edit user form
     * If post data available process post data
     * 1. test permission
     * 2. get post and get params
     * 3. test for userid
     * 4. get userdata or set error
     * 5. get available user permissions and user groups
     * 6. Load template
     */
    public function edituserAction()
    {
    	// 1. user has to be logged in and the right user/group permissions
    	$this->testPermisions();
    	// 2.
    	$get 	= Helper::getRequestParams('get');
    	$user	= array();
    	 
    	// 3.
    	if(is_array($get) && isset($get['userid']) && (int)$get['userid'] > 0) {
    		// 4.
    		$user = $this->Data->getUserAccountById((int)$get['userid']);
    
    		if($user !== false) {
    			 
    			$this->View->setTplVar('user', $user);
    			 
    		} else {
    			 
    			$this->Session->setError($this->Lang->get('account_user_edit_form_no_userdata'));
    		}
    
    	} else {
    
    		$this->Session->setError($this->Lang->get('account_user_edit_form_no_userid'));
    	}
    	 
    	// 5.
    	$groups = $this->Data->getPermissionGroups();
    	$perms	= $this->Data->getPermissionOptions();
    	 
    	$this->View->setTplVar('groups', $groups);
    	$this->View->setTplVar('perms', $perms);
    	 
    	// 6.
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'edituser.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'edituser.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'edituser.php');
    	}
    
    	// main template
    	$this->View->setModuleTpl('edituser', $template);
    }
    
    
    // ------------------ Add User ---------------------------------------------
    // -------------------------------------------------------------------------
    
    /**
     * Process user create form data
     */
    public function processUserCreateAction()
    {
    	// 1. user has to be logged in and the right user/group permissions
    	$this->testPermisions();
    	// 2.
    	$params = Helper::getRequestParams('post');
    	 
    	// post is coming through
    	if(is_array($params) && count($params)) {
    
    		// add params to the session to refill form on error an reload
    		$this->Session->setPostData($params);
    		 
    		if($this->Data->addNewUser($params) === true) {
    			 
    			// clear the form date from session
    			$this->Session->clearFormData();
    			 
    			$this->Session->setSuccess($this->Lang->get('account_user_edit_form_base_success'));
    			Helper::redirectTo('/account/user/index/');
    			exit;
    			 
    		} else {
    			 
    			Helper::redirectTo('/account/user/adduser/');
    			exit;
    		}
    		 
    	} else {
    		 
    		$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_nodatagiven'));
    		Helper::redirectTo('/account/user/adduser/');
    		exit;
    		 
    	}
    	 
    	return false;
    }
    
    /**
     * Load add user form
     */
    public function adduserAction()
    {
    	// test permissions
    	$this->testPermisions();
    	 
    	$groups = $this->Data->getPermissionGroups();
    	$perms	= $this->Data->getPermissionOptions();
    	 
    	$this->View->setTplVar('groups', $groups);
    	$this->View->setTplVar('perms', $perms);
    	 
    	$template = '';
    	 
    	if(file_exists($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'createuser.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'account'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'createuser.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'createuser.php');
    	}
    	 
    	// main template
    	$this->View->setModuleTpl('createuser', $template);
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
