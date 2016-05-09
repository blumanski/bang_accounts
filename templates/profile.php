<!--breadcrumbs start-->
<div id="breadcrumbs-wrapper">
	<div class="container">
		<div class="row">
			<div class="col s12 m12 l12">
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_profile_navi_profile');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
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
					<li class="active"><a href="#"><?php echo $this->Lang->write('account_profile_breadcrumbs_profil');?></a></li>
		      </ul>
			</div>
		</nav>

		<div class="action-wrapper">
	
			<div class="table-datatables">

				<div class="row s12">
							
					<div class="col s6">
				
						<form autocomplete="off" id="profile-form" action="/account/profile/processprofile/" method="post">
							<?php
							$user = array();
							if(isset($this->tplVar['myprofile']) && count($this->tplVar['myprofile'])) {
								$user = $this->tplVar['myprofile'];
							}
							?>
							
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_firstname');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="firstname" 
							            	name="firstname" 
							            	value="<?php if(isset($user['firstname'])) { echo htmlspecialchars($user['firstname'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_firstname');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_lastname');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="lastname" 
							            	name="lastname" 
							            	value="<?php if(isset($user['lastname'])) { echo htmlspecialchars($user['lastname'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_lastname');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_mobile');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="mobile" 
							            	name="mobile" 
							            	value="<?php if(isset($user['mobile'])) { echo htmlspecialchars($user['mobile'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_mobile');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_address');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="address" 
							            	name="address" 
							            	value="<?php if(isset($user['address_street'])) { echo htmlspecialchars($user['address_street'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_address');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_town');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="suburb" 
							            	name="suburb" 
							            	value="<?php if(isset($user['address_suburb'])) { echo htmlspecialchars($user['address_suburb'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_town');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_state');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="state" 
							            	name="state" 
							            	value="<?php if(isset($user['address_state'])) { echo htmlspecialchars($user['address_state'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_state');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('account_profile_form_country');?>" 
							            	type="text" 
							            	maxlength="35"
							            	id="country" 
							            	name="country" 
							            	value="<?php if(isset($user['address_country'])) { echo htmlspecialchars($user['address_country'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('account_profile_form_country');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
							
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
				
					</div> <!-- s6 -->
				
				
					<div class="col s6">
			
						<form id="profileDropzone" action="/account/ajax/avatarupload/" class="dropzone">
							<div id="profile-files"></div>
						</form>
					
						<script>
						
						Dropzone.autoDiscover = false;
						var token = '<?php if(isset($this->tplVar['token']) && !empty($this->tplVar['token'])) { echo $this->tplVar['token']; }?>';
		
						var profileDropzone = new Dropzone("#profileDropzone",
							{ 
								maxFilesize: (2 * 512000) / 1024 / 1024,
								parallelUploads: 1,
								acceptedFiles: 'image/jpeg, image/jpe, image/jpg, image/png',
								addRemoveLinks: true,
								clickable: ".dropzone",
								previewsContainer: '#profile-files',
								thumbnailWidth: "160",
								thumbnailHeight: "160",
								cursorAt: { top: 0, left: 0 },
								maxFiles: 1,
								dictDefaultMessage: "<?php echo $this->Lang->write('dropzone_dictDefaultMessage');?>",
								dictFallbackMessage: "<?php echo $this->Lang->write('dropzone_dictFallbackMessage');?>",
								dictFallbackText: "<?php echo $this->Lang->write('dropzone_dictFallbackText');?>",
								dictFileTooBig: "<?php echo $this->Lang->write('dropzone_dictFileTooBig');?>",
								dictInvalidFileType: "<?php echo $this->Lang->write('dropzone_dictInvalidFileType');?>",
								dictResponseError: "<?php echo $this->Lang->write('dropzone_dictResponseError');?>",
								dictCancelUpload: "<?php echo $this->Lang->write('dropzone_dictCancelUpload');?>",
								dictCancelUploadConfirmation: "<?php echo $this->Lang->write('dropzone_dictCancelUploadConfirmation');?>",
								dictRemoveFile: "<?php echo $this->Lang->write('dropzone_dictRemoveFile');?>",
								dictMaxFilesExceeded: "<?php echo $this->Lang->write('dropzone_dictMaxFilesExceeded');?>",

								params: {
							        _token: token
							    },
								
								init: function() {
			
									this.on("success", function(file, responseText) {
										document.location.reload();
									 });
									
									this.on("error", function(file, message) { 
										console.log('error'); 
									});
			
									this.on("maxfilesexceeded", function(file) { 
										this.removeFile(file);
										console.log('too big'); 
									});
								}
							}
						);
						
						</script>
						
					</div> <!-- end upload -->
				</div> <!-- s12 -->
			</div> <!--  data tables -->
		</div> <!-- action wrapper -->
	</div> <!-- section -->
</div> <!--  content wrapper -->