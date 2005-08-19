// Menu Code by Ryan Marshall ( viperal1@gmail.com )

/*
* To do:
*
*	Add some kind of slide affect
*	Menu generator in javascript
*	Auto closing of menu...
*	
*/

var active_menu = false;

function get_offsets(element)
{
	var offsets = new Array();

	offsets['top']	= element.offsetTop;
	offsets['left']	= element.offsetLeft;

	while ((element = element.offsetParent) != null)
	{
		offsets['top']	+= element.offsetTop;
		offsets['left']	+= element.offsetLeft;
	}

	return offsets;
}

function menu_init(object_name)
{
	var menu		= document.getElementById(object_name + '_menu');
	var object		= document.getElementById(object_name);

	if (menu == null|| object == null)
	{
		return;
	}

	object.onclick = function()
	{
		/* Open or close the menu */
		if (menu.style.display == 'none')
		{
			menu_show(object_name);
		}
		else
		{
			menu_hide(object_name);
		}

		return false;
	}
	
/*
	need to read more to get these working :-(

	object.onmouseover = function()
	{
		//menu_show(object_name);
	}
	
	object.onmouseout = function(e)
	{
		menu_hide(object_name);
	}

	menu.onmouseout = function(e)
	{
		menu_hide(object_name);
	}

	menu.onmouseover = function(e)
	{
		this.onmouseout = function(e)
		{
			menu_hide(object_name);
		}

		e.preventDefault();
		e.stopPropagation();
	}

	menu.onmouseout = function()
	{
		menu_hide(object_name);
	}
*/

	menu.style.display	= 'none';
	menu.style.position	= 'absolute';
	menu.style.zIndex = 1000;
	object.style.cursor = 'pointer';
}

function menu_show(object_name)
{
	var menu		= document.getElementById(object_name + '_menu');
	var object		= document.getElementById(object_name);

	if (menu == null|| object == null)
	{
		return;
	}

	// If a menu is open, lets close it
	if (active_menu)
	{
		menu_hide(active_menu);
	}

	active_menu = object_name;

	// best to do this everytime, incase the windows is resized
	var object_offsets = get_offsets(object);

	menu.style.display	= '';
	
//Move to init function, once done
	menu.style.position	= 'absolute';

	if ((object_offsets['left'] + menu.offsetWidth) > document.body.clientWidth)
	{
		// It to wide to show on the right so we have to put it on the left
		menu.style.left = (object_offsets['left'] + object.offsetWidth - menu.offsetWidth) + 'px';
	}
	else
	{
		menu.style.left = object_offsets['left'] + 'px';
	}

	menu.style.top = (object_offsets['top'] + object.offsetHeight) + 'px';
}

function menu_hide(object_name)
{
	if (!object_name)
	{
		object_name = active_menu;
	}

	var menu		= document.getElementById(object_name + '_menu');
	var object		= document.getElementById(object_name);

	if (active_menu == object_name)
	{
		active_menu = false;
	}
	
	menu.style.display = 'none';	
}