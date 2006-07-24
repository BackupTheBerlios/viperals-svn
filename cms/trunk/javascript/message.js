// Message Code by Ryan Marshall ( viperal1@gmail.com )

var system_message_array = new Array();

function system_message_hide(identifier)
{
	var message = document.getElementById(identifier);
	message.style.display = 'none';	
	
	window.clearInterval(system_message_array[identifier]);
}

function system_message_show(identifier)
{
	var message = document.getElementById(identifier);
	var message_table = document.getElementById(identifier + '_table');

	message.style.display = '';	
	message.style.left = (document.body.clientWidth - message_table.offsetWidth - 5) + 'px';

	window.clearInterval(system_message_array[identifier]);
	system_message_array[identifier] = window.setInterval("system_message_hide('" + identifier + "')", 3000);
}

function system_message_init(identifier, message_text)
{
	var message = document.getElementById(identifier);

	if (!message)
	{
		var message = document.createElement('div');
	}
	
	message.id = identifier;
	message.style.position	= (is_gecko) ? 'fixed' : 'absolute';
	message.style.top = '5px';
	message.style.left = '5px';
	message.style.zIndex = 1;
	message.style.width = '100%';
	message.style.display = 'none';	

	message.onclick = function()
	{
		system_message_hide(identifier);
	}

	message.innerHTML = '<table id="' + identifier + '_table" class="tablebg" border="0" cellpadding="4" cellspacing="1" >' +
	'<tbody><tr><td class="row2" align="center"><font class="error"><b>System Message</b></font></td></tr>' +
	'<tr><td class="row1" align="center">'+ message_text + '</td></tr></tbody></table>';

	message.style.cursor = 'pointer';
	document.body.insertBefore(message, document.nextSibling);

	system_message_array[identifier] = window.setInterval("system_message_show('" + identifier + "')", 500);
}