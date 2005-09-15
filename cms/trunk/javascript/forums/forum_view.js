// By Ryan Marshall ( viperal1@gmail.com )

function tite_edit_init(id, mode)
{
	var area = document.getElementById(mode + '_' + id)

	if (!area)
	{
		return
	}

	area.onmouseover = '';
	area.ondblclick = function()
	{
		tite_edit_add(id, mode);
	}
}

function tite_edit_add(id, mode)
{
	if (document.getElementById(mode + '_input_' + id))
	{
		return;
	}

	var area = document.getElementById(mode + '_' + id);
	var title = document.getElementById(mode + '_name_' + id);
	var input = document.createElement('input');

	title.style.display ='none';

	input.type = 'text';
	input.size = 50;
	input.className = 'post';
	input.value = title.innerHTML;
	input.id = mode + '_input_' + id;
	input.onblur =	function()
	{
		tite_edit_onblur(id, mode);
	}

	input.onkeypress = function(e)
	{
		switch (e.keyCode)
		{
			case 13:
			{
				ajax = new core_ajax();

				var onreadystatechange = function()
				{
					if (ajax.state_ready() && ajax.responseText())
					{
						title.innerHTML = ajax.responseText();
					}
				}
				
				ajax.onreadystatechange(onreadystatechange);

				var input = document.getElementById(mode + '_input_' + id);

				ajax.send('index.php?mod=Forums&file=ajax', '&mode=' + mode + '_edit_title&title=' + input.value + '&id=' + id);

				tite_edit_remove(id, mode);
			}
	
			case 27:
			{
				tite_edit_remove(id, mode);
			}
		}
	}

	if (input = area.insertBefore(input, title))
	{
		input.focus();
	}

	return false;
}

function tite_edit_remove(id, mode)
{
	var area = document.getElementById(mode + '_' + id);
	var title = document.getElementById(mode + '_name_' + id);
	var input = document.getElementById(mode + '_input_' + id);

	if (!input)
	{
		return;
	}

	title.style.display = '';

	area.removeChild(input);
}

function tite_edit_onblur(id, mode)
{
	var input = document.getElementById(mode + '_input_' + id);
	var title = document.getElementById(mode + '_name_' + id);

	if (!input)
	{
		return;
	}

	if (input.value == title.innerHTML)
	{
		tite_edit_remove(id, mode);

		return;
	}

	ajax = new core_ajax();

	var onreadystatechange = function()
	{
		if (ajax.state_ready() && ajax.responseText())
		{
			title.innerHTML = ajax.responseText();
		}
	}
	
	ajax.onreadystatechange(onreadystatechange);

	var input = document.getElementById(mode + '_input_' + id);

	ajax.send('index.php?mod=Forums&file=ajax', '&mode=' + mode + '_edit_title&title=' + input.value + '&id=' + id);

	tite_edit_remove(id, mode);
}