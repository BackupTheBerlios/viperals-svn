// Collapse Code by Ryan Marshall ( viperal1@gmail.com )

/*
* To do:
*
**	Auto opening on mouse over maybe ( closeing on mouse out ) ?	
*/

if (typeof slider_id == 'undefined')
{
	var slider_id = new Array();
	var slider_height = new Array();
}

function get_cookie(cookie_name)
{
	var cookie_prefix = cookie_name + "=";
	var cookie_length = document.cookie.length;

	if (cookie_length > 0)
	{
		var start = document.cookie.indexOf(cookie_prefix);

		if (start != -1)
		{
			start += cookie_prefix.length;
			var end = document.cookie.indexOf(';', start);

			if (end == -1)
			{
				end = cookie_length;
			}

			return unescape(document.cookie.substring(start, end));
		}
	}

	return null;
}

function set_cookie(cookie_name, value, expires)
{
	document.cookie = cookie_name + '=' + escape(value) + '; path=/; expires=' + expires.toGMTString();
}

function delete_cookie(cookie_name)
{
	document.cookie = cookie_name + '=' + '; expires=Thu, 01-Jan-70 00:00:01 GMT' +  '; path=/';
}

function array_search(needle, haystack, strict)
{
	for (var i in haystack)
	{
		if (haystack[i] == needle)
		{
			return i;
		}
	}

	return null;
}

function switch_collapse(identifier, no_slide, cookie)
{
	var area = document.getElementById(identifier);
	var img = document.getElementById(identifier + '_img');

	var item = document.getElementById(identifier + '_item');

	if (area.style.display == 'none')
	{
		switch_collapse_save(identifier, false, cookie);

		if (img)
		{
			img.src = img.src.replace('show', 'hide');
		}

		if (item)
		{
			item.className = item.className.replace('show', 'hide');
		}

		area.style.display = '';

		if (!no_slide)
		{
			area.style.height = '';

			var height = area.offsetHeight;
	
			area.style.overflow	= 'hidden';
			area.style.height = '0px';

			start_slide_block(identifier, 'out', height);
		}
	}
	else
	{
		switch_collapse_save(identifier, true, cookie);
		
		if (img)
		{
			img.src = img.src.replace('hide', 'show');
		}

		if (item)
		{
			item.className = item.className.replace('hide', 'show');
		}

		if (no_slide)
		{
			area.style.display = 'none';
		}
		else
		{
			var height = area.offsetHeight;
			area.style.overflow	= 'hidden';
	
			start_slide_block(identifier, 'in', height);
		}
	}
}

function switch_collapse_save(identifier, save, cookie_name)
{
	// typeof name == 'undefined'
	if (!cookie_name)
	{
		cookie_name = 'collapsed_items';
	}

	//alert(cookie_name);

	var collapsed_cookie = get_cookie(cookie_name);
	var collapsed_items = new Array();
	var set = false;

	if (collapsed_cookie != null)
	{
		collapsed_cookie = collapsed_cookie.split(':');

		for (var i in collapsed_cookie)
		{
			if (collapsed_cookie[i] == identifier)
			{
				if (set == false)
				{
					set = true;

					if (save != false)
					{
						collapsed_items.push(identifier);
					}
				}
			}
			else
			{
				collapsed_items.push(collapsed_cookie[i]);
			}
		}
	}

	if (set == false && save != false)
	{
		collapsed_items.push(identifier);
	}

	//alert(collapsed_items.join(':'));
	if (collapsed_items.length)
	{
		var expires = new Date()

		expires.setTime(expires.getTime() + 31536000000);
		set_cookie(cookie_name, collapsed_items.join(':'), expires)
	}
	else
	{
		delete_cookie(cookie_name)
	}
}

function slide_block(identifier, option, height)
{
	var area = document.getElementById(identifier);
	var step = Math.ceil(height / 25);

	if (step < 6)
	{
		step = 6;
	}

	if (option == 'in')
	{
		slider_height[identifier] -= step;
		
		if (slider_height[identifier] <= 0)
		{
			area.style.display = 'none';
			area.style.height = '';

			stop_slide_block(identifier);
			
			return;
		}
	}
	else
	{
		slider_height[identifier] += step;

		if (slider_height[identifier] >= height)
		{
			area.style.height = '';
			stop_slide_block(identifier);

			return;
		}
	}

	area.style.height = slider_height[identifier]+'px';
}

function start_slide_block(identifier, option, height)
{
	stop_slide_block(identifier);

	if (option == 'in')
	{
		slider_height[identifier] = height;
	}
	else
	{
		option = 'out';
		slider_height[identifier] = 0;
	}

	slider_id[identifier] = window.setInterval("slide_block('"+identifier+"','"+option+"', "+height+")", 10);
}

function stop_slide_block(identifier)
{
	if (slider_id[identifier] && slider_id[identifier] != null)
	{
		window.clearInterval(slider_id[identifier]);
		slider_id[identifier] = null;
	}
}