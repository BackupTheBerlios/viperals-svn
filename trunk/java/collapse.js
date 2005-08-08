// Collapse Code by Ryan Marshall ( viperal1@gmail.com )

var collapsed_cookie = get_cookie('blocks_collapsed');

if (collapsed_cookie != null)
{
	var collapsed_cookie = collapsed_cookie.split(':');
	//blocks_collapsed.sort();
	//for (var i in blocks_collapsed)
	
	var length = collapsed_cookie.length

	for (var i = 0; i < length; i++)
	{
		blocks_collapsed[i] = blocks_collapsed.shift();
	}
}
else
{
	var blocks_collapsed = new Array();
}


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

function switch_collapse(id, min, max)
{
	var img = document.getElementById(id);

	if (img.style.display == 'none')
	{
		img.style.display = '';
		var value = null;
	}
	else
	{
		img.style.display = '';
		var value = id;
	}

	var key = array_search(id, blocks_collapsed, false);

	if (key != null)
	{
		if (value != null)
		{
			blocks_collapsed[key] == key;
		}
		else
		{
			var tmp = new array();
	
			for (var i in blocks_collapsed)
			{
				// We do it like this to get any duplicate values
				if (blocks_collapsed[i] != id)
				{
					tmp.push(id);
				}
			}

			// not sure if this works
			blocks_collapsed = tmp;
		}
	}
	else
	{
		blocks_collapsed.push(id);
	}

	if (blocks_collapsed.length)
	{
		var expires = new Date()

		expires.setTime(expires.getTime() + 31536000000);
		set_cookie('blocks_collapsed', blocks_collapsed.join(':'), expires)
	}
	else
	{
		delete_cookie('blocks_collapsed')
	}
}