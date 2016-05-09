<form action="/account/index/forgot/" method="post" class="login-form">
      
	<div class="row">
		<div class="input-field col s12">
			<h4><?php echo $this->Lang->write('account_forgot_headline');?></h4>
			<p>
			<?php echo $this->Lang->write('account_forgot_desciption');?>
			</p>
		</div>
	</div>
        
	<div class="row margin">
		<div class="input-field col s12">
			<i class="mdi-social-person-outline prefix"></i>
			<input type="text" name="email" value="" />
			<input type="hidden" name="token" value="<?php if(isset($this->tplVar['token'])) echo htmlspecialchars($this->tplVar['token'], ENT_QUOTES, 'UTF-8')?>" />
			<label for="email" class="center-align"><?php echo $this->Lang->write('account_forgot_form_reset_email');?></label>
		</div>        
	</div>
        
	<div class="row margin">
		<div class="col s12">
          
			<div class="g-recaptcha" data-sitekey="<?php print htmlspecialchars($this->tplVar['sitekey'], ENT_QUOTES, 'UTF-8');?>"></div>
          
		</div>
	</div>
        
	<div class="row">
		<div class="input-field col s12">
			<button type="submit" class="btn waves-effect waves-light col s12"><?php echo $this->Lang->write('account_forgot_form_reset_password');?></button>
		</div>
	</div>

</form>
