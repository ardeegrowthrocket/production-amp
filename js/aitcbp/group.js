Event.observe(window, 'load', function() {
	aitcbpHandleWebsites();
	Event.observe('all_websites', 'change', aitcbpHandleWebsites);
});

function aitcbpHandleWebsites()
{
	allWebsites = $('all_websites').value;
	
	if (allWebsites == '1') {
		$('website_ids').disable();
	}
	else {
		$('website_ids').enable();
	}
}