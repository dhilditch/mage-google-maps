jQuery(document).ready(function($) {
$('.btn-background').click(function() {  
	field_src = $(this).parent().prev('.upload');
	field_w = $(this).parent().parent().parent().find('.upload_w');
	field_h = $(this).parent().parent().parent().find('.upload_h');
	field_id = $(this).parent().parent().parent().find('.upload_id');
	field_name = $(this).parent().prev('.upload').attr('id');
	window_name = $('label[for="' + field_name + '"]').html();
	tb_show(window_name, 'media-upload.php?referer=mage-upload&amp;type=image&amp;TB_iframe=true&amp;post_id=0', false);
	return false;  
}); 
window.send_to_editor = function(html) { 
	var image_url = $('img',html).attr('src');
	var image_w = $('img',html).attr('width');
	var image_h = $('img',html).attr('height');
	var image_id = $('img',html).attr('class').match(/\d+/);
    $(field_src).val(image_url);
	$(field_w).val(image_w);
	$(field_h).val(image_h);
	$(field_id).val(image_id);
    tb_remove();	
	$('.'+field_name+' img').attr('src',image_url);  
	$('#update').trigger('click'); 	 
}  
$(".pop").each(function() {
    var $pElem= $(this);
    $pElem.popover(
        {
          title: getPopTitle($pElem.attr("id")),
          content: getPopContent($pElem.attr("id")),
		  container: 'body'
        }
    );
});
function getPopTitle(target) { return $("#" + target + "_content > div.popTitle").html(); };		
function getPopContent(target) { return $("#" + target + "_content > div.popContent").html(); };
$('.updated').delay(1000).fadeOut(1000);
$('.activator').click(function(){
	if ($(this).attr('checked')) {		
		$(this).parent().addClass('active');	
	} else {
		$(this).parent().removeClass('active');
	}
});
});
