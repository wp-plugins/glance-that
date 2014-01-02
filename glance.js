/**
 * Theme cards for displaying excerpt and post content.
 */
jQuery(document).ready(function( $ ) {

	$('#visible-icon').click(
		function() {
			if( $('#iconlist').is(':visible') ) {
				$('#iconlist').hide();
			} else {
				$('#iconlist').css('display','block');
				$('#dashboard_right_now .inside').css('overflow','visible');
			}
		});

	$('.dashicon-option').click(
		function() {
			$('#visible-icon').attr('alt',$(this).attr('alt'));
			$('#visible-icon').removeClass();
			$('#visible-icon').addClass($(this).attr('class'));
			$('input[data-dashicon="selected"]').attr('value',$(this).attr('alt'));
			$('#iconlist').hide();
		})

	$('#show-gt-form').click(
		function() {
			$('#gt-form').css('display','block');
			$(this).css('display','none');
			});

	$('#remove-gt-item').click(
		function() {
			$('#gt-form').attr('action','index.php?action=remove-gt-item');
		});
});
