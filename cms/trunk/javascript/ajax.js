// By Ryan Marshall ( viperal1@gmail.com )

function core_ajax()
{
	this.request = false;
	this.async = true;
}

core_ajax.prototype.init = function()
{
	try
	{
		this.request = new XMLHttpRequest();
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