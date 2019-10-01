jQuery(document).ready(function($) {

	function autofillFields(event) {
		var data = {
			category: (event.target.id.substr(41, event.target.id.length)),
		};

		console.log("category id: " + (event.target.id.substr(41, event.target.id.length)));

		$.ajax( {
			type: 'POST',
			url: job_manager_ajax_company_data.ajax_url.toString().replace( '%%endpoint%%', 'get_company_data' ),
			data: data,
			success: function( result ) {
				if ( result ) {
					try {
						result.data = data;
						console.log(result);
						$.each(result, function(k, v) {
						    if(!event.target.checked) {
						    	$( '#_' + k ).prop("readonly", false);
						    	$( '#_' + k ).val("");
						    }
						    else {
						    	$( '#_' + k ).prop("readonly", true); 
						    	$( '#_' + k ).val(v);
						    }
						});
						
					} catch ( err ) {
						if ( window.console ) {
							window.console.log( err );
						}
					}
				}
				else
					console.log('ajax returned null result');
			},
			error: function( jqXHR, textStatus, error ) {
				if ( window.console && 'abort' !== textStatus ) {
					window.console.log( textStatus + ': ' + error );
				}
			},
			statusCode: {
				404: function() {
					if ( window.console ) {
						window.console.log(
							'Error 404: Ajax Endpoint cannot be reached. Go to Settings > Permalinks and save to resolve.'
						);
					}
				},
			},
		});
	}

	var termsListClass = 'editor-post-taxonomies__hierarchical-terms-input';

	// Listen for changes to the category field 
	$(document.body).on('change', '.editor-post-taxonomies__hierarchical-terms-input[type=checkbox]', function(event) {
		console.log(event);
		autofillFields(event);
	});
});
