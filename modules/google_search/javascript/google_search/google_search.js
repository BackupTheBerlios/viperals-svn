// By Ryan Marshall ( viperal1@gmail.com )

function google_search()
{
	ajax = new core_ajax();

	if (!ajax)
	{
		return true;
	}

	var query = document.getElementById('query');
	var search_type = document.getElementById('search_type');

	var google_form = document.getElementById('google_form');
	var progress_bar = document.getElementById('google_progress_bar');
	var google_results = document.getElementById('google_results');

	var onreadystatechange = function()
	{
		if (ajax.state_ready() && ajax.responseText())
		{
			var area = document.getElementById('google_results');
			area.innerHTML = ajax.responseText();

			google_form.style.display = '';
			google_results.style.display = '';
			progress_bar.style.display = 'none';
			//query.value = '';
		}
	}

	ajax.onreadystatechange(onreadystatechange);

	google_results.style.display = 'none';
	google_form.style.display = 'none';
	progress_bar.style.display = '';

//alert(search_type.options[search_type.selectedIndex].value);
	ajax.send('ajax.php?mod=google_search', '&query=' + query.value + '&search_type=' + search_type.options[search_type.selectedIndex].value);

	return false;
}