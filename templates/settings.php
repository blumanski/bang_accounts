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
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('account_settings_headliner');?></h5>
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
					<li class="active"><a href="#"><?php echo $this->Lang->write('account_settings_navi_conf');?></a></li>
		      </ul>
			</div>
		</nav>

	<div class="action-wrapper">
		<div class="table-datatables">


		<div class="row">
						
			<div class="col s6">
			
				<form autocomplete="off" id="update-settings" action="/account/settings/processSaveSetings/" method="post">
				
					<div class="row margin">
								
						 <h5><?php echo $this->Lang->write('account_settings_navi_language');?></h5>
					
						<div id="language" class="input-field col s6"  style="margin-top: 15px;">
								
								<?php if(isset(CONFIG['langwhitelist']) && count(CONFIG['langwhitelist'])) { ?>
								
									<?php foreach(CONFIG['langwhitelist'] AS $key => $value) { ?>
									
										<input 
												<?php if(isset($this->tplVar['language']) && strtolower($this->tplVar['language']) == strtolower($value)) { echo 'checked="checked"';}?> 
												class="permcheck"
												type="radio" 
												name="language" 
												value="<?php echo $value;?>"
												id="language_<?php echo $key;?>"
										/>
										<label for="language_<?php echo $key;?>"><?php echo strtoupper($key);?></label><br />
									
									<?php } ?>
								
								<?php } ?>
								                     
		                      <div class="valerror"></div>  
				           
						</div>
					</div>
					
					<div class="row margin">
					
						 <h5><?php echo $this->Lang->write('account_settings_navi_timezone');?></h5>
					
						<div id="timezone-wrapper" class="input-field col s6"  style="margin-top: 15px;">
								
		                      <select class="auth-input" id="timezone" name="timezone">
		                      <option value=""><?php echo $this->Lang->write('account_settings_form_select_one');?></option>
		                      
		                      <?php if(isset($this->tplVar['zone']) && count($this->tplVar['zone'])) { ?>
		                      
		                      	<?php foreach($this->tplVar['zone'] AS $key => $value) { ?>
							    	
							    	<option <?php if(isset($this->tplVar['timezone']) && $this->tplVar['timezone'] == $key) { echo 'selected="selected"';}?> 
							    			value="<?php echo $key;?>">
							    		<?php echo $value;?>
							    	</option>
							    
							    <?php } ?>
							  <?php } ?>  
							    
							</select>
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
			
			</div>
			
			
			<div class="col s6">
			
			
			</div>
		
		</div>
			</div>
		</div>
	</div>
</div>

<script>
	var errmsg = {
		"language": {
			"error": "<?php echo $this->Lang->write('account_settings_error_language');?>"
		},
		"timezone": {
			"error": "<?php echo $this->Lang->write('account_settings_error_timezone');?>"
		}
	};
</script>