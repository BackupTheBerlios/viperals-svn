/*
	JSCookMenu v1.25.  (c) Copyright 2002 by Heng Yuan

	Permission is hereby granted, free of charge, to any person obtaining a
	copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation
	the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included
	in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	ITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
	DEALINGS IN THE SOFTWARE.
*/

var _cmIDCount = 0;
var _cmIDName = 'cmSubMenuID';
var _cmTimeOut = null;
var _cmCurrentItem = null;
var _cmNoAction = new Object ();
var _cmSplit = new Object ();
var _cmItemList = new Array ();
var _cmNodeProperties =
{
  	mainFolderLeft: '',
  	mainFolderRight: '',
	mainItemLeft: '',
	mainItemRight: '',
	folderLeft: '',
	folderRight: '',
	itemLeft: '',
	itemRight: '',
	mainSpacing: 0,
	subSpacing: 0,
	delay: 500
};
function cmNewID ()
{
	return _cmIDName + (++_cmIDCount);
}
function cmActionItem (item, prefix, isMain, idSub, orient, nodeProperties)
{
	// var index = _cmItemList.push (item) - 1;
	_cmItemList[_cmItemList.length] = item;
	var index = _cmItemList.length - 1;
	idSub = (!idSub) ? 'null' : ('\'' + idSub + '\'');
	orient = '\'' + orient + '\'';
	prefix = '\'' + prefix + '\'';
	return ' onmouseover="cmItemMouseOver (this,' + prefix + ',' + isMain + ',' + idSub + ',' + orient + ',' + index + ')" onmouseout="cmItemMouseOut (this,' + nodeProperties.delay + ')" onmousedown="cmItemMouseDown (this,' + index + ')" onmouseup="cmItemMouseUp (this,' + index + ')"';
}

function cmNoActionItem (item, prefix)
{
	return item[1];
}

function cmSplitItem (prefix, isMain, vertical)
{
	var classStr = 'cm' + prefix;
	if (isMain)
	{
		classStr += 'Main';
		if (vertical)
			classStr += 'HSplit';
		else
			classStr += 'VSplit';
	}
	else
		classStr += 'HSplit';
	var item = eval (classStr);
	return cmNoActionItem (item, prefix);
}
function cmDrawSubMenu (subMenu, prefix, id, orient, nodeProperties)
{
	var str = '<div class="' + prefix + 'SubMenu" id="' + id + '"><table summary="sub menu" cellspacing="' + nodeProperties.subSpacing + '" class="' + prefix + 'SubMenuTable">';
	var strSub = '';

	var item;
	var idSub;
	var hasChild;

	var i;

	var classStr;

	for (i = 5; i < subMenu.length; ++i)
	{
		item = subMenu[i];
		if (!item)
			continue;

		hasChild = (item.length > 5);
		idSub = hasChild ? cmNewID () : null;

		str += '<tr class="' + prefix + 'MenuItem"' + cmActionItem (item, prefix, 0, idSub, orient, nodeProperties) + '>';

		if (item == _cmSplit)
		{
			str += cmSplitItem (prefix, 0, true);
			str += '</tr>';
			continue;
		}

		if (item[0] == _cmNoAction)
		{
			str += cmNoActionItem (item, prefix);
			str += '</tr>';
			continue;
		}

		classStr = prefix + 'Menu';
		classStr += hasChild ? 'Folder' : 'Item';

		str += '<td class="' + classStr + 'Left">';

		if (item[0] != null && item[0] != _cmNoAction)
			str += item[0];
		else
			str += hasChild ? nodeProperties.folderLeft : nodeProperties.itemLeft;

		str += '<td class="' + classStr + 'Text">' + item[1];

		str += '<td class="' + classStr + 'Right">';

		if (hasChild)
		{
			str += nodeProperties.folderRight;
			strSub += cmDrawSubMenu (item, prefix, idSub, orient, nodeProperties);
		}
		else
			str += nodeProperties.itemRight;
		str += '</td></tr>';
	}

	str += '</table></div>' + strSub;
	return str;
}
function cmDraw (id, menu, orient, nodeProperties, prefix)
{
	var obj = cmGetObject (id);

	if (!nodeProperties)
		nodeProperties = _cmNodeProperties;
	if (!prefix)
		prefix = '';

	var str = '<table summary="main menu" class="' + prefix + 'Menu" cellspacing="' + nodeProperties.mainSpacing + '">';
	var strSub = '';

	if (!orient)
		orient = 'hbr';

	var orientStr = String (orient);
	var orientSub;
	var vertical;

	if (orientStr.charAt (0) == 'h')
	{
		orientSub = 'v' + orientStr.substr (1, 2);
		str += '<tr>';
		vertical = false;
	}
	else
	{
		orientSub = 'v' + orientStr.substr (1, 2);
		vertical = true;
	}

	var i;
	var item;
	var idSub;
	var hasChild;

	var classStr;

	for (i = 0; i < menu.length; ++i)
	{
		item = menu[i];

		if (!item)
			continue;

		str += vertical ? '<tr' : '<td';
		str += ' class="' + prefix + 'MainItem"';

		hasChild = (item.length > 5);
		idSub = hasChild ? cmNewID () : null;

		str += cmActionItem (item, prefix, 1, idSub, orient, nodeProperties) + '>';

		if (item == _cmSplit)
		{
			str += cmSplitItem (prefix, 1, vertical);
			str += vertical? '</tr>' : '</td>';
			continue;
		}

		if (item[0] == _cmNoAction)
		{
			str += cmNoActionItem (item, prefix);
			str += vertical? '</tr>' : '</td>';
			continue;
		}

		classStr = prefix + 'Main' + (hasChild ? 'Folder' : 'Item');

		str += vertical ? '<td' : '<span';
		str += ' class="' + classStr + 'Left">';

		str += (item[0] == null) ? (hasChild ? nodeProperties.mainFolderLeft : nodeProperties.mainItemLeft)
					 : item[0];
		str += vertical ? '</td>' : '</span>';

		str += vertical ? '<td' : '<span';
		str += ' class="' + classStr + 'Text">';
		str += item[1];

		str += vertical ? '</td>' : '</span>';

		str += vertical ? '<td' : '<span';
		str += ' class="' + classStr + 'Right">';

		str += hasChild ? nodeProperties.mainFolderRight : nodeProperties.mainItemRight;

		str += vertical ? '</td>' : '</span>';

		str += vertical ? '</tr>' : '</td>';

		if (hasChild)
			strSub += cmDrawSubMenu (item, prefix, idSub, orientSub, nodeProperties);
	}
	if (!vertical)
		str += '</tr>';
	str += '</table>' + strSub;
	obj.innerHTML = str;
}
function cmItemMouseOver (obj, prefix, isMain, idSub, orient, index)
{
	clearTimeout (_cmTimeOut);

	if (!obj.cmPrefix)
	{
		obj.cmPrefix = prefix;
		obj.cmIsMain = isMain;
	}

	var thisMenu = cmGetThisMenu (obj, prefix);
	if (!thisMenu.cmItems)
		thisMenu.cmItems = new Array ();
	var i;
	for (i = 0; i < thisMenu.cmItems.length; ++i)
	{
		if (thisMenu.cmItems[i] == obj)
			break;
	}
	if (i == thisMenu.cmItems.length)
	{
		thisMenu.cmItems[i] = obj;
	}
	if (_cmCurrentItem)
	{
		if (_cmCurrentItem == thisMenu)
			return;

		var thatPrefix = _cmCurrentItem.cmPrefix;
		var thatMenu = cmGetThisMenu (_cmCurrentItem, thatPrefix);
		if (thatMenu != thisMenu.cmParentMenu)
		{
			if (_cmCurrentItem.cmIsMain)
				_cmCurrentItem.className = thatPrefix + 'MainItem';
			else
				_cmCurrentItem.className = thatPrefix + 'MenuItem';
			if (thatMenu.id != idSub)
				cmHideMenu (thatMenu, thisMenu, thatPrefix);
		}
	}

	_cmCurrentItem = obj;
	cmResetMenu (thisMenu, prefix);

	var item = _cmItemList[index];
	var isDefaultItem = cmIsDefaultItem (item);

	if (isDefaultItem)
	{
		if (isMain)
			obj.className = prefix + 'MainItemHover';
		else
			obj.className = prefix + 'MenuItemHover';
	}

	if (idSub)
	{
		var subMenu = cmGetObject (idSub);
		cmShowSubMenu (obj, prefix, subMenu, orient);
	}

	var descript = '';
	if (item.length > 4)
		descript = (item[4] != null) ? item[4] : (item[2] ? item[2] : descript);
	else if (item.length > 2)
		descript = (item[2] ? item[2] : descript);

	window.defaultStatus = descript;
}
function cmItemMouseOut (obj, delayTime)
{
	if (!delayTime)
		delayTime = _cmNodeProperties.delay;
	_cmTimeOut = window.setTimeout ('cmHideMenuTime ()', delayTime);
	window.defaultStatus = '';
}
function cmItemMouseDown (obj, index)
{
	if (cmIsDefaultItem (_cmItemList[index]))
	{
		if (obj.cmIsMain)
			obj.className = obj.cmPrefix + 'MainItemActive';
		else
			obj.className = obj.cmPrefix + 'MenuItemActive';
	}
}
function cmItemMouseUp (obj, index)
{
	var item = _cmItemList[index];

	var link = null, target = '_self';

	if (item.length > 2)
		link = item[2];
	if (item.length > 3 && item[3])
		target = item[3];

	if (link != null)
	{
		window.open (link, target);
	}

	var prefix = obj.cmPrefix;
	var thisMenu = cmGetThisMenu (obj, prefix);

	var hasChild = (item.length > 5);
	if (!hasChild)
	{
		if (cmIsDefaultItem (item))
		{
			if (obj.cmIsMain)
				obj.className = prefix + 'MainItem';
			else
				obj.className = prefix + 'MenuItem';
		}
		cmHideMenu (thisMenu, null, prefix);
	}
	else
	{
		if (cmIsDefaultItem (item))
		{
			if (obj.cmIsMain)
				obj.className = prefix + 'MainItemHover';
			else
				obj.className = prefix + 'MenuItemHover';
		}
	}
}
function cmMoveSubMenu (obj, subMenu, orient)
{
	var mode = String (orient);
	var p = subMenu.offsetParent;
	if (mode.charAt (0) == 'h')
	{
		if (mode.charAt (1) == 'b')
			subMenu.style.top = (cmGetYAt (obj, p) + cmGetHeight (obj)) + 'px';
		else
			subMenu.style.top = (cmGetYAt (obj, p) - cmGetHeight (subMenu)) + 'px';
		if (mode.charAt (2) == 'r')
			subMenu.style.left = (cmGetXAt (obj, p)) + 'px';
		else
			subMenu.style.left = (cmGetXAt (obj, p) + cmGetWidth (obj) - cmGetWidth (subMenu)) + 'px';
	}
	else
	{
		if (mode.charAt (2) == 'r')
			subMenu.style.left = (cmGetXAt (obj, p) + cmGetWidth (obj)) + 'px';
		else
			subMenu.style.left = (cmGetXAt (obj, p) - cmGetWidth (subMenu)) + 'px';
		if (mode.charAt (1) == 'b')
			subMenu.style.top = (cmGetYAt (obj, p)) + 'px';
		else
			subMenu.style.top = (cmGetYAt (obj, p) + cmGetHeight (obj) - cmGetHeight (subMenu)) + 'px';
	}
}
function cmShowSubMenu (obj, prefix, subMenu, orient)
{
	if (!subMenu.cmParentMenu)
	{
		var thisMenu = cmGetThisMenu (obj, prefix);
		subMenu.cmParentMenu = thisMenu;
		if (!thisMenu.cmSubMenu)
			thisMenu.cmSubMenu = new Array ();
		thisMenu.cmSubMenu[thisMenu.cmSubMenu.length] = subMenu;
	}

	cmMoveSubMenu (obj, subMenu, orient);
	subMenu.style.visibility = 'visible';
	if (document.all)	// it is IE
	{
		if (!subMenu.cmOverlap)
			subMenu.cmOverlap = new Array ();
		cmHideControl ("IFRAME", subMenu);
		cmHideControl ("SELECT", subMenu);
		cmHideControl ("OBJECT", subMenu);
	}
}
function cmResetMenu (thisMenu, prefix)
{
	if (thisMenu.cmItems)
	{
		var i;
		var str;
		var items = thisMenu.cmItems;
		for (i = 0; i < items.length; ++i)
		{
			if (items[i].cmIsMain)
				str = prefix + 'MainItem';
			else
				str = prefix + 'MenuItem';
			if (items[i].className != str)
				items[i].className = str;
		}
	}
}
function cmHideMenuTime ()
{
	if (_cmCurrentItem)
	{
		var prefix = _cmCurrentItem.cmPrefix;
		cmHideMenu (cmGetThisMenu (_cmCurrentItem, prefix), null, prefix);
	}
}
function cmHideMenu (thisMenu, currentMenu, prefix)
{
	var str = prefix + 'SubMenu';
	if (thisMenu.cmSubMenu)
	{
		var i;
		for (i = 0; i < thisMenu.cmSubMenu.length; ++i)
		{
			cmHideSubMenu (thisMenu.cmSubMenu[i], prefix);
		}
	}
	while (thisMenu && thisMenu != currentMenu)
	{
		cmResetMenu (thisMenu, prefix);
		if (thisMenu.className == str)
		{
			thisMenu.style.visibility = 'hidden';
			cmShowControl (thisMenu);
		}
		else
			break;
		thisMenu = cmGetThisMenu (thisMenu.cmParentMenu, prefix);
	}
}
function cmHideSubMenu (thisMenu, prefix)
{
	if (thisMenu.style.visibility == 'hidden')
		return;
	if (thisMenu.cmSubMenu)
	{
		var i;
		for (i = 0; i < thisMenu.cmSubMenu.length; ++i)
		{
			cmHideSubMenu (thisMenu.cmSubMenu[i], prefix);
		}
	}
	cmResetMenu (thisMenu, prefix);
	thisMenu.style.visibility = 'hidden';
	cmShowControl (thisMenu);
}
function cmHideControl (tagName, subMenu)
{
	var x = cmGetX (subMenu);
	var y = cmGetY (subMenu);
	var w = subMenu.offsetWidth;
	var h = subMenu.offsetHeight;

	var i;
	for (i = 0; i < document.all.tags(tagName).length; ++i)
	{
		var obj = document.all.tags(tagName)[i];
		if (!obj || !obj.offsetParent)
			continue;
		var ox = cmGetX (obj);
		var oy = cmGetY (obj);
		var ow = obj.offsetWidth;
		var oh = obj.offsetHeight;

		if (ox > (x + w) || (ox + ow) < x)
			continue;
		if (oy > (y + h) || (oy + oh) < y)
			continue;
		if(obj.style.visibility == "hidden")
			continue;

		subMenu.cmOverlap[subMenu.cmOverlap.length] = obj;
		obj.style.visibility = "hidden";
	}
}
function cmShowControl (subMenu)
{
	if (subMenu.cmOverlap)
	{
		var i;
		for (i = 0; i < subMenu.cmOverlap.length; ++i)
			subMenu.cmOverlap[i].style.visibility = "";
	}
	subMenu.cmOverlap = null;
}
function cmGetThisMenu (obj, prefix)
{
	var str1 = prefix + 'SubMenu';
	var str2 = prefix + 'Menu';
	while (obj)
	{
		if (obj.className == str1 || obj.className == str2)
			return obj;
		obj = obj.parentNode;
	}
	return null;
}
function cmIsDefaultItem (item)
{
	if (item == _cmSplit || item[0] == _cmNoAction)
		return false;
	return true;
}
function cmGetObject (id)
{
	if (document.all)
		return document.all[id];
	return document.getElementById (id);
}
function cmGetWidth (obj)
{
	var width = obj.offsetWidth;
	if (width > 0 || !cmIsTRNode (obj))
		return width;
	if (!obj.firstChild)
		return 0;
	return obj.lastChild.offsetLeft - obj.firstChild.offsetLeft + cmGetWidth (obj.lastChild);
}
function cmGetHeight (obj)
{
	var height = obj.offsetHeight;
	if (height > 0 || !cmIsTRNode (obj))
		return height;
	if (!obj.firstChild)
		return 0;
	return obj.firstChild.offsetHeight;
}
function cmGetX (obj)
{
	var x = 0;

	do
	{
		x += obj.offsetLeft;
		obj = obj.offsetParent;
	}
	while (obj);
	return x;
}

function cmGetXAt (obj, elm)
{
	var x = 0;

	while (obj && obj != elm)
	{
		x += obj.offsetLeft;
		obj = obj.offsetParent;
	}
	if (obj == elm)
		return x;
	return cmGetX (elm) - x;
}

function cmGetY (obj)
{
	var y = 0;
	do
	{
		y += obj.offsetTop;
		obj = obj.offsetParent;
	}
	while (obj);
	return y;
}

function cmIsTRNode (obj)
{
	var tagName = obj.tagName;
	return tagName == "TR" || tagName == "tr" || tagName == "Tr" || tagName == "tR";
}

function cmGetYAt (obj, elm)
{
	var y = 0;

	if (!obj.offsetHeight && cmIsTRNode (obj))
	{
		var firstTR = obj.parentNode.firstChild;
		obj = obj.firstChild;
		y -= firstTR.firstChild.offsetTop;
	}

	while (obj && obj != elm)
	{
		y += obj.offsetTop;
		obj = obj.offsetParent;
	}

	if (obj == elm)
		return y;
	return cmGetY (elm) - y;
}
function cmGetProperties (obj)
{
	if (obj == undefined)
		return 'undefined';
	if (obj == null)
		return 'null';

	var msg = obj + ':\n';
	var i;
	for (i in obj)
		msg += i + ' = ' + obj[i] + '; ';
	return msg;
}