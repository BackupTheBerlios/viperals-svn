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

function switch_collapse(id, name)
{
	var area = document.getElementById(id);

	if (area.style.display == 'none')
	{
		area.style.display = '';
		switch_collapse_save(id, false, name);
	}
	else
	{
		area.style.display = 'none';
		switch_collapse_save(id, id, name);
	}
}

function switch_collapse_img(id, img_show, img_hide, name)
{
	var area = document.getElementById(id);
	var img = document.getElementById(id+'_img');

	if (area.style.display == 'none')
	{
		area.style.display = '';
		img.src = img_show;

		switch_collapse_save(id, false, name);
	}
	else
	{
		area.style.display = 'none';
		img.src = img_hide;

		switch_collapse_save(id, id, name);
	}
}

function switch_collapse_save(id, save, name)
{
	// typeof name == 'undefined'
	if (!name)
	{
		name = 'collapsed_items';
	}

	//alert(name);

	var collapsed_cookie = get_cookie(name);
	var collapsed_items = new Array();
	var set = false;

	if (collapsed_cookie != null)
	{
		collapsed_cookie = collapsed_cookie.split(':');

		for (var i in collapsed_cookie)
		{
			if (collapsed_cookie[i] == id)
			{
				if (set == false)
				{
					set = true;

					if (save != false)
					{
						collapsed_items.push(id);
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