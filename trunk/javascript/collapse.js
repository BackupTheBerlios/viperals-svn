// Collapse Code by Ryan Marshall ( viperal1@gmail.com )

// If we use this on news, we need some kind of true gc.
// May not be possible tho without some php stuff

function get_cookie(Name)
{
	var cookie_name = Name + "=";
	var cookie_length = document.cookie.length;

	if (cookie_length > 0)
	{
		var offset = document.cookie.indexOf(cookie_name);

		if (offset != -1)
		{
			offset += cookie_name.length;
			var end = document.cookie.indexOf(';', offset);

			if (end == -1)
			{
				end = cookie_length;
			}

			return unescape(document.cookie.substring(offset, end));
		}
	}
	return null;
}

function set_cookie(name, value, expires)
{
	document.cookie = name + '=' + escape(value) + '; path=/; expires=' + expires.toGMTString();
}

function delete_cookie(name)
{
	document.cookie = name + '=' + '; expires=Thu, 01-Jan-70 00:00:01 GMT' +  '; path=/';
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

function switch_collapse_area(id, name)
{
	var area = document.getElementById(id);

	if (area.style.display == 'none')
	{
		area.style.display = '';
		var value = null;
	}
	else
	{
		area.style.display = 'none';
		var value = id;
	}

	// typeof name == 'undefined'
	if (!name)
	{
		name = 'collapsed_items';
	}

	// Best why to do it, if we don't want var name
	// it's a small script, shouldn't have any noticable speed impact
	var collapsed_cookie = get_cookie(name);
	var collapsed_items = new Array();

	if (collapsed_cookie != null)
	{
		collapsed_cookie = collapsed_cookie.split(':');
	
		for (var i in collapsed_cookie)
		{
			collapsed_items[i] = collapsed_cookie[i];
		}
	}

	var key = array_search(id, collapsed_items, false);

	if (key != null)
	{
		if (value != null)
		{
			collapsed_items[key] == key;
		}
		else
		{
			var tmp = new Array();
	
			for (var i in collapsed_items)
			{
				// We do it like this to get any duplicate values ( simple gc -- well not really )
				if (collapsed_items[i] != id)
				{
					tmp.push(collapsed_items[i]);
				}
			}

			collapsed_items = tmp;
		}
	}
	else
	{
		collapsed_items.push(id);
	}

	//alert(collapsed_items.join(':'));

	if (collapsed_items.length)
	{
		var expires = new Date()

		expires.setTime(expires.getTime() + 31536000000);
		set_cookie(name, collapsed_items.join(':'), expires)
	}
	else
	{
		delete_cookie(name)
	}
}