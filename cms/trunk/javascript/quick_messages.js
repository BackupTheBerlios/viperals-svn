// By Ryan Marshall ( viperal1@gmail.com )

function quick_message_submit()
{
	var message = document.getElementById('quick_message');
	var poster_name = document.getElementById('poster_name');

	ajax = new core_ajax();

	if (!ajax)
	{
		return true;
	}

	var onreadystatechange = function()
	{
		if (ajax.state_ready() && ajax.responseText())
		{
			var area = document.getElementById('qm_block');
			area.innerHTML = ajax.responseText();

			message.value = '';

			system_message_init('quickmessage', 'Quick Message Posted');
		}
	}

	ajax.onreadystatechange(onreadystatechange);

	poster_name = (poster_name) ? 'poster_name=' + poster_name.value : '';

	ajax.send('ajax.php?mod=quick_message&mode=ajax_add', poster_name + '&message=' + message.value);

	return false;
}

function quick_message_refresh()
{
	ajax = new core_ajax();

	var onreadystatechange = function()
	{
		if (ajax.state_ready() && ajax.responseText())
		{
			var area = document.getElementById('qm_block');
			area.innerHTML = ajax.responseText();
			
			system_message_init('quickmessage', 'Quick Message Refresh Complete');
		}
	}

	ajax.onreadystatechange(onreadystatechange);

	ajax.send('ajax.php?mod=quick_message&mode=ajax_refresh', null);
}