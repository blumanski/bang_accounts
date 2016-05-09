<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * Account Module ->  Data Model
 */

Namespace Bang\Modules\Account\Models;

Use \Bang\PdoWrapper, PDO, \Bang\Helper, Bang\Modules\Account\Models\Mail;

class Db extends \Bang\SuperModel
{
    /**
     * PDO
     * @var object
     */
    private $PDO;
    
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
     * instance of language object
     * @var object
     */
    private $Lang;
    
    /**
     * Mail instance
     * @var object
     */
    private $Mail;
    
    /**
     * Contains an array with the current users data
     * @var array
     */
    private $User = array();
    
    /**
     * Set up the db model
     * @param object $di
     */
    public function __construct(\stdClass $di)
    {
        $this->PdoWrapper      	= $di->PdoWrapper;
        $this->ErrorLog 		= $di->ErrorLog;
        $this->Session	 		= $di->Session;
        $this->View				= $di->View;
        $this->Lang				= $di->View->Lang;
        
        $this->User				= $this->Session->getUser();
    }
    
    /**
     * Set mail instance
     * @param Bang\Modules\Account\Models $Mail
     */
    public function setMailInstance(\Bang\Modules\Account\Models\Mail $Mail)
    {
    	$this->Mail = $Mail;
    }
    
    /**
     * Create a password hash
     * @param string $string
     * @return string
     */
    private function hashPassword(string $string)
    {
    	// PASSWORD_DEFAULT - Use the bcrypt algorithm (default as of PHP 5.5.0).
    	return password_hash($string, PASSWORD_DEFAULT, CONFIG['app']['passwordoptions']);
    }
    
    /**
     * Validate form parameters for reset password form
     * 1. test email
     * 2. test if email is in system
     * 3. return or false
     * @param $param
     */
    private function passwordResetCodeFormValidation(string $email) : bool
    {	
    	// 1.
    	if(!empty($email)) {
    	
    		if(Helper::validate($email, 'email', 150) === true) {
    			// 2.
    			if($this->emailExist($email) === true) {
    				
    				// 3.
    				return true;
    	
    			} else {
    				
    				$this->Session->setError($this->Lang->get('account_forgot_form_reset_email_notexist'));
    			}
    	
    		} else {
    			
    			$this->Session->setError($this->Lang->get('account_forgot_form_reset_email_invalid'));
    		}
    	
    	} else {
    		
    		$this->Session->setError($this->Lang->get('account_forgot_form_reset_email_invalid'));
    	}
    	
    	// 3.
    	return false;
    }
    
    /**
     * Send the reset code for the password reset
     * 1. Run form parameter validation
     * 2. Get user data and generate code
     * 3. Use transaction to update the code and send the notification email
     * 4. return bool
     * @param string $email
     */
    public function sendPasswordResetCode(string $email) : bool
    {
    	// 1.
        if($this->passwordResetCodeFormValidation($email) === true) {
        	
        	// 2.
        	$userData  = $this->getUserByEmail($email);
        	$code      = Helper::generateCode(34);
        	
        	// don't like to debug nested transactions...
        	if($this->PdoWrapper->inTransaction() !== true) {
        		 
        		// 3. begin a db transaction
        		$this->PdoWrapper->beginTransaction();
        		
        		// add code to users table
        		if($this->addResetCodeToUser($userData, $code) === true) {
        		
        			// I log this as security message
        			$ip = Helper::getClientIp();
        			$message = 'A password reset code was requested for userid: '.(int)$userData['id'].', account id: '.(int)$userData['accountid'].'
        	    	from IP: '.$ip.' at: '.date('d/m/Y H:i:s').', email address: '.$userData['email'];
        			$this->ErrorLog->logError('security', $message, __METHOD__ .' - Line: '. __LINE__);
        			// log finished
        			 
        			// send email and return outcome
        			if($this->Mail->dispatchResetMail($userData, $code) === true) {
        				
        				// mail was send, commit and return true
        				$this->PdoWrapper->commit();
        				// 4.
        				return true;
        				
        			} else {
        				// mail wasn't send, rollBack
        				$this->PdoWrapper->rollBack();
        			}
        			
        		} else { // addResetCodeToUser failed
        			
        			$this->PdoWrapper->rollBack();
        		}
        		
        	} // end transaction
        }
        
        // 4.
        return false;
    }
    
    /**
     * Validate reset form parameters
     * 1. Test parameters and set error messages if needed
     * 2. Test if the code exists in the database and return userid
     * 3. return userid or false
     * @param array $data
     */
    private function validatePasswordResetForm(array $data)
    {
    	// 1.
        if(is_array($data) && count($data)) {
            // 2.
            if(isset($data['code']) && !empty($data['code']) && mb_strlen($data['code']) == 34) {

            	if(isset($data['pwd']) && !empty($data['pwd'])) {
            	
	                if(Helper::validate($data['pwd'], 'password', 30) === true) {
	                    
	                    if(isset($data['pwd2']) && $data['pwd2'] === $data['pwd']) {
	                        // 3.
	                        $userid = $this->codeExists($data['code']);
	                        
	                        if($userid !== false && (int)$userid > 0) {
	                            
	                            // 3. past validation
	                            return $userid;
	                            
	                        } else {
	                            
	                            $this->Session->setError($this->Lang->get('account_reset_password_form_error_nocodefound'));
	                        }
	                        
	                    } else {
	                        
	                        $this->Session->setError($this->Lang->get('account_reset_password_form_error_noconfirm'));
	                    }
	                    
	                } else {
	                	
	                	$this->Session->setError($this->Lang->get('account_reset_password_form_weak_weak_password'));
	                }
                    
                } else {
                    
                    $this->Session->setError($this->Lang->get('account_reset_password_form_error_nodpwd'));
                }
                
            } else {
                
                $this->Session->setError($this->Lang->get('account_reset_password_form_error_nodcode'));
            }
            
        } else {
            
            $this->Session->setError($this->Lang->get('account_reset_password_form_error_nodata'));
        }
        
        // 3.
        return false;
    }
    
    /**
     * Test if a code exist and return the id
     * 1. Set an expiry date
     * 2. Get data and return
     * @param string $code
     * @return mixed boolean || int
     */
    private function codeExists(string $code)
    {
        // 1. code is only valif for 24 hours
        $dateAge = date('Y-m-d H:i:s', strtotime("-24 hours"));
        //2 .
        $query = "SELECT `id` 
                    FROM
                        `".$this->addTable('users')."`
                    WHERE 
                        `resetcode` = :resetcode
                    AND 
                        `resetrequest` > :dateage
        ";
        
        try {

            $this->PdoWrapper->prepare($query);
            $this->PdoWrapper->bindValue(':resetcode', $code, PDO::PARAM_STR);
            $this->PdoWrapper->bindValue(':dateage', $dateAge, PDO::PARAM_STR);
            $this->PdoWrapper->execute();
             
            $result = $this->PdoWrapper->fetchAssoc();

            if(is_array($result) && isset($result['id'])) {
                return $result['id'];
            }
             
        } catch (\PDOException $e) {
             
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            $this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
        }
         
        return false;
    }
    
    /**
     * Update password after password reset
     * 1. Validate change password form
     * 2. Start transaction to update user and send mail
     * 3. Commit or rollback
     * @param array $data
     */
    public function resetAction(array $data) : bool
    {
		// 1. validate form params, this will return false or the userid
		$userid = $this->validatePasswordResetForm($data);
       
		if($userid !== false) {
           
			if($this->PdoWrapper->inTransaction() !== true) {
				// 2. begin a db transaction
				$this->PdoWrapper->beginTransaction();
				// reset reset code and new password
				$query = "UPDATE `".$this->addTable('users')."`
							SET
								`resetcode` = '',
								`pwd`  = :newpwd
							WHERE
								`id` = :userid
				";
                
				try {
               
				$this->PdoWrapper->prepare($query);
				$this->PdoWrapper->bindValue(':newpwd', $this->hashPassword($data['pwd']), PDO::PARAM_STR);
				$this->PdoWrapper->bindValue(':userid', $userid, PDO::PARAM_INT);
				$this->PdoWrapper->execute();

				// Make sure we know the outcome of the query
				if($this->PdoWrapper->rowCount() > 0) {
					$user = $this->getUserById($userid);
                       
					// if the mail was send, commit the data, otherwise we need to roll back
					if($this->Mail->sendPasswordChangeNotification($user) === true) {
						// 3.
						$this->PdoWrapper->commit();
							return true;
						}
					} 
                   
					// 3. if i get here, roll back, as it should bomb out after commit otherwise
					$this->PdoWrapper->rollBack();
					
				} catch (\PDOException $e) {
					$message = $e->getMessage();
					$message .= $e->getTraceAsString();
					$message .= $e->getCode();
					$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
				}
			}
		}
		
		return false;
	}
    
    /**
     * Add the reset code to the users data set
     * 1. No user id, bomb out
     * 2. query and return bool
     * @param array $userData
     * @param string $code
     * @return boolean 
     */
    private function addResetCodeToUser(array $userData, string $code) : bool
    {
		// 1. not the right data available, bomb out
        if(!isset($userData['id']) || empty($code)){
            return false;
        }
        
        $query = "UPDATE `".$this->addTable('users')."`
                    SET `resetcode` = :code,
                        `resetrequest` = :date
                  WHERE 
                    `id` = :id
        ";
        
        try {
             
        	// 2.
            $this->PdoWrapper->prepare($query);
            $this->PdoWrapper->bindValue(':code', $code, PDO::PARAM_STR);
            $this->PdoWrapper->bindValue(':date', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $this->PdoWrapper->bindValue(':id', $userData['id'], PDO::PARAM_INT);
            $this->PdoWrapper->execute();
            
            // Make sure we know the outcome of the query
            if($this->PdoWrapper->rowCount() > 0) {
                return true;
            }
             
        } catch (\PDOException $e) {
             
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            $this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
        }
        
        // 2.
        return false;
    }
    
    /**
     * Get a user by userid
     * @param int $userid
     * @return mixed bool|array
     */
    private function getUserById(int $id)
    {
        $query = "SELECT
    				`username`,
    				`email`,
    				`accountid`,
    	            `id`
    
    				FROM `".$this->addTable('users')."`
    
    			  WHERE
    				`id` = :id
    	";
         
        try {
             
            $this->PdoWrapper->prepare($query);
            $this->PdoWrapper->bindValue(':id', $id, PDO::PARAM_INT);
            $this->PdoWrapper->execute();
             
            $result = $this->PdoWrapper->fetchAssoc();
             
            if(is_array($result) && count($result)) {
                return $result;
            }
             
        } catch (\PDOException $e) {
             
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            $this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
        }
         
        return false;
    }
    
    /**
     * Get a user by email
     * @param String $email
     * @return mixed bool|array
     */
    private function getUserByEmail(string $email)
    {
    	$query = "SELECT 
    				`username`,
    				`email`,
    				`accountid`,
    	            `id`
    				
    				FROM `".$this->addTable('users')."`
    						
    			  WHERE 
    				`email` = :email 
    			  LIMIT 1
    	";
    	
    	try {
    	
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':email', $email, PDO::PARAM_STR);
    		$this->PdoWrapper->execute();
    	
    		$result = $this->PdoWrapper->fetchAssoc();
    	
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    	
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Create the new user, add data to db
     * 1. Test if the method is called inside a transaction otehrwise bomb out and log error
     * 2. Insert user data
     * 3. return userid
     * @param array $params
     * @param int $accountid
     */
    private function createUser(array $params, int $accountid, string $code)
    {
		// 1. test if we are in a transaction, otherwise bomb out
		if($this->PdoWrapper->inTransaction() !== true){
    		$this->ErrorLog->logError('App', 
    		    'Create user withoud being in an db transaction. Accountid -> '.
    		    (int)$accountid, __METHOD__ .' - Line: '. __LINE__);
    		return false;	
    	}
    	
    	// The password is only set as placeholder.
    	// The user will get an email and set up his own password
    	// No administrator will ever see a password and no password
    	// will ever get send via email.
    	$password	= $this->hashPassword(Helper::generateCode(10));
    	
    	// 2.
    	$query = "INSERT INTO
    				`".$this->addTable('users')."`
    				(`username`, `pwd`, `email`, `accountid`, `created`, `resetcode`, `resetrequest`)
    			  VALUES
    				(:username, :pwd, :email, :accountid, :created, :resetcode, :resetrequest)
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':username', 	$params['username'], PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':pwd', 		$password, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':email', 		$params['email'], PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':resetcode', 	$code, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':created',	date('Y-m-d H:i:s'), PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':accountid', 	(int)$accountid, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':resetrequest',date('Y-m-d H:i:s'), PDO::PARAM_STR);
    		 
    		$this->PdoWrapper->execute();
    	
    		if($this->PdoWrapper->rowCount() > 0) {
    			// 3.
    			return $this->PdoWrapper->lastInsertId();
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	// 3.
    	return false;
    }
    
    /**
     * This will create an account which the new user will get asigned to.
     * Id it this way as I need at a later stage multi user accounts where 
     * multiple user can be assigned to multiple accounts.
     */
    private function createAccount(array $params)
    {
    	$query = "INSERT INTO 
    				`".$this->addTable('accounts')."`
    				(`name`, `created`)
    			  VALUES
    				(:name, :created)
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':name', 		$params['username'], PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':created',	date('Y-m-d H:i:s'), PDO::PARAM_STR);
    		 
    		$this->PdoWrapper->execute();
		
    		if($this->PdoWrapper->rowCount() > 0) {
    			return $this->PdoWrapper->lastInsertId();
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    
    /**
     * Test if an email address is already in the system
     * If a userid is given, the query will except that userid
     * @param string $username
     * @param int $userid
     */
    public function usernameExist(string $username, int $userid = 0) : bool
    {
    	$withid = '';
    	
    	if((int)$userid > 0) {
    		$withid = " AND `id` != :userid";
    	}
    	
    	$query = "SELECT `username` FROM
    				`".$this->addTable('users')."`
    				WHERE
    					`username` = :username
    				".$withid."
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':username', $username, PDO::PARAM_STR);
    		
    		if($withid != '') {
    			$this->PdoWrapper->bindValue(':userid', $userid, PDO::PARAM_INT);
    		}
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssoc();
    
    		if(is_array($result) && isset($result['username'])) {
    			return true;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    
    	return false;
    }
    
    /**
     * Test if an email address is already in the system
     * @param string $email
     */
    public function emailExist(string $email, int $userid = 0) : bool
    {
    	$widthid = '';
    	
    	if((int)$userid > 0) {
    		$widthid = " AND `id` != :userid";
    	}
    	
    	$query = "SELECT `email` FROM 
    				`".$this->addTable('users')."`
    				WHERE 
    					`email` = :email
    				".$widthid."
    	";
    	
    	try {
    	
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':email', $email, PDO::PARAM_STR);
    		
    		if((int)$userid > 0) {
    			$this->PdoWrapper->bindValue(':userid', $userid, PDO::PARAM_INT);
    		}
    	
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssoc();
    		
    		if(is_array($result) && isset($result['email']) && $result['email'] == $email) {
    			return true;
    		}
    	
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	
    	}
    	 
    	return false;
    }
    
    /**
     * Test a users credentials and login
     *  1. Test parameters
     *  2. Get userdata by username
     *  3. Verify password
     *  4. if needed, rehash password (after may php changes the algorithm)
     *  5. Add user to session
     *  6. return bool
     * @param array $params
     */
    public function login(array $params)
    {
        // 1. All validation is kept as close as possible to the endpoint
        if(is_array($params) && isset($params['username']) && !empty($params['username'])) {
            
        	if(!isset($params['fingerprint'])) {
        		$params['fingerprint'] = '';
        	}
        	
            // test if the password is given and not empty
            if(isset($params['pwd']) && !empty($params['pwd'])) {
                
            	// 2.
                $query = "SELECT
                            users.`id`, 
                			users.`username`, 
                			users.`email`, 
                			users.`mobile`, 
                			users.`pwd`, 
                			users.`accountid`,
                			profile.`set_language`,
                			profile.`timezone`,
                			profile.`firstname`,
                			profile.`lastname`,
                			profile.`avatar`

                		  FROM `".$this->addTable('users')."` `users`
                		  		LEFT JOIN `".$this->addTable('user_profile')."` `profile`
                		  			ON users.`id` = profile.`userid`
                            
                          WHERE
                              (`email` = :username OR `username` = :username)
                          AND
                              `deleted` != 1
                ";
                            
                $this->PdoWrapper->prepare($query);
                $this->PdoWrapper->bindValue(':username', $params['username'], PDO::PARAM_STR);
	    		$this->PdoWrapper->execute();
	    		
	    		$result = $this->PdoWrapper->fetchAssoc();
	    		
	    		// we have a result
				if(is_array($result) && count($result)) {
					
					$perms = false;
					// get user permission groups
					$perms = $this->getUserGroups($result['id']);
					
					// test if the user has allowance to log into the backend
					// permission $perms['1'] -> backendLogin
					if(is_array($perms) && count($perms) && isset($perms['allPermissions']) && isset($perms['allPermissions'][1])) {
						
						// 3. verify the password
						if (password_verify($params['pwd'], $result['pwd']) === true) {
						
							// 4. if the internal password options have changed, such as a new default algorythm standard of the php version or
							// the change of cost.
							// very rare that this is happening.
							if (password_needs_rehash($result['pwd'], PASSWORD_DEFAULT, CONFIG['app']['passwordoptions'])) {
								// If so, create a new hash, and replace the old one
								$newHash = $this->hashPassword($params['pwd']);
								$this->saveRegeneratedHash($newHash, $result['id']);
								$this->ErrorLog->logError('Security', 'Password hash was regenerated (option change) for userid '.$result['id'], __METHOD__ .' - Line: '. __LINE__);
							}
								
							// 5. set user data to session
							if($this->setUserToSession($result, $params['fingerprint'], $perms) === true) {
								// 6.
								return true;
						
							} else {
						
								// In this case, that would be a group/permission issue which i want to log
								$this->ErrorLog->logError('App', 'Could not assign a permission group to the user with id '.(int)$result['id'], __METHOD__ .' - Line: '. __LINE__);
							}
						
						} else {
								
							$this->Session->setError($this->Lang->get('account_login_failed_error'));
						}
						
					} else {
						
						$this->Session->setError($this->Lang->get('account_login_failed_no_permissions'));
					}
                        
				} else {
				    
				    $this->Session->setError($this->Lang->get('account_login_failed_error'));
				}
				
			} else {
			    
			    $this->Session->setError($this->Lang->get('account_login_password_error'));
			}
			
		} else {
		    
		    $this->Session->setError($this->Lang->get('account_login_username_error'));
		}
        
		// 7.
		return false;
    }
    
    /**
     * If the password settings have changed and the password hash was
     * newly generated, the new hash has to get saved.
     * This is not a password change but a internal password hash change which will
     * trigger this.
     * @param string $hash
     * @param itn $userid
     */
    private function saveRegeneratedHash(string $hash, int $userid)
    {
    	$query = "UPDATE `".$this->addTable('users')."`
    				SET `pwd` = :pwd
    			  WHERE 
    				`id` = :userid
    	";
    	
    	try {
    		
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':pwd', (string)$hash, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
    		
    		$this->PdoWrapper->execute();
    		
    		if($this->PdoWrapper->rowCount() > 0) {
    			return true;
    		}
    		
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);

    	}
    	
    	return false;
    }
    
    /**
     * Get the users permission before setting to session
     * @param int $userid
     */
    private function getUserGroups($userid)
    {
    	$query = "SELECT 
    				perms.`groupid` AS `groupid`,
    				groups.`name` 	AS `name`,
    				
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_group_assigned')."` 
    					WHERE `groupid` = perms.`groupid` ) AS `groupPermIds`,
    						
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_user_assigned')."` 
    					WHERE `userid` = :userid ) AS `userPermIds`
    			
    				FROM 
    					`".$this->addTable('permissions_user_groups_assigned')."` perms
    					LEFT JOIN 
    						`".$this->addTable('permission_groups')."` groups
    							ON perms.`groupid` = groups.`id`
    				WHERE 
    					perms.`userid` = :userid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':userid', $userid, PDO::PARAM_INT);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssocList();
    	
    		if(is_array($result) && count($result)) {
    			// Declare and cast new key
    			$result['allPermissions'] = array();
    			// Merge permissions
    			$result = $this->combinePermissions($result);
    			// Flip the array so that the permission ids are key
    			// As  like that I can use isset or array_key_exists instead of in_array
    			$result['allPermissions'] = array_flip($result['allPermissions']);
    			
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Combine all permissions into one flat array
     * It takes the group permissions and user permissions and 
     * merges those to an flat array.
     * @param array $result
     */
    private function combinePermissions($result)
    {
    	if(is_array($result) && count($result)) {
	    	foreach($result AS $key => $value) {
	    		// loop through and add the permission ids as array
	    		if(is_array($value) && count($value)) {
	    				
	    			if(isset($value['groupPermIds']) && !empty($value['groupPermIds'])) {
	    				$result['allPermissions'] = array_unique(array_merge($result['allPermissions'], explode(',', $value['groupPermIds'])));
	    			}
	    				
	    			if(isset($value['userPermIds']) && !empty($value['userPermIds'])) {
	    				$result['allPermissions'] = array_unique(array_merge($result['allPermissions'], explode(',', $value['userPermIds'])));
	    			}
	    				
	    		}
	    	}
    	}
    	
    	return $result;
    }

    /**
     * Add the users data to the session
     */
    private function setUserToSession(array $user, $fingerprint, $perms) : bool
    {
    	if(is_array($perms) && count($perms)) {
    		
    		// if no language is set in the profile, use the config default language
    		if(empty($user['set_language'])) {
    			// set default language
    			$this->Session->setToUser('lang', CONFIG['app']['language']);
    			$this->View->setLoadedLanguage(CONFIG['app']['language']);
    			 
    		} else {
    			// language is set, test it
    			// is use in_array here but the array will may have max 10 entries anyway
    			if(in_array($user['set_language'], CONFIG['langwhitelist'])) {
    				// set userd set default language - on login
    				$this->Session->setToUser('lang', $user['set_language']);
    				$this->View->setLoadedLanguage($user['set_language']);
    			}
    		}
    		
    		// if no language is set in the profile, use the config default language
    		if(empty($user['timezone'])) {
    			// set default language
    			$this->Session->setToUser('timezone', CONFIG['app']['timezone']);
    		
    		} else {
    			 
    			$this->Session->setToUser('timezone', $user['timezone']);
    			date_default_timezone_set($user['timezone']);
    		}
    		
    		$this->Session->setToUser('id', $user['id']);
    		$this->Session->setToUser('username', $user['username']);
    		$this->Session->setToUser('accountid', $user['accountid']);
    		$this->Session->setToUser('email', $user['email']);
    		$this->Session->setToUser('mobile', $user['mobile']);
    		$this->Session->setToUser('session_start', date('Y-m-d H:i:s'));
    		$this->Session->setToUser('perms', $perms);
    		$this->Session->setToUser('fingerprint', $fingerprint);
    		$this->Session->setToUser('avatar', $user['avatar']);
    		
    		return true;
    	}
    	
    	return false;
    }
    
    /**
     * Assign the user to choosen individual system paermissions
     * @param array $params
     */
    private function assignIndividualPermissions(array $perms, int $userid) : bool
    {
    	$query = "INSERT INTO `".$this->addTable('permissions_user_assigned')."`
    				(`userid`, `permissionid`)
    			  VALUES 
    				(:userid, :permissionid)
    	";
    	
    	try {
    	
    		$this->PdoWrapper->prepare($query);
    			
    		foreach($perms AS $key => $value) {
    	
    			$this->PdoWrapper->bindValue(':permissionid', (int)$value, PDO::PARAM_INT);
    			$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
    			$this->PdoWrapper->execute();
    		}
    			
    		return true;
    	
    	} catch (\PDOException $e) {
    	
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	
    	}
    		
    	return false;
    }
    
    /**
     * Reset a users individual system permissions
     * This is happening usually before the permissions are newly set.
     * @param int $userid
     */
    private function resetUsersIndividualPermissions(int $userid) : bool
    {
    	$query = "DELETE FROM `".$this->addTable('permissions_user_assigned')."`
    				WHERE `userid` = :userid
    	";
    	
    	try {
    	
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
    	
    		return $this->PdoWrapper->execute();
    	
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Reactivate a user
     * @return boolean
     */
    public function reactivateUser(int $userid) : bool
    {
    	// can only done if permissions are right, it's the same test as for delete user
    	if($this->testPermissionsToDeleteUser($userid) === true) {
    	
	    	$query = "UPDATE `".$this->addTable('users')."` SET `deleted` = :deleted, `deletedby` = :deletedby WHERE `id` = :userid";
	    	
	    	try {
	    	
	    		$blame = $this->Session->getUserId();
	    	
	    		$this->PdoWrapper->prepare($query);
	    		$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
	    		$this->PdoWrapper->bindValue(':deletedby', (int)$blame, PDO::PARAM_INT);
	    		$this->PdoWrapper->bindValue(':deleted', 0, PDO::PARAM_INT);
	    		 
	    		$this->PdoWrapper->execute();
	    	
	    		if($this->PdoWrapper->rowCount() > 0) {
	    			$this->ErrorLog->logError('App', 'User with id: '.(int)$userid.' was reactivated by user with id: '.(int)$blame.'.', __METHOD__ .' - Line: '. __LINE__);
	    			return true;
	    		}
	    		 
	    	} catch (\PDOException $e) {
	    		 
	    		$message = $e->getMessage();
	    		$message .= $e->getTraceAsString();
	    		$message .= $e->getCode();
	    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
	    		 
	    	}
    	}
    	 
    	return false;
    }
    
    /**
     * Test if the current user is allowed to delete the user with given id
     * @param int $userid
     */
    private function testPermissionsToDeleteUser(int $userid) : bool
    {
    	$current = $this->Session->getUserId();
    	// 1. User with id 1 can't be deleted
    	if($userid == 1 && (int)$current != 1) {
    		$this->Session->setError($this->Lang->get('account_user_ans_delete_mega_user'));
    		return false;
    	}
    	
    	// 2. test the current users general permissions step by step
    	if(array_key_exists('perms', $this->User) && is_array($this->User['perms'])) {
    		
    		if(array_key_exists('allPermissions', $this->User['perms']) && is_array($this->User['perms']['allPermissions'])) {
    			// key 1 allows user to login into backend
    			// key 3 allows user to delete users
    			if(isset($this->User['perms']['allPermissions'][1]) && isset($this->User['perms']['allPermissions'][3])) {
    				// 2. test the user id groups
    				// A super administrator can only get deleted by another super administrator
    				// Get the permission groups of the user that is supposed to get deleted
    				$userToDeleteGroups = $this->getUserGroups((int)$userid);
    				
    			/** test for super administrators */
    				
    				// if true, user is in group superadmin and can only get deleted by another memeber of superadmins
    				if($this->isUserInGroupByGroupId($userToDeleteGroups, 1) === true) {
    					// test permissions to delete super user
    					
    					// if true, user is in group superadmin and can only get deleted by another member of superadmins
				    	// test if the current user is in the group superadmins
				    	if($this->isUserInGroupByGroupId($this->User['perms'], 1) === true) {
				    		return true;
				    	
				    	} else {
				    		// The current user is not in group superadmins
				    		// and can't delete the user as the user is in group superadmins
				    		// I want to log this attempt to delete a superadmin
				    		$this->ErrorLog->logError('Security', 'User with id '.(int)$this->User['id'].'
				    				tried to delete a super administrator.
				    				User with id '.(int)$this->User['id'].' tried to delete user with id:
				    				'.(int)$userid, __METHOD__ .' - Line: '. __LINE__);
				    	
				    			// set error message for display
				    			$this->Session->setError($this->Lang->get('account_user_delete_superadmin_failed'));
				    			
				    		return false;
				    	}
    				}
    				
    			/** test for admins */
    				
    				if($this->isUserInGroupByGroupId($userToDeleteGroups, 2) === true) {
    					// Only a super administrator can delete admin accounts
    					if($this->isUserInGroupByGroupId($this->User['perms'], 1) === true) {
    						 
    						return true;
    						 
    					} else {
    						// The current user is not in group superadmins
    						// and can't delete the user as the user is in group superadmins
    						// I want to log this attempt to delete a superadmin
    						$this->ErrorLog->logError('Security', 'User with id '.(int)$this->User['id'].'
			    				tried to delete a super administrator.
			    				User with id '.(int)$this->User['id'].' tried to delete user with id:
			    				'.(int)$userid, __METHOD__ .' - Line: '. __LINE__);
    						 
    						// set error message for display
    						$this->Session->setError($this->Lang->get('account_user_delete_superadmin_failed'));
    						
    						return false;
    					}
    				}
    				
    			/** other users */
    				// if it didn't bomb out yet, that means that the user is not a super administrator
    				// and not an admin, this user can get deleted by anybody with permissions to delete users
    				return true;
    				
    			}
    			
    		} else {
    			
    			$this->Session->setError($this->Lang->get('app_no_permissions'));
    		}
    		
    	} else {
    			
			$this->Session->setError($this->Lang->get('app_no_permissions'));
		}
    	
    	return false;
    }

    /**
     * Test if a user is in the group with given id
     * @param array $groups 
     * @param int $groupid
     */
    private function isUserInGroupByGroupId($groups, $groupid)
    {
    	if(is_array($groups) && count($groups) && (int)$groupid > 0) {
    		
    		foreach($groups AS $key => $value) {
    			if(isset($value['groupid']) && (int)$value['groupid'] == (int)$groupid) {
    				return true;
    			}
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Delete user from application
     * A user is never deleted, the user is just get a flag deleted and the delete date
     * @param int $userid
     */
    public function deleteUser($userid)
    {
    	// test permissions and delete
    	if($this->testPermissionsToDeleteUser($userid) === true) {
    		
    		$query = "UPDATE `".$this->addTable('users')."` SET `deleted` = :deleted, `deletedby` = :deletedby WHERE `id` = :userid";
    		
    		try {
    		
    			$blame = $this->Session->getUserId();
    		
    			$this->PdoWrapper->prepare($query);
    			$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
    			$this->PdoWrapper->bindValue(':deletedby', (int)$blame, PDO::PARAM_INT);
    			$this->PdoWrapper->bindValue(':deleted', 1, PDO::PARAM_INT);
    			 
    			$this->PdoWrapper->execute();
    		
    			if($this->PdoWrapper->rowCount() > 0) {
    				$this->ErrorLog->logError('App', 'User with id: '.(int)$userid.' was deletd by user with id: '.(int)$blame.'.', __METHOD__ .' - Line: '. __LINE__);
    				return true;
    			}
    			 
    		} catch (\PDOException $e) {
    			 
    			$message = $e->getMessage();
    			$message .= $e->getTraceAsString();
    			$message .= $e->getCode();
    			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    			 
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Test if a user has the right permissions to assign a new user to a particular permission group.
     * @Example An admin should not be allowed to create a super administrator
     * @param array $groups
     */
	private function userCanAssignUserToPermissionGroup(array $groups) : bool
	{
		// get the current users permission array from the session
		$currentPerms = $this->Session->getCurrentUsersPermisionsArray();
		
		if(is_array($groups) && count($groups)) {
			$groups = array_flip($groups);
			
			// This is a try to assign a new user to teh super administrator group
			if(isset($groups[1])) {
				// test the current users group, if superadministrator, no problem
				if($this->isUserInGroupByGroupId($currentPerms, 1) === true) {
					return true;
				}
			}
			
			// test admin group
			if(isset($groups[2])) {
				// test the current users group, if superadministrator, no problem
				if($this->isUserInGroupByGroupId($currentPerms, 2) === true || $this->isUserInGroupByGroupId($currentPerms, 1) === true) {
					return true;
				}
			}
			
			// 
			if(!isset($groups[1]) && !isset($groups[1]) && ( $this->Session->isMemberOfGroupWithId(2) === true || $this->Session->isMemberOfGroupWithId(1) === true) ){
				
				if($this->isUserInGroupByGroupId($currentPerms, 2) === true || $this->isUserInGroupByGroupId($currentPerms, 1) === true) {
					return true;
				}
			}
			
			// other groups are tested using the merged user permissions from all assigned groups and individual
			// permissions
		}
		
		return false;
	}
	
    /**
     * Add user from admin
     * 1. validate form
     * 2. start transaction
     * 3. create account
     * 4. create user
     * 5. assign user to groups
     * 6. Reset individual permissions // may empty on create... anyway...
     * 7. Assign individual permissions
     * 8. send register email
     * 9. commit or roll back transaction
     * 10. return bool 
     */
    public function addNewUser(array $params) : bool
    {
    	// 1.
    	if($this->validateCreateUserBaseInformationForm($params) === true) {
    		// 2. Test if a transaction is already running for whatever reason
    		if($this->PdoWrapper->inTransaction() !== true) {
    			// begin transaction
    			$this->PdoWrapper->beginTransaction();
    			// 3.
    			$accountId = $this->createAccount($params);
    			// account id test
    			if((int)$accountId > 0) {
    				
    				// create random code for password setup
    				$code = Helper::generateCode(34);
    				
    				// 4. create user
    				$userid = $this->createUser($params, (int)$accountId, $code);
    				// call the create user to insert the user data to db
    				if($userid !== false && (int)$userid > 0) {
    					// 5.
    					if(isset($params['permissiongroup']) && is_array($params['permissiongroup']) && count($params['permissiongroup'])) {
    						// Assign user groups
    						if($this->assignUserGroups($params['permissiongroup'], (int)$userid) === true) {
    							// Reset individual permissions regardless
    							if($this->resetUsersIndividualPermissions((int)$userid) === true) {
	    							// if individual permissions are set
	    							if(isset($params['perms']) && is_array($params['perms']) && count($params['perms'])) {
	    								// 7.
	    								if($this->assignIndividualPermissions($params['perms'], (int)$userid) === true) {
	    									// 8.
	    									if($this->Mail->dispatchRegisterMail($params, $code) === true) {
	    										// 9.
	    										$this->PdoWrapper->commit();
	    										// 10.
	    										return true;
	    									}
	    								}
	    									
	    							} else {
	    								// 8
	    								if($this->Mail->dispatchRegisterMail($params, $code) === true) {
	    									// 9.
	    									$this->PdoWrapper->commit();
	    									// 10.
	    									return true;
	    								}
	    							}
    							}
    						}
    					}
    				}
    			
    			} else {
    				// log error
    				$this->ErrorLog->logError('App', 'Failed to create account for user: '.$param['username'], __METHOD__ .' - Line: '. __LINE__);
    			}
    			// 9. create user failed, rollback the transaction
    			$this->PdoWrapper->rollBack();
    			
    		} else {
    			// log error
    			$this->ErrorLog->logError('App', 'Transaction was already open @ sign up user.', __METHOD__ .' - Line: '. __LINE__);
    		}  		
    	}
    	
    	// 10.
    	return false;
    }
    
    /**
     * Get a list of all users
     * @param int $limit
     * @return mixed
     */
    public function getUserAccountById(int $userid)
    {
    	$query = "SELECT
    				account.`name` 				AS `accountName`,
	    			account.`created` 			AS `accountCreated`,
	    			account.`description` 		AS `accountDescription`,
	    			account.`id` 				AS `accountId`,
	    			user.`username` 			AS `userUsername`,
    				user.`email`				AS `userEmail`,
    				user.`id`		 			AS `userId`,
	    			user.`mobile` 				AS `userMobile`,
	    			user.`deleted` 				AS `userDeleted`,
    				user.`last_login`			AS `userLastlogin`,
    				user.`created`				AS `userCreated`,
    				profile.`id`				AS `profileId`,
    				profile.`firstname`			AS `profileFirstname`,
    				profile.`lastname`			AS `profileLastname`,
    				profile.`address_street`	AS `profileAddess_street`,
    				profile.`address_suburb`	AS `profileAddress_suburb`,
    				profile.`address_region`	AS `profileAddress_region`,
    				profile.`address_state`		AS `profileAddress_state`,
    				profile.`address_country`	AS `profileAddress_country`,
    				profile.`set_language`		AS `profileSet_language`,
    				profile.`timezone`			AS `profileTimezone`,
    
    				(SELECT GROUP_CONCAT(`name`) FROM `".$this->addTable('permission_groups')."`
    					WHERE `id` IN (SELECT `groupid` FROM `".$this->addTable('permissions_user_groups_assigned')."`
    						WHERE `userid` = user.`id`)) AS `assignedGroupNames`,
    		
    				(SELECT GROUP_CONCAT(`groupid`) FROM `".$this->addTable('permissions_user_groups_assigned')."`
    					WHERE `userid` = user.`id`) AS `assignedGroupIds`,
    
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_group_assigned')."`
    					WHERE `groupid` IN (SELECT `groupid` FROM `".$this->addTable('permissions_user_groups_assigned')."`
    						WHERE `userid` = user.`id`)) AS `groupPermissions`,
    
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_user_assigned')."`
    					WHERE `userid` = user.`id`) AS `userPermissionIds`
    
    			FROM
    				`".$this->addTable('users')."` user
    					LEFT JOIN
    						`".$this->addTable('accounts')."` account
    							ON user.`accountid` = account.`id`
    					LEFT JOIN
    						`".$this->addTable('user_profile')."` profile
    							ON user.`id` = profile.`userid`
    
    			WHERE 
    				user.`id` = :userid
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssoc();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    	 
    }
    
    /**
     * Get a list of all users 
     * @param int $limit
     * @return mixed
     */
    public function getUserAccounts(int $limit = 50)
    {
    	$query = "SELECT
    				account.`name` 				AS `accountName`,
	    			account.`created` 			AS `accountCreated`,
	    			account.`description` 		AS `accountDescription`,
	    			account.`id` 				AS `accountId`,
	    			user.`username` 			AS `userUsername`,
    				user.`email`				AS `userEmail`,
    				user.`id`		 			AS `userId`,
	    			user.`mobile` 				AS `userMobile`,
	    			user.`deleted` 				AS `userDeleted`,
    				user.`last_login`			AS `userLastlogin`,
    				user.`created`				AS `userCreated`,
    				profile.`id`				AS `profileId`,
    				profile.`firstname`			AS `profileFirstname`,
    				profile.`lastname`			AS `profileLastname`,
    				profile.`address_street`	AS `profileAddess_street`,
    				profile.`address_suburb`	AS `profileAddress_suburb`,
    				profile.`address_region`	AS `profileAddress_region`,
    				profile.`address_state`		AS `profileAddress_state`,
    				profile.`address_country`	AS `profileAddress_country`,
    				profile.`set_language`		AS `profileSet_language`,
    				profile.`timezone`			AS `profileTimezone`,
    				
    				(SELECT GROUP_CONCAT(`name`) FROM `".$this->addTable('permission_groups')."` 
    					WHERE `id` IN (SELECT `groupid` FROM `".$this->addTable('permissions_user_groups_assigned')."` 
    						WHERE `userid` = user.`id`)) AS `assignedGroupNames`,
    							
    				(SELECT GROUP_CONCAT(`groupid`) FROM `".$this->addTable('permissions_user_groups_assigned')."` 
    					WHERE `userid` = user.`id`) AS `assignedGroupIds`,
    						
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_group_assigned')."` 
    					WHERE `groupid` IN (SELECT `groupid` FROM `".$this->addTable('permissions_user_groups_assigned')."` 
    						WHERE `userid` = user.`id`)) AS `groupPermissions`,
    						
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_user_assigned')."` 
    					WHERE `userid` = user.`id`) AS `userPermissionIds`
    			
    			FROM 
    				`".$this->addTable('users')."` user
    					LEFT JOIN
    						`".$this->addTable('accounts')."` account
    							ON user.`accountid` = account.`id`
    					LEFT JOIN
    						`".$this->addTable('user_profile')."` profile
    							ON user.`id` = profile.`userid`
    								
    			LIMIT :limit
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':limit', $limit, PDO::PARAM_INT);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssocList();
    	
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	
    	return false;
    	
    }
    
    /**
     * Get all permissions options
     */
    public function getPermissionOptions()
    {
    	$query = "SELECT 
    				`id`,
    				`name`,
    				`description`,
    				`permtype`
    			  FROM 
    				
    				`".$this->addTable('permissions')."`
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssocList();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    }
    
    /**
     * Update users data
     * @param array $params
     */
    private function updateUser($params)
    {
    		
		$query = "UPDATE `".$this->addTable('users')."`
					SET 
						`username` = :username,
						`email`		= :email,
						`mobile`	= :mobile,
						`pwd`		= :pwd
    				
					WHERE
					`id` = :userid
    		";
    		
    		try {
    			 
    			$this->PdoWrapper->prepare($query);
    			return $this->PdoWrapper->execute();
    			 
    		} catch (\PDOException $e) {
    			 
    			$message = $e->getMessage();
    			$message .= $e->getTraceAsString();
    			$message .= $e->getCode();
    			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    			 
    		}
    	
    	return false;
    }
    
    /**
     * Update users avatar image
     * @param string $file
     */
    public function updateCurrentUsersAvatar(string $file)
    {
    	$query = "UPDATE `".$this->addTable('user_profile')."`
    				SET 
    					`avatar` = :file
    				WHERE
    					`userid` = :userid
    	";
    	
    	try {
    	
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':userid', (int)$this->Session->getUserId(), PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':file', $file, PDO::PARAM_STR);
    	
    		return $this->PdoWrapper->execute();
    	
    	} catch (\PDOException $e) {
    	
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	
    	}
    	
    	return false;
    }
    
    /**
     * Validate form data
     * It may looks crazy but it is not as error-prone and more precise
     * for complex form validation
     * @param array $params@return bool
     */
    private function validateCreateUserBaseInformationForm($params)
    {
    	if(is_array($params) && count($params)) {

    		// test if the curent user is allowed to assign the new user to the selected groups
    		if(isset($params['permissiongroup']) && $this->userCanAssignUserToPermissionGroup($params['permissiongroup']) === true) {
    		
	    		if(isset($params['permissiongroup']) && is_array($params['permissiongroup']) && count($params['permissiongroup'])) {
	    
	    			if(isset($params['username']) && !empty($params['username'])) {
	    
	    				if(\Bang\Helper::validate($params['username'], 'username', 50) !== false) {
	
	    					if($this->usernameExist($params['username']) === false) {
	
	    						if(\Bang\Helper::validate($params['email'], 'email', 100) !== false) {
	    							 
	    							if($this->emailExist($params['email']) === false) {
	    								 
	    								return true;
	    								 
	    							} else {
	    								 
	    								$this->Session->setError($this->Lang->get('account_signup_emailexists_error'));
	    							}
	    							 
	    						} else {
	    							 
	    							$this->Session->setError($this->Lang->get('account_signup_emailvalid_error'));
	    						}
	    							
	    					} else {
	    							
	    						$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_username_exists'));
	    					}
	    					 
	    				} else {
	    					 
	    					$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_invalid_username'));
	    				}
	    
	    			} else {
	    
	    				$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_username'));
	    			}
	    	   
	    		} else {
	    	   
	    			$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_group'));
	    		}
	    		
    		} else {
    			
    			$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_group_permissions'));
    		}
    	}
    	 
    	return false;
    }
    
    /**
     * Validate form data
     * It may looks crazy but it is not as error-prone and more precise
     * for complex form validation
     * @param array $params@return bool
     */
    private function validateUserBaseInformationForm($params)
    {
    	if(is_array($params) && count($params)) {
    		
    		if(isset($params['permissiongroup']) && is_array($params['permissiongroup']) && count($params['permissiongroup'])) {
    		
	    		if(isset($params['username']) && !empty($params['username'])) {
	    			
	    			if(\Bang\Helper::validate($params['username'], 'username', 50) !== false) {
	    				
	    				if($this->usernameExist($params['username'], (int)$params['userid']) === false) {
	    				
	    					if(\Bang\Helper::validate($params['email'], 'email', 100) !== false) {
	    				
	    						if($this->emailExist($params['email'], (int)$params['userid']) === false) {
	    				
	    							return true;
	    				
	    						} else {
	    				
	    							$this->Session->setError($this->Lang->get('account_signup_emailexists_error'));
	    						}
	    				
	    					} else {
	    				
	    						$this->Session->setError($this->Lang->get('account_signup_emailvalid_error'));
	    					}
	    					 
	    				} else {
	    					 
	    					$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_username_exists'));
	    				}
	    				
	    			} else {
	    				
	    				$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_invalid_username'));
	    			}
	    			
	    		} else {
	    			
	    			$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_username'));
	    		}
	    		
	    	} else {
	    		
	    		$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_username'));
	    	}
    		
    	}
    	
    	return false;
    }
    
    
    /**
     * Assign grooups to a user account
     * @param array $groups
     * @param int $userid
     */
    private function assignUserGroups(array $groups, int $userid)
    {
    	$current = $this->Session->getUserId();
    	// 1. User with id 1 can't be deleted
    	if($userid == 1 && (int)$current != 1) {
    		return true;
    	}
    	
		$query = "INSERT IGNORE INTO `".$this->addTable('permissions_user_groups_assigned')."`
					(`groupid`, `userid`)
				  VALUES
					(:groupid, :userid)
		";
		
		try {
			 
			$this->PdoWrapper->prepare($query);
			
			foreach($groups AS $key => $value) {
				
				$this->PdoWrapper->bindValue(':groupid', (int)$value, PDO::PARAM_INT);
				$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
				$this->PdoWrapper->execute();
			}
			
			return true;
			 
		} catch (\PDOException $e) {
			 
			$message = $e->getMessage();
			$message .= $e->getTraceAsString();
			$message .= $e->getCode();
			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
			 
		}
		 
    	return false;
    }
    
    /**
     * Remove all user - group assignments
     * @param int $userid
     */
    private function resetAssignedUserGroups(int $userid)
    {
    	// user with id 1 need only to be superadmin
    	if((int)$userid == 1) {
    		return true;	
    	}
    	
    	$query = "DELETE FROM `".$this->addTable('permissions_user_groups_assigned')."` 
    				WHERE 
    					`userid` = :userid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':userid', (int)$userid, PDO::PARAM_INT);
    		
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	
    	return false;
    }
    
    /**
     * Update the users base data - query
     * @params array $params
     */
    private function updateUserBase(array $params)
    {
    	$query = "UPDATE `".$this->addTable('users')."`
    				SET 
    					`username` 	= :username,
    					`email`		= :email
    			
    				WHERE
    					`id` = :userid
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':username',	$params['username'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':email',		$params['email'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':userid', 	(int)$params['userid'], PDO::PARAM_INT);
    		
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    }
    
    /**
     * Update users basis data
     * This is complex as for validation and db updates/inserts
     * Validation has to consider password change or not, username exist, user name format,
     * email exist and format, group assignment for user and individual permissions for a user.
     * 1. Test user permissions on group hierachy
     * 2. validation
     * 3. start transaction
     * 4. Group assignment
     * 5. Update user base data
     * 6. Reset individual permissions
     * 7. Update individual permissions
     * 8. Commit/rollBack transaction
     * 9. return bool
     * @note The user with id 1 will not change it's usergroupd, that user will
     * always be only in group super administrators
     * @params array $params
     */
    public function updateUserBaseInformation(array $params) : bool
    {
    	// 1. permissions for delete user are the same as for updating users
    	if($this->testPermissionsToDeleteUser((int)$params['userid']) === true) {
	    	// 2. 
	    	if($this->validateUserBaseInformationForm($params) === true) {
				// 3. I do not like to debug nested transactions, therefor I test
	    	 	if($this->PdoWrapper->inTransaction() !== true){
	    	 		// start transaction
	    	 		$this->PdoWrapper->beginTransaction();
	    	 		// 4. deal with groups assignments
	    	 		if(isset($params['permissiongroup']) && is_array($params['permissiongroup']) && count($params['permissiongroup'])) {
	    	 		
						if($this->resetAssignedUserGroups((int)$params['userid']) === true) {
	    	 					
							if($this->assignUserGroups($params['permissiongroup'], (int)$params['userid']) === true) {
	    	 					// 5.
								if($this->updateUserBase($params) === true) {
									// 6.
									if($this->resetUsersIndividualPermissions((int)$params['userid']) === true) {
										
										if(isset($params['perms']) && is_array($params['perms']) && count($params['perms'])) {
											// 7.
											if($this->assignIndividualPermissions($params['perms'], (int)$params['userid']) === true) {
												// 8.
												$this->PdoWrapper->commit();
												// 9.
												return true;
											}
												
										} else {
											// 8.
											$this->PdoWrapper->commit();
											// 9.
											return true;
										}
									}
	    	 					}
	    	 				}
	    	 			}
	    	 		}
	    	 		// 8.
	    	 		$this->PdoWrapper->rollBack();
	    	 	}
	    	 }
	    	 
    	} else {
    		
    		$this->Session->setError($this->Lang->get('app_no_permissions'));
    	}
    	
    	 // 9.
    	 return false;
    }
    
    
    /** ------------------------------------------------------------------------
     * Permission Group Methods
     * -----------------------------------------------------------------------*/
    
    /**
     * Get all permission groups and return
     */
    public function getPermissionGroups()
    {
    	$query = "SELECT
    				`id`,
    				`name`,
    				`description`,
    				`permtype`
    
    				FROM `".$this->addTable('permission_groups')."`
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssocList();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    
    	return false;
    }
    
    /**
     * Validate the create permission group form
     * Test name, descriptionand choosen permissions
     * @param array $params
     */
    private function validateAddPermissionGroupForm(array $params) : bool
    {
    	if(isset($params['name']) && !empty($params['name'])) {
    		
    		if(Helper::validate($params['name'], 'raw', 35) === true) {
    		
	    		if(isset($params['desc']) && !empty($params['desc'])) {
	    			
	    			if(Helper::validate($params['desc'], 'raw', 35) === true) {
	    					
						if($this->groupNameExists($params['name']) !== true) {
							// Only super administrators can add groups
							if($this->Session->isMemberOfGroupWithId(1) === true) {
	    							
								return true;
	    							
							} else {
    										
								$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_group_permissions'));
							}
	    						
	    				} else {
	    						
	    					$this->Session->setError($this->Lang->get('account_group_create_error_name_taken'));
	    				}
	    				
	    			} else {
	    				
	    				$this->Session->setError($this->Lang->get('account_group_create_error_desc_between'));
	    			}
	    			
	    		} else {
	    			
	    			$this->Session->setError($this->Lang->get('account_group_create_error_desc'));
	    		}
    		
    		} else {
    			
    			$this->Session->setError($this->Lang->get('account_group_create_error_name_between'));
    		}
    		
    	} else {
    		
    		$this->Session->setError($this->Lang->get('account_group_create_error_name'));
    	}
    	
    	return false;
    }
    
    /**
     * Test if a group with that name already exists
     * @param string $name
     */
    private function groupNameExists(string $name, int $groupid = 0) : bool
    {
    	$notid = '';
    	
    	if((int)$groupid > 0) {
    		$notid = " AND `id` != :groupid";	
    	}
    	
    	$query = "SELECT `id` 
    				FROM 
    					`".$this->addTable('permission_groups')."`
    				WHERE
    					`name` = :name
    				".$notid."
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':name', $name, PDO::PARAM_STR);
    		
    		if((int)$groupid > 0) {
    			$this->PdoWrapper->bindValue(':groupid', $groupid, PDO::PARAM_INT);
    		}
    	
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssoc();
    	
    		if(is_array($result) && isset($result['id']) && (int)$result['id'] > 0) {
    			return true;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Insert new permission group
     * @param array $params
     */
    private function insertPermissionGroup(array $params)
    {
    	$query = "INSERT INTO `".$this->addTable('permission_groups')."`
    				(`name`, `description`, `permtype`)
    			  VALUES
    				(:name, :description, :permtype)
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':name', $params['name'], PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':description', $params['desc'], PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':permtype', '2', PDO::PARAM_STR);
    		 
    		$this->PdoWrapper->execute();
    		 
    		if($this->PdoWrapper->rowCount() > 0) {
    			return $this->PdoWrapper->lastInsertId();
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Assign the group permissions in the db table
     * @param array $params
     */
    private function assignGroupPermissions(array $perms, int $groupid) : bool
    {
    	$query = "INSERT INTO `".$this->addTable('permissions_group_assigned')."`
    				(`groupid`, `permissionid`)
    			  VALUES
    				(
    				 :groupid,
    				 (SELECT `id` FROM `".$this->addTable('permissions')."` WHERE `name` = :name)
    				)
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    	
    		foreach($perms AS $key => $value) {
    			
    			$this->PdoWrapper->bindValue(':name', $key, PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':groupid', (int)$groupid, PDO::PARAM_INT);
    			$this->PdoWrapper->execute();
    			 
    			if($this->PdoWrapper->rowCount() < 1) {
    				return false;
    			}
    		}
    		
    		return true;
    	
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Reset the assigned group permissions before adding the updated permissions
     * @param int $groupid
     */
    private function resetGroupPermissions(int $groupid) : bool
    {
    	$query = "DELETE FROM `".$this->addTable('permissions_group_assigned')."`
    				WHERE 
    					`groupid` = :groupid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':groupid', (int)$groupid, PDO::PARAM_INT);
	    	return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Add new permission group
     * @param array $params
     */
    public function addNewPermissionGroup(array $params) : bool
    {
    	if($this->validateAddPermissionGroupForm($params) === true) {
    		
    		if($this->PdoWrapper->inTransaction() !== true){
    			// start transaction
    			$this->PdoWrapper->beginTransaction();
    			
    			$groupid = $this->insertPermissionGroup($params);
    			
    			if($groupid !== false && (int)$groupid > 0) {
    				
    				if($this->resetGroupPermissions((int)$groupid) === true) {

    					if(!isset($params['perms'])) {
    						$params['perms'] = array();
    					}
    					
    					if($this->assignGroupPermissions($params['perms'], $groupid) === true) {
    						
    						$this->PdoWrapper->commit();
    						return true;
    						
    					} else {
    						
    						$this->ErrorLog->logError('DB', 'Assign Group Failed', __METHOD__ .' - Line: '. __LINE__);
    					}
    					
    				} else {
    					
    					$this->ErrorLog->logError('DB', 'Reset Group failed', __METHOD__ .' - Line: '. __LINE__);
    				}
    				
    			} else {
    				
    				$this->ErrorLog->logError('DB', 'Insert Group failed', __METHOD__ .' - Line: '. __LINE__);
    			}
    			
    			$this->PdoWrapper->rollBack();
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Update permission groups 
     * @param array $params
     */
    private function validateEditGroupForm(array $params) : bool
    {
    	if(isset($params['name']) && !empty($params['name'])) {
    		
    		if(Helper::validate($params['name'], 'raw', 35) === true) {
    	
    			if(isset($params['desc']) && !empty($params['desc'])) {
    	
    				if(Helper::validate($params['desc'], 'raw', 35) === true) {
    		    
    						if($this->groupNameExists($params['name'], (int)$params['groupid']) !== true) {
    							
    							// Test for super administrator group
    							if($params['groupid'] == 1 || $params['groupid'] == 2) {
    								
    								// The super admin and the admin group can only get changed by a super administrator
    								if($this->Session->isMemberOfGroupWithId(1) === true) {
    										
    									return true;
    								
    								} else {
    										
    									$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_group_permissions'));
    								}
    								
    							// test all other groups
    							} else {
    								
    								if($this->Session->isMemberOfGroupWithId(2) === true || $this->Session->isMemberOfGroupWithId(1) === true) {
    								
    									return true;
    								
    								} else {
    								
    									$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_group_permissions'));
    								}
    								
    							}
    								
    						} else {
    								
    							$this->Session->setError($this->Lang->get('account_group_create_error_name_taken'));
    						}
    		    
    				} else {
    		    
    					$this->Session->setError($this->Lang->get('account_group_create_error_desc_between'));
    				}
    	
    			} else {
    	
    				$this->Session->setError($this->Lang->get('account_group_create_error_desc'));
    			}
    	
    		} else {
    			 
    			$this->Session->setError($this->Lang->get('account_group_create_error_name_between'));
    		}
    	
    	} else {
    	
    		$this->Session->setError($this->Lang->get('account_group_create_error_name'));
    	}
    	 
    	return false;
    }
    
    /**
     * Update the permission group table
     * @param array $params
     */
    private function updatePermisionGroupBase(array $params) : bool
    {
    	$query = "UPDATE `".$this->addTable('permission_groups')."`
    				SET
    					`name` = :name,
    					`description` = :description
    				WHERE 
    					`id` = :groupid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':groupid', (int)$params['groupid'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':name', $params['name'], PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':description', $params['desc'], PDO::PARAM_STR);
    		 
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	
    	return false;
    }
    
    /**
     * Update permission group
     * @param array $params
     */
    public function updatePermissionGroup(array $params) : bool
    {
    	if($this->validateEditGroupForm($params) === true) {
    		
    		if($this->PdoWrapper->inTransaction() !== true) {
    			// start transaction
    			$this->PdoWrapper->beginTransaction();
    			
    			if($this->resetGroupPermissions((int)$params['groupid']) === true) {
    				
    				if(!isset($params['perms'])) {
    					$params['perms'] = array();
    				}
    				if(!isset($params['frontperms'])) {
    					$params['frontperms'] = array();
    				}
    				
    				if($this->assignGroupPermissions($params['perms'], (int)$params['groupid']) === true) {
    					
    					$this->assignGroupPermissions($params['frontperms'], (int)$params['groupid']);
    					
    					if($this->updatePermisionGroupBase($params) === true) {
    						
    						$this->PdoWrapper->commit();
    						return true;
    					}
    				}
    			}
    			
    			$this->PdoWrapper->rollBack();
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Delete group by id
     * @param int $groupid
     */
    private function deleteGroup(int $groupid) : bool
    {
    	$query = "DELETE FROM  `".$this->addTable('permission_groups')."`
    				WHERE `id` = :groupid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':groupid', (int)$groupid, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		
    		if($this->PdoWrapper->rowCount() > 0) {
    			return true;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    }
    
    /**
     * Remove all user - group assignments based on the groupid
     * @param int $groupid
     */
    private function resetAssignedUserGroupsByGroupId(int $groupid) : bool
    {
    	$query = "DELETE FROM `".$this->addTable('permissions_user_groups_assigned')."`
    				WHERE
    					`groupid` = :groupid
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':groupid', (int)$groupid, PDO::PARAM_INT);
    
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    }
    
    /**
     * Delete permission group
     * This need to clean up all traces of a group
     * Remove all assign rows for users and system permissions
     * Only the complete clean up is commiting.
     * @param int $groupid
     */
    public function deletePermissionGroup(int $groupid) : bool
    {
    	if((int)$groupid > 0) {
    		
    		// Dissalow the delete of system groups
    		if((int)$groupid == 1 || (int)$groupid == 2 || (int)$groupid == 3) {
    			
    			$this->Session->setError($this->Lang->get('account_group_delete_system_group_cant_get_deleted'));
    			return false;
    		}
    		
    		// Only super administrators can delete groups AND users with individual permissions
    		if($this->Session->isMemberOfGroupWithId(1) !== true && $this->Session->hasPermission(6) !== true) {
    		
    			$this->Session->setError($this->Lang->get('account_user_edit_form_base_error_group_permissions'));
    			return false;
    		} 
    		
    		// don't like to debug nested transactions...
    		if($this->PdoWrapper->inTransaction() !== true) {
    			 
    			// 3. begin a db transaction
    			$this->PdoWrapper->beginTransaction();
    		
    				if($this->deleteGroup((int)$groupid) === true) {
    					
    					if($this->resetGroupPermissions((int)$groupid) === true) {
    						
    						if($this->resetAssignedUserGroupsByGroupId((int)$groupid) === true) {
    							
    							$this->PdoWrapper->commit();
    							return true;
    							
    						} else {
    						
    							$this->ErrorLog->logError('DB', 'Delete Permission Group - Reset user - group assignemnt '.(int)groupid.' failed', __METHOD__ .' - Line: '. __LINE__);
    						}
    						
    					} else {
    						
    						$this->ErrorLog->logError('DB', 'Delete Permission Group - Reset Group Permissions '.(int)groupid.' failed', __METHOD__ .' - Line: '. __LINE__);
    					}
    					
    				} else {
    					
    					$this->ErrorLog->logError('DB', 'Delete Permission Group - Delete group '.(int)groupid.' failed', __METHOD__ .' - Line: '. __LINE__);
    				}
    			
    			$this->PdoWrapper->rollBack();
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Get group data by id
     * @param int $groupid
     */
    public function getGroupDataById(int $groupid)
    {
    	$query = "SELECT 
    				`id`, `name`, `description`, `permtype`,
    			
    				(SELECT GROUP_CONCAT(`permissionid`) FROM `".$this->addTable('permissions_group_assigned')."` 
    					WHERE `groupid` = :groupid ) AS `groupPermIds`
    						
    				FROM
    					`".$this->addTable('permission_groups')."`
    				WHERE
    					`id` = :groupid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':groupid', (int)$groupid, PDO::PARAM_INT);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssoc();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    }
    
    /** ----------------- Settings Update ----------- */
    
    /**
     * Validate the seetings form
     * @param array $params
     */
    private function validateSettingForm(array $params) : bool
    {
    	// testt the string for length, the language code will never have more this
    	if(!empty($params['language'])) {
    		
    		$langs = array_flip(CONFIG['langwhitelist']);
    		// test against whitelist
    		if(isset($langs[$params['language']])) {
    			
    			if(isset($params['timezone']) && !empty($params['timezone'])) {
    				
    				$tzlist = \Bang\Helper::getTiemzoneList();
    				
    				if(is_array($tzlist) && isset($tzlist[$params['timezone']])) {
    					
    					return true;
    					
    				} else {
    					
    					$this->Session->setError($this->Lang->get('account_settings_validate_error_timezone'));
    				}
    				
    			} else {
    				
    				$this->Session->setError($this->Lang->get('account_settings_error_timezone'));
    			}
    			
    		} else {
    			
    			$this->Session->setError($this->Lang->get('account_settings_validate_error_language'));
    		}
    		
    	} else {
    		
    		$this->Session->setError($this->Lang->get('account_settings_error_language'));
    	}
    	
    	return false;
    }
    
    /**
     * Update the current users settings
     * @param array $params
     */
    public function updateUserSettings(array $params) : bool
    {
    	if($this->validateSettingForm($params) === true) {
    		
    		$query = "UPDATE `".$this->addTable('user_profile')."`
    					SET 
    						`set_language` = :language,
    						`timezone` = :timezone
    					WHERE
    						`userid` = :userid
    		";
    		
    		try {
    			 
    			$this->PdoWrapper->prepare($query);
    			$this->PdoWrapper->bindValue(':userid', 	(int)$this->Session->getUserId(), PDO::PARAM_INT);
    			$this->PdoWrapper->bindValue(':timezone', 	$params['timezone'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':language', 	$params['language'], PDO::PARAM_STR);
    			 
    			return $this->PdoWrapper->execute();
    			 
    		} catch (\PDOException $e) {
    			 
    			$message = $e->getMessage();
    			$message .= $e->getTraceAsString();
    			$message .= $e->getCode();
    			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    			 
    		}
    	}
    	
    	return false;
    }
    
    
    /** -------------- User Profile -------------------- */
    
    
    /**
     * Get the current users profile
     */
    public function getCurrentUserProfile()
    {
    	$query = "SELECT * FROM `".$this->addTable('user_profile')."`
    				WHERE 
    					`userid` = :userid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':userid', (int)$this->Session->getUserId(), PDO::PARAM_INT);
    		 
    		$this->PdoWrapper->execute();
    		$result = $this->PdoWrapper->fetchAssoc();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	
    	return false;
    }
    
    /**
     * Validate form parameters
     * @param array $params
     */
    private function validateProfile(array $params)
    {
    	// no mandatory fields yet
    	return true;
    }
    
    /**
     * Update current users profile
     * This is processing inserts and updates using "on duplicate key"
     * @param array $params
     */
    public function updateUserProfile(array $params) 
    {
    	if($this->validateProfile($params) === true) {
    		
			// insert  and if key is already there update    		
    		$query = "INSERT INTO `".$this->addTable('user_profile')."`
    					(`userid`, `firstname`, `lastname`, `address_street`, 
    					`address_suburb`, `address_state`, 
    					`address_country`, `mobile`)
    				
    				   VALUES
    				
    					(:userid, :firstname, :lastname, :address_street, 
    					:address_suburb, :address_state, 
    					:address_country, :mobile)
    				
    				ON DUPLICATE KEY UPDATE
    					
    					firstname			= VALUES(firstname),
                    	lastname   			= VALUES(lastname),
	    				address_street   	= VALUES(address_street),
	    				address_suburb   	= VALUES(address_suburb),
	    				address_state   	= VALUES(address_state),
	    				address_country   	= VALUES(address_country),
    					mobile   			= VALUES(mobile)
    		";
    		
    		try {
    		
    			$this->PdoWrapper->prepare($query);
    			$this->PdoWrapper->bindValue(':userid', 			(int)$this->Session->getUserId(), PDO::PARAM_INT);
    			$this->PdoWrapper->bindValue(':firstname', 			$params['firstname'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':lastname', 			$params['lastname'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':address_street', 	$params['address'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':address_suburb', 	$params['suburb'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':address_state', 		$params['state'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':address_country', 	$params['country'], PDO::PARAM_STR);
    			$this->PdoWrapper->bindValue(':mobile', 			$params['mobile'], PDO::PARAM_STR);
    		
    			return $this->PdoWrapper->execute();
    		
    		} catch (\PDOException $e) {
    		
    			$message = $e->getMessage();
    			$message .= $e->getTraceAsString();
    			$message .= $e->getCode();
    			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		
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