// By Ryan Marshall ( viperal1@gmail.com )

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