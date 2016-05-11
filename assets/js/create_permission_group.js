/***/

(function($) {

    $.CreateGroup = function(element, options) {

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
        plugin.saveGroup = function(elem, callback)
        {
        	var name 	= $('#group-name').val();
        	var desc 	= $('#group-description').val();
        	var groups 	= $('#permission-groups option:selected').val();
        	
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
    
    
    $.fn.CreateGroup = function(options) 
    {
        return this.each(function() {
            if (undefined == $(this).data('CreateGroup')) {
                var plugin = new $.CreateGroup(this, options);
                $(this).data('CreateGroup', plugin);
            }
        });
    };

})(jQuery);


$(function(){
	
	var CreateGroup = $(this).CreateGroup();
	
	$('#create-group').on('submit', function(event) {
		event.preventDefault();
		
		CreateGroup.data('CreateGroup').saveGroup($(this), function(response){
			console.log(response);
		});
		
		
	});
	
});