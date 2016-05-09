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
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_group_edit_headline');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						 <i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<a href="/account/group/index/">
							<?php echo $this->Lang->write('account_groups_breadcrumbs_tolisting');?>
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
				        <li class="active"><a href="#"><?php echo $this->Lang->write('account_navi_groupsedit');?></a></li>
		      </ul>
			</div>
		</nav>
		
		
	<div class="action-wrapper">
		<div class="table-datatables">
		
		<?php if($this->Session->isMemberOfGroupWithId(1) === true || $this->Session->hasPermission(5) === true) { ?>	

			<?php if(isset($this->tplVar['group']) && is_array($this->tplVar['group']) && count($this->tplVar['group'])) { ?>
				
				<?php 
				$group = $this->tplVar['group'];
				
				if(!empty($group['groupPermIds'])) {
					$group['perms'] = explode(',', $group['groupPermIds']);
					if(is_array($group['perms'])) {
						$group['perms'] = array_flip($group['perms']);
					}
						
				} else {
					$group['perms'] = array();
				}
				?>
				
				<?php if(isset($this->tplVar['perms']) && is_array($this->tplVar['perms']) && count($this->tplVar['perms'])) { ?>
				
						<div class="row">
							
							<div class="col s6">
							
								<form id="edit-group" action="/account/group/processeditgroupform/" method="post">
							
								 <div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_group_create_label_name');?>" 
							            	type="text" id="name" 
							            	maxlength="35"
							            	name="name" 
							            	value="<?php if(isset($group['name'])) { echo htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <input type="hidden" name="groupid" value="<?php if(isset($group['id'])) echo $group['id'];?>" />
							            
							            <label for="name" class="center-align"><?php echo $this->Lang->write('account_group_create_label_name');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_group_create_label_desc');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="desc" 
							            	name="desc" 
							            	value="<?php if(isset($group['description'])) { echo htmlspecialchars($group['description'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_group_create_label_desc');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<?php if($group['permtype'] == 1) { ?>
								
									<div class="row margin">
									
										 <h5><?php echo $this->Lang->write('account_group_create_label_permissions');?></h5>
									
										<div id="permsis" class="input-field col s6"  style="margin-top: 15px;">
												
						                      <?php foreach($this->tplVar['perms'] AS $key => $value) { ?>
												
												<?php if($value['permtype'] == 1) { ?>
												
												<input 
														<?php if(isset($group['perms'][$value['id']])) { echo 'checked="checked" '; }?>
														class="permcheck"
														type="checkbox" 
														name="perms[<?php echo $value['name']; ?>]" 
														value="<?php echo $value['id']; ?>"
														id="perms<?php echo $value['id']; ?>" 
												/>
												<label for="perms<?php echo $value['id']; ?>"><?php echo $value['description']; ?></label><br />
	
											<?php } ?>
						                      <?php } ?>
						                      <div class="valerror"></div>  
								           
										</div>
									</div>
								
								<?php } ?>
								
								<?php //if($group['permtype'] == 2) { ?>
								
									<div class="row margin">
									
										 <h5><?php echo $this->Lang->write('account_group_create_label_permissions_frontend');?></h5>
									
										<div id="permsis" class="input-field col s6"  style="margin-top: 15px;">
												
						                      <?php foreach($this->tplVar['perms'] AS $key => $value) { ?>
												
												<?php if($value['permtype'] == 2) { ?>
												<input 
														<?php if(isset($group['perms'][$value['id']])) { echo 'checked="checked" '; }?>
														class="permcheck"
														type="checkbox" 
														name="frontperms[<?php echo $value['name']; ?>]" 
														value="<?php echo $value['id']; ?>"
														id="perms<?php echo $value['id']; ?>" 
												/>
												<label for="perms<?php echo $value['id']; ?>"><?php echo $value['description']; ?></label><br />
	
											<?php } ?>
						                      <?php } ?>
						                      <div class="valerror"></div>  
								           
										</div>
									</div>
								
								<?php //} ?>
								
								<div class="row" style="margin-top: 25px;">
									<div class="col s12">
									<button 
										type="submit" 
										class="btn waves-effect waves-light left submit">
											<?php echo $this->Lang->write('account_group_create_submit_button');?>
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
			
			<?php } else { ?>
			
				<?php $this->Lang->write('account_user_edit_form_base_error_group_permissions');?>
			
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
		}
	};
</script>

