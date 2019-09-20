jQuery(document).ready(function($) {
	// Listen for changes to the category field 
	$( 'body' ).on("change", ".editor-post-taxonomies__hierarchical-terms-input", function(event) {
		var data = {
			category: (event.target.id.substr(41, event.target.id.length)),
		};

		$.ajax( {
			type: 'POST',
			url: job_manager_ajax_company_data.ajax_url.toString().replace( '%%endpoint%%', 'get_company_data' ),
			data: data,
			success: function( result ) {
				if ( result ) {
					try {
						result.data = data;
						$.each(result, function(k, v) {
						    if(!event.target.checked) {
						    	$( '#_' + k ).prop("disabled", false);
						    	$( '#_' + k ).val("");
						    }
						    else {
						    	$( '#_' + k ).prop("disabled", true); 
						    	$( '#_' + k ).val(v);
						    }
						});
						
					} catch ( err ) {
						if ( window.console ) {
							window.console.log( err );
						}
					}
				}
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
	});
});
