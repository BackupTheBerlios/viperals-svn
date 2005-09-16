// By Ryan Marshall ( viperal1@gmail.com )

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
	document.cookie = cookie_name + '=' + escape(value) + '; path='+cms_cookie_path+'; expires=' + expires.toGMTString();
}

function delete_cookie(cookie_name)
{
	document.cookie = cookie_name + '=' + '; expires=Thu, 01-Jan-70 00:00:01 GMT' +  '; path='+cms_cookie_path+'';
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

function core_ajax()
{
	this.request = null;
	this.async = true;
}

core_ajax.prototype.init = function()
{
	try
	{
		this.request = new XMLHttpRequest();

		return true;
	}
	catch(e)
	{
		try
		{
			this.request = new ActiveXObject('Microsoft.XMLHTTP');

			return true;
		}
		catch(e)
		{
			return false;
		}
	}
}

core_ajax.prototype.send = function(url, data)
{
	if (!this.request)
	{
		this.init();
	}

	url += '&sid='+cms_session_id;

	this.request.open('POST', url, this.async);
	this.request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	this.request.send(data);
}

core_ajax.prototype.onreadystatechange = function(event)
{
	if (!this.request)
	{
		this.init();
	}

	if (typeof(event) == 'function')
	{
		this.request.onreadystatechange = event;
	}
}

core_ajax.prototype.responseText = function()
{
	return this.request.responseText;
}

core_ajax.prototype.state_not_ready = function()
{
	return (this.request.readyState < 4);
}

core_ajax.prototype.state_ready = function()
{
	return (this.request.readyState == 4 && this.request.status == 200);
}