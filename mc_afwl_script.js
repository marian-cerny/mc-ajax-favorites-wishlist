var $ = jQuery.noConflict();

$( document ).ready( function()
{

$( 'body' ).delegate( '.afwl-button', 'click', function( event )
{
	// GET THE CLICKED LINK ELEMENT
	var clicked_link = $(this);
		
	// GET THE CLICKED WATCH ID
	var item_id = clicked_link.attr('id').replace( 'afwl-button-', '' )
	
	// GET ACTION - ADDING OR REMOVING
	var action;
	if ( clicked_link.hasClass('add') )
		action = 'add'
	else
		action= 'remove';
		
	// PREVENT DEFAULT CLICK ACTION
	event.preventDefault();
	// CHANGE LINK TO LOADER ANIMATION
	clicked_link.html("<img src='" + afwl_ajax_vars.loader_src + "' alt='loading...' />'");
	
	$.ajax({
		url: afwl_ajax_vars.ajax_url,
		type: 'POST',
		data: 'action=button_click&iid=' + item_id,
		success: function( html ) 
		{ 	
			// CHANGE LINK TEXT AND ATTRIBUTES WHEN SUCCESSFULLY ADDED
			// INCREASE / DECREASE COUNT VALUES
			if ( action == 'add' )
			{
				clicked_link.removeClass( 'add' );
				clicked_link.addClass( 'remove' );
				
				clicked_link.html( 
					$( clicked_link )
						.siblings( '.afwl-button-hidden' )
						.find( ' .remove' )
						.html() 
					);
					
				clicked_link.attr( 
					'title', 
					$( clicked_link )
						.siblings( '.afwl-button-hidden' )
						.find( ' .remove_title' )
						.html() 
				);
				
				change_count( 'inc' );
			}
			else
			{
				clicked_link.removeClass( 'remove' );
				clicked_link.addClass( 'add' );
				
				clicked_link.html( 
					$( clicked_link )
						.siblings( '.afwl-button-hidden' )
						.find( ' .add' )
						.html() 
					);
					
				clicked_link.attr( 
					'title', 
					$( clicked_link )
						.siblings( '.afwl-button-hidden' )
						.find( ' .add_title' )
						.html() 
				);
				
				change_count( 'dec' );
			}
		}
	});
	
} );


function change_count( action )
{ 
	// GET THE DESIRED ELEMENTS
	var element = $( '.afwl-count' );
	
	// IF NO ELEMENTS EXIST, STOP
	if ( element.length == 0 )
		return false;
	
	// GET CURRENT VALUE
	var current_count = parseInt( element.html() );
	
	// ASSIGN NEW VALUE
	if ( action == 'inc' )
		element.html( ++current_count );
	else
		element.html( --current_count );
}


} );