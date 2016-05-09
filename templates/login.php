
      <form class="login-form" action="/account/index/login/" method="post">
        <div class="row">
          <div class="input-field col s12">
            <h4><?php echo $this->Lang->write('account_login_headline');?></h4>
          </div>
        </div>
        <div class="row margin">
          <div class="input-field col s12">
            <i class="mdi-social-person-outline prefix"></i>
            <input type="text" name="username" value="" />
            <input type="hidden" name="finger" id="finger" value="" />
            <input type="hidden" name="token" value="<?php if(isset($this->tplVar['token'])) echo htmlspecialchars($this->tplVar['token'], ENT_QUOTES, 'UTF-8')?>" />
            
            <label for="username" class="center-align"><?php echo $this->Lang->write('account_login_username');?></label>
          </div>
        </div>
        <div class="row margin">
          <div class="input-field col s12">
            <i class="mdi-action-lock-outline prefix"></i>
            <input type="password" name="pwd" value="" />
            <label for="password"><?php echo $this->Lang->write('account_login_password');?></label>
          </div>
        </div>
        <div class="row">
          <div class="input-field col s12">
            <button class="btn waves-effect waves-light col s12" type="submit"><?php echo $this->Lang->write('account_login_button');?></button>	
          </div>
        </div>
        <div class="row">
          <div class="input-field col s12 m12 l6">
              <p class="margin right-align medium-small"><a href="/account/index/forgot/"><?php echo $this->Lang->write('account_link_forgot_password');?></a></p>
          </div>          
        </div>

      </form>