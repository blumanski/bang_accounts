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
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_user_create_form_headline');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						 <i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<a href="/account/user/index/">
							<?php echo $this->Lang->write('account_listing_breadcrumbs_tolisting');?>
						</a>
						<i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
							<?php echo $this->Lang->write('account_listing_breadcrumbs_new_user');?>
						
						
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
		        <li class="active"><a href="/account/user/adduser/"><?php echo $this->Lang->write('account_navi_create');?></a></li>
		        <li><a href="/account/group/index/"><?php echo $this->Lang->write('account_navi_groupslist');?></a></li>
		        <li><a href="/account/group/newgroup/"><?php echo $this->Lang->write('account_navi_groupsnew');?></a></li>
		      </ul>
			</div>
		</nav>

		<div class="action-wrapper">
			<div class="table-datatables">
			
			
			
				<?php if(isset($this->tplVar['groups']) && is_array($this->tplVar['groups']) && count($this->tplVar['groups'])) { ?>
					
					<?php if(isset($this->tplVar['perms']) && is_array($this->tplVar['perms']) && count($this->tplVar['perms'])) { ?>
					
					<?php 
					$post = $this->Session->getFormData();
					if(isset($post['permissiongroup']) && is_array($post['permissiongroup']) && count($post['permissiongroup'])) {
						$post['permissiongroup'] = array_flip($post['permissiongroup']);
					}
					?>
					
							<div class="row">
								
								<div class="col s6">
								
									<form autocomplete="off" id="create-user-base" action="/account/user/processusercreate/" method="post">
									
										<div class="row margin">
											<div class="input-field col s12">
									            <input 
									            	autocomplete="off"
									            	placeholder="<?php echo $this->Lang->write('account_user_edit_form_username');?>" 
									            	type="text" id="username" 
									            	name="username" 
									            	value="<?php if(isset($post['username'])) { echo htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8'); }?>" 
									            />
									            
									            <label for="username" class="center-align"><?php echo $this->Lang->write('account_user_edit_form_username');?></label>
									            <div class="valerror"></div>
											</div>
										</div>
										
										<div class="row margin">
											<div class="input-field col s12">
									            <input 
									            	autocomplete="off"
									            	placeholder="<?php echo $this->Lang->write('account_user_edit_form_email');?>" 
									            	type="text" 
									            	id="email" 
									            	name="email" 
									            	value="<?php if(isset($post['email'])) { echo htmlspecialchars($post['email'], ENT_QUOTES, 'UTF-8'); }?>" 
									            />
									            
									            <label for="email" class="center-align"><?php echo $this->Lang->write('account_user_edit_form_email');?></label>
									            <div class="valerror"></div>
											</div>
										</div>
										
										<div class="row margin">
										
											<h5><?php echo $this->Lang->write('account_user_edit_form_permission_groups');?></h5>
										
											<div class="col s6">
												
							                      <?php foreach($this->tplVar['groups'] AS $key => $value) { ?>
													
													<?php if($this->Session->isMemberOfGroupWithId(1) === true) { ?>
														
														<input 
															<?php if(isset($post['permissiongroup'][$value['id']])) { echo 'checked="checked" '; }?>
															type="checkbox" 
															name="permissiongroup[]" 
															value="<?php echo $value['id']; ?>"
															id="group<?php echo $value['id']; ?>" 
														/>
														<label for="group<?php echo $value['id']; ?>"><?php echo $value['name']; ?></label><br />
														<div class="valerror"></div>
														
														
													<?php } elseif($value['permtype'] == 2) { ?>
													
														<input 
															<?php if(isset($post['permissiongroup'][$value['id']])) { echo 'checked="checked" '; }?>
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
										<div class="divider" style="margin-top: 20px;"></div>
										
										<h4><?php echo $this->Lang->write('account_user_create_form_optional');?></h4>
										
										<div class="row margin">
											<div 
												id="toggle-indi" 
												data-hide="<?php echo $this->Lang->write('account_user_edit_form_permission_user_show_button_hide');?>" 
												data-show="<?php echo $this->Lang->write('account_user_edit_form_permission_user_show_button');?>"
												class="btn waves-effect waves-light warn btn-xs">
													<?php echo $this->Lang->write('account_user_edit_form_permission_user_show_button');?>
												</div>
										</div>
										
										
										<div class="row margin" id="indi">
										
											 <h5><?php echo $this->Lang->write('account_user_edit_form_permission_user');?></h5>
										
											<div class="input-field col s6"  style="margin-top: 15px;">
												
							                      <?php foreach($this->tplVar['perms'] AS $key => $value) { ?>
													
													<input 
															type="checkbox" 
															name="perms[<?php echo $value['name']; ?>]" 
															value="<?php echo $value['id']; ?>"
															id="userperm<?php echo $value['id']; ?>" 
													/>
													<label for="userperm<?php echo $value['id']; ?>"><?php echo $value['description']; ?></label><br />
													                      
							                      <?php } ?>
							                      
									           
											</div>
										</div>
										
								<?php */ ?>	
										
										<div class="divider" style="margin-top: 20px;"></div>
										
										<div class="row margin" style="margin-top: 35px !important;">
											<div class="input-field ol s12">
											<input type="checkbox" value="1" checked="checked" disabled="disabled" name="sendmail" id="sendmail" />
											<label for="sendmail"><?php echo $this->Lang->write('account_user_create_form_send_register');?></label>
											</div>
										</div>
										
										<div class="row" style="margin-top: 25px;">
											<div class="col s12">
											<button 
												type="submit" 
												class="btn waves-effect waves-light left submit">
													<?php echo $this->Lang->write('account_user_edit_form_submit');?>
												</button>
											</div>
										</div>
									
									</form>
								
								</div>
								
								
								<div class="col s6">
								
								
								</div>
							
							</div>
						
					
					<?php } ?>
				
				<?php } ?>
			</div>
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
		},
		"passwordempty": {
			"error": "<?php echo $this->Lang->write('account_user_create_form_base_error_password_empty');?>"
		}
	};
</script>

