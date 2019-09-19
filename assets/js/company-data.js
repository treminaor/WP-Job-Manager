jQuery(document).ready(function($) {

	// Listen for changes to the category field 
	$( '#job_category' ).on( 'select2:select select2:unselect', function() {
		var data = {
			category: $( '#job_category' ).val(),
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
						    if(v === false) {
						    	$( '#' + k ).prop("disabled", false);
						    	$( '#' + k ).val("");
						    }
						    else {
						    	$( '#' + k ).prop("disabled", true); 
						    	$( '#' + k ).val(v);
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
