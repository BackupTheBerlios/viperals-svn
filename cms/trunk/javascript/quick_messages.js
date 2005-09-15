// By Ryan Marshall ( viperal1@gmail.com )

function quick_message_submit()
{
	var message = document.getElementById('message');
	var poster_name = document.getElementById('poster_name');

	if (!poster_name)
	{
		poster_name = '';
	}

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
		}
	}

	ajax.onreadystatechange(onreadystatechange);

	ajax.send('index.php?mod=Quick_Message&mode=ajax_add', '&poster_name=' + poster_name.value + '&message=' + message.value);

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
		}
	}

	ajax.onreadystatechange(onreadystatechange);

	ajax.send('index.php?mod=Quick_Message&mode=ajax_refresh', null);
}