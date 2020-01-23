jQuery(document).ready(function($) {

	function autofillFields() {
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
							if(k == 'company_logo_src') {
								if( $( '.job-manager-uploaded-file' ).length ) {
										$( '.job-manager-uploaded-file' ).remove();
									} 
								if(v) {
									$( '.job-manager-uploaded-files' ).append( '<div class="job-manager-uploaded-file"><div class="job-manager-uploaded-file-preview"><img src="' + v + '"/></div></div' );
								}
							}
						    else if(v === false) {
						    	$( '#' + k ).prop("readonly", false); 
						    	if( !$( '#' + k ).is( 'input[type=file]' ) ) {
						    		$( '#' + k ).val("");
						    	} else {
						    		$( '#' + k ).prop("disabled", false); 
						    	}
						    } else {
						    	$( '#' + k ).prop("readonly", true); 
						    	if( !$( '#' + k ).is( 'input[type=file]' ) ) {
							    	$( '#' + k ).val(v);
							    } else {
							    	$( '#' + k ).prop("disabled", true); 
							    }
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
	};

	autofillFields();
	
	// Listen for changes to the category field 
	$( '#job_category' ).on( 'select2:select select2:unselect', function() {
		autofillFields();
	});
});
