$(function(){
	
	$('#toggle-indi').on('click', function() {
		$('#indi').toggle();
		
		var disp = $('#indi').css('display');
		
		if(disp == 'none') {
			$('#toggle-indi').html($('#toggle-indi').data('show'));
		} else {
			$('#toggle-indi').html($('#toggle-indi').data('hide'));
		}
	});
	
	// set fingerprint to login form
	getFingerprint(function(response){
		console.log(response);
		if(response) {
			$('#finger').val(response);
		}
	})
	
	
	$('.del-group').on('click', function(event) {
		
		var href = $(this).attr('href');
		event.preventDefault();
		
		$('#confirmation-modal').find('.modal-content h4').html(defaultmsg.confirm_headline.msg);
		$('#confirmation-modal').find('.modal-content p').html(defaultmsg.confirm_paragraph.msg);
		
		$('#confirmation-modal').openModal({
			dismissible: true, // Modal can be dismissed by clicking outside of the modal
			opacity: .5, // Opacity of modal background
			in_duration: 300, // Transition in duration
			out_duration: 200, // Transition out duration
			ready: function() { 
			}, // Callback for Modal open
			complete: function(response) { } // Callback for Modal close
		});
		
		$('#confirmation-modal .modal-footer .agree').on('click', function(){
			if(href != '') {
				window.location.href = href;
			}
		});
		
	});
	
	/**
	 * Validate setting update for user
	 */
	$('form#update-settings').on('submit', function(event){
		
		var langwrap = $('#language');
		var radios = langwrap.find('input:radio:checked');
		langwrap.removeClass('errorborder');
		langwrap.find('.valerror').html('');
		
		if(radios.length < 1) {
			
			var msg = 'Please fill out the field.';
			
			if(typeof(errmsg) == 'object' && typeof(errmsg.language) == 'object' && errmsg.language.error) {
				msg = errmsg.language.error;
			}
			
			langwrap.find('.valerror').html(msg);
			langwrap.addClass('errorborder');
			event.preventDefault();
		}
		
		var zone = $('#timezone-wrapper');
		var selected = $('#timezone');
		zone.removeClass('errorborder');
		zone.find('.valerror').html('');
		
		if(selected.val() == '') {
			
			var msg = 'Please fill out the field.';
			
			if(typeof(errmsg) == 'object' && typeof(errmsg.timezone) == 'object' && errmsg.timezone.error) {
				msg = errmsg.timezone.error;
			}
			
			zone.find('.valerror').html(msg);
			zone.addClass('errorborder');
			event.preventDefault();
		}
		
	});
	
	/**
	 * Validate create group form
	 */
	$('form#create-group').on('submit', function(event){
		
		// --------- name ------------
		var name = $('#name');
		name.removeClass('errorborder');
		name.parent().find('.valerror').html('');

		var msg = 'Please fill out the field.';
		
		if(typeof(errmsg) == 'object' && typeof(errmsg.name) == 'object' && errmsg.name.error) {
			msg = errmsg.name.error;
		}
		
		if(name.val() == '') {
			name.parent().find('.valerror').html(msg);
			name.addClass('errorborder');
			event.preventDefault();
		}
		
		// --------- username ------------
		var desc = $('#desc');
		desc.removeClass('errorborder');
		desc.parent().find('.valerror').html('');

		var msg = 'Please fill out the field.';
		
		if(typeof(errmsg) == 'object' && typeof(errmsg.desc) == 'object' && errmsg.desc.error) {
			msg = errmsg.desc.error;
		}
		
		if(desc.val() == '') {
			desc.parent().find('.valerror').html(msg);
			desc.addClass('errorborder');
			event.preventDefault();
		}
		
//		var permsis = $('#permsis');
//		var perms = $('input:checkbox:checked');
//			
//		if(perms.length < 1) {
//			
//			var msg = 'Please fill out the field.';
//			
//			if(typeof(errmsg) == 'object' && typeof(errmsg.perms) == 'object' && errmsg.perms.error) {
//				msg = errmsg.perms.error;
//			}
//			
//			permsis.find('.valerror').html(msg);
//			permsis.addClass('errorborder');
//			event.preventDefault();
//		}
		
	});
	
	/**
	 * Validate edit-user-base form
	 */
	$('form#edit-user-base, form#create-user-base').on('submit', function(event){
		
		var target = $(event.target).attr('id');
		
		// --------- username ------------
		var username = $('#username');
		username.removeClass('errorborder');
		username.parent().find('.valerror').html('');

		var msg = 'Please fill out the field.';
		
		if(typeof(errmsg) == 'object' && typeof(errmsg.username) == 'object' && errmsg.username.error) {
			msg = errmsg.username.error;
		}
		
		if(username.val() == '') {
			username.parent().find('.valerror').html(msg);
			username.addClass('errorborder');
			event.preventDefault();
		}
		
		// -------- end username -----
		
		// --------- email ------------
		var email = $('#email');
		email.removeClass('errorborder');
		email.parent().find('.valerror').html('');

		var msg = 'Please fill out the field.';
		
		if(typeof(errmsg) == 'object' && typeof(errmsg.email) == 'object' && errmsg.email.error) {
			msg = errmsg.email.error;
		}
		
		if(email.val() == '' || validateEmail(email.val()) !== true) {
			email.parent().find('.valerror').html(msg);
			email.addClass('errorborder');
			event.preventDefault();
		}
		
		// -------- end email -----
		
		if(target == 'create-user-base') {
			
			if(typeof(errmsg) == 'object' && typeof(errmsg.passwordempty) == 'object' && errmsg.passwordempty.error) {
				msg = errmsg.passwordempty.error;
			}
			
			if(password.val() == '') {
				password.parent().find('.valerror').html(msg);
				password.addClass('errorborder');
				event.preventDefault();
			}
		}
		
		// -------- end password -----
		
		
	});

});