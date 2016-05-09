<form id="resetform" class="resetform" action="/account/index/reset/" method="post">
      
	<div class="row">
		<div class="input-field col s12">
			
				<h4><?php echo $this->Lang->write('account_reset_password_form_headline');?></h4>
				<p>
				<?php echo $this->Lang->write('account_reset_password_form_description');?>
				</p>
			
		</div>
	</div>
        
	<div class="row margin">
		<div class="input-field col s12">
			<label><?php echo $this->Lang->write('account_reset_password_form_pwdfield_label');?></label>
			<input type="password" name="pwd" placeholder="<?php echo $this->Lang->write('account_reset_password_form_pwdfield_label');?>" value="" />
			<input type="hidden" name="token" value="<?php if(isset($this->tplVar['token'])) echo htmlspecialchars($this->tplVar['token'], ENT_QUOTES, 'UTF-8')?>" />
		</div>        
	</div>
	
	<div class="row margin">
		<div class="input-field col s12">
			<label><?php echo $this->Lang->write('account_reset_password_form_pwdfield2_label');?></label>
			<input type="password" placeholder="<?php echo $this->Lang->write('account_reset_password_form_pwdfield2_label');?>" name="pwd2" value="" />
		</div>        
	</div>
	
	<div class="row margin">
		<div class="input-field col s12">
			<label><?php echo $this->Lang->write('account_reset_password_form_code_label');?></label>
			<input 
				type="text" 
				placeholder="<?php echo $this->Lang->write('account_reset_password_form_code_label');?>" 
				name="code" 
				value="<?php if(isset($this->tplVar['code'])) { print htmlspecialchars($this->tplVar['code'], ENT_QUOTES, 'UTF-8'); } ?>"
			/>
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