<!--breadcrumbs start-->
<div id="breadcrumbs-wrapper">
	<!-- Search for small screen -->
	<div class="header-search-wrapper grey hide-on-large-only">
		<i class="mdi-action-search active"></i>
			<input type="text" name="Search" class="header-search-input z-depth-2" placeholder="Explore Materialize">
	</div>
	
	<div class="container">
	
		<div class="row">
			<div class="col s12 m12 l12">
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_navi_edit');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						 <i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<a href="/account/user/index/">
							<?php echo $this->Lang->write('account_navi_list');?>
						</a>
					</li>
				</ol>
			</div>
		</div>
	</div>
</div>
<!--breadcrumbs end-->
        
<!--start container-->
<div class="container content-wrapper">
	<div class="section">
	
		<nav>
			<div class="nav-wrapper">
				<ul id="nav-mobile" class="left hide-on-med-and-down">
				        <li><a href="/account/user/index/"><?php echo $this->Lang->write('account_navi_list');?></a></li>
				        <li><a href="/account/user/adduser/"><?php echo $this->Lang->write('account_navi_create');?></a></li>
				        <li><a href="/account/group/index/"><?php echo $this->Lang->write('account_navi_groupslist');?></a></li>
				        <li><a href="/account/group/newgroup/"><?php echo $this->Lang->write('account_navi_groupsnew');?></a></li>
				        <li class="active"><a href="#"><?php echo $this->Lang->write('account_navi_edit');?></a></li>
		      </ul>
			</div>
		</nav>

		<div class="action-wrapper">
			<div class="table-datatables">
			
			<?php if($this->Session->isMemberOfGroupWithId(1) === true || $this->Session->isMemberOfGroupWithId(2) === true || $this->Session->hasPermission(5) === true) { ?>
			
				<h4 class="header"><?php echo $this->Lang->write('account_user_edit_form_headline'); ?></h4>
				
				<?php if(isset($this->tplVar['groups']) && is_array($this->tplVar['groups']) && count($this->tplVar['groups'])) { ?>
					
					<?php if(isset($this->tplVar['perms']) && is_array($this->tplVar['perms']) && count($this->tplVar['perms'])) { ?>
					
						<?php 
						$user = array();
						if(isset($this->tplVar['user']) && is_array($user) && isset($this->tplVar['user']['userUsername'])) {
							$user = $this->tplVar['user'];	
							
							// unpack user groups and flip array tu be able to use the keys instead of the values
							$user['groups'] = explode(',', $user['assignedGroupIds']);
							if(is_array($user['groups'])) {
								$user['groups'] = array_flip($user['groups']);
							} else {
								$user['groups'] = array();
							}
							
							// unpack user user permissions and flip array tu be able to use the keys instead of the values
							if(!empty($user['userPermissionIds'])) {
								$user['perms'] = explode(',', $user['userPermissionIds']);
								if(is_array($user['perms'])) {
									$user['perms'] = array_flip($user['perms']);
								}
								
							} else {
								$user['perms'] = array();
							}
							
						}
						?>
					
							<div class="row">
								
								<div class="col s6">
								
									<form id="edit-user-base" action="/account/user/processuserbaseform/" method="post">
								
			<!-- 							<div class="row"> -->
			<!-- 								<div class="input-field col s12"> -->
			<!-- 						            <p> -->
									            	<?php //echo $this->Lang->write('account_user_edit_form_created');?> 
									            	<!-- <time data-format="L" datetime="<?php echo htmlspecialchars($user['userCreated'], ENT_QUOTES, 'UTF-8');?>"></time> -->
			<!-- 						            </p> -->
			<!-- 								</div> -->
			<!-- 							</div> -->
									
										<h4><?php echo $this->Lang->write('account_user_edit_form_mandatory');?></h4>
					
										<div class="row margin">
											<div class="input-field col s12">
									            <input 
									            	placeholder="<?php echo $this->Lang->write('account_user_edit_form_username');?>" 
									            	type="text" id="username" 
									            	name="username" 
									            	value="<?php if(isset($user['userUsername'])) { echo htmlspecialchars($user['userUsername'], ENT_QUOTES, 'UTF-8'); }?>" 
									            />
									            
									            <input type="hidden" name="userid" value="<?php if(isset($user['userId'])) { echo (int)$user['userId']; }?>" />
									            
									            <label for="username" class="center-align"><?php echo $this->Lang->write('account_user_edit_form_username');?></label>
									            <div class="valerror"></div>
											</div>
										</div>
										
										<div class="row margin">
											<div class="input-field col s12">
									            <input 
									            	placeholder="<?php echo $this->Lang->write('account_user_edit_form_email');?>" 
									            	type="text" 
									            	id="email" 
									            	name="email" 
									            	value="<?php if(isset($user['userEmail'])) { echo htmlspecialchars($user['userEmail'], ENT_QUOTES, 'UTF-8'); }?>" 
									            />
									            
									            <label for="email" class="center-align"><?php echo $this->Lang->write('account_user_edit_form_email');?></label>
									            <div class="valerror"></div>
											</div>
										</div>
										
										<div class="row margin">
										
											<h5><?php echo $this->Lang->write('account_user_edit_form_permission_groups');?></h5>
										
											<div class="input-field col s6">
												
							                      <?php foreach($this->tplVar['groups'] AS $key => $value) { ?>
													
													<?php if(($value['id'] == 1 || $value['id'] == 2)) { ?>
													
													<?php if($this->Session->isMemberOfGroupWithId(1) === true) { ?>
													<input 
															<?php if(isset($user['groups'][$value['id']])) { echo 'checked="checked" '; }?>
															type="checkbox" 
															name="permissiongroup[]" 
															value="<?php echo $value['id']; ?>"
															id="group<?php echo $value['id']; ?>" 
													/>
													<label for="group<?php echo $value['id']; ?>"><?php echo $value['name']; ?></label><br />
													<div class="valerror"></div>
													
													<?php  }} else { ?>
														
														<input 
																<?php if(isset($user['groups'][$value['id']])) { echo 'checked="checked" '; }?>
																type="checkbox" 
																name="permissiongroup[]" 
																value="<?php echo $value['id']; ?>"
																id="group<?php echo $value['id']; ?>" 
														/>
														<label for="group<?php echo $value['id']; ?>"><?php echo $value['name']; ?></label><br />
														<div class="valerror"></div>
														
														
													<?php } ?>
													                      
							                      <?php } ?>
											</div>
										</div>
										
									<?php /* ?>	
										<?php if(!count($user['perms'])) { ?>
										<div class="row margin">
											<div 
												id="toggle-indi" 
												data-hide="<?php echo $this->Lang->write('account_user_edit_form_permission_user_show_button_hide');?>" 
												data-show="<?php echo $this->Lang->write('account_user_edit_form_permission_user_show_button');?>"
												class="btn btn-xs warn waves-effect waves-light ">
													<?php echo $this->Lang->write('account_user_edit_form_permission_user_show_button');?>
												</div>
										</div>
										<?php } ?>
										
									
										<div class="row margin" <?php if(!count($user['perms'])) { ?> id="indi" <?php } ?>>
										
											 <h5><?php echo $this->Lang->write('account_user_edit_form_permission_user');?></h5>
										
											<div class="input-field col s6">
												
							                      <?php foreach($this->tplVar['perms'] AS $key => $value) { ?>
													
													<?php if($this->Session->isMemberOfGroupWithId(1) === true) { ?>
													
													<input 
															<?php if(isset($user['perms'][$value['id']])) { echo 'checked="checked" '; }?>
															type="checkbox" 
															name="perms[<?php echo $value['name']; ?>]" 
															value="<?php echo $value['id']; ?>"
															id="userperm<?php echo $value['id']; ?>" 
													/>
													
													<label for="userperm<?php echo $value['id']; ?>"><?php echo $value['description']; ?></label><br />
													
													<?php } else { ?>
													
														<!--  <input type="hidden" name="perms[<?php echo $value['noPermissions']; ?>]" value="4" id="userperm4"  />-->
													
													<?php } ?>
													                      
							                      <?php } ?>
							                      
									           
											</div>
										</div>
										<?php */ ?>
										
										<div class="row margin" style="margin-top: 25px !important;">
											<button 
												type="submit" 
												class="btn waves-effect waves-light left submit">
													<?php echo $this->Lang->write('account_user_edit_form_submit');?>
												</button>
										</div>
									
									</form>
								
								</div>

							</div>
					
					<?php } ?>
				
				<?php } ?>
				
				<?php  } else { ?>
					
					<?php $this->Lang->write('account_user_edit_form_base_error_group_permissions');?>
				
				<?php } ?>
			</div>
		</div>
	</div>

<script>
	var errmsg = {
		"username": {
			"error": "<?php echo $this->Lang->write('account_user_edit_form_base_error_notempty');?>"
		},
		"email": {
			"error": "<?php echo $this->Lang->write('account_user_edit_form_base_error_email');?>"
		},
		"password": {
			"error": "<?php echo $this->Lang->write('account_user_edit_form_base_error_password');?>"
		}
	};
</script>

