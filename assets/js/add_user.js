/***/

(function($) {

    $.AddUser = function(element, options) {

        var defaults = {
            //foo: 'bar',
           // onFoo: function() {}
        };

        var plugin = this;

        plugin.settings = {}; 

        var $element = $(element),
             element = element;

        // constructor method
        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);
            // code goes here
        };

        /**
         * get google analytics profiles
         */
        plugin.saveUser = function(elem, callback)
        {
        	var name = $('#group-name').val();
        	var desc = $('#group-description').val();
        	
        	 var request = $.ajax({
			    		url: '/account/ajax/creategroup/', 
			            type: "post",
			            
			            data: {
			            	name	: encodeURIComponent(name),
			            	desc	: encodeURIComponent(desc)
			            },
		        });
	          
	        request.done(function( response ) {
	        	
	        	callback(response);
	        });
	          
	        request.fail(function( response ) { 
	        	
	        });
	        
        }
        
        
        plugin.init();

    };
    
    
    $.fn.AddUser = function(options) 
    {
        return this.each(function() {
            if (undefined == $(this).data('AddUser')) {
                var plugin = new $.AddUser(this, options);
                $(this).data('AddUser', plugin);
            }
        });
    };

})(jQuery);


$(function(){
	
	var AddUser = $(this).AddUser();
	
	$('#create-user').on('submit', function(event) {
		event.preventDefault();
		
		AddUser.data('AddUser').saveUser($(this), function(response){
			console.log(response);
		});
	});
	
});