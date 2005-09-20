<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

class menu
{

	var $menu = '<script language="JavaScript" type="text/javascript">';
	var $menuname = 'Menu';
	var $options = '<script language="JavaScript" type="text/javascript">var AdminM =[';

	var $mainopen = false;
	function main($image='null', $name, $link ,$t2='null',$t3=false)
	{
		if ($this->mainopen)
		{
			$this->options .= "]\n";
		}
		$this->options .= "[null,'$name','$link',null,'Home Page',\n";

	}
	
	function sub($image='null', $name, $link ,$t2='null',$t3=false)
	{

		$this->options .= "[null,'$name','$link',null,'Home Page'],\n";

	}
	
	function menuarray($menu, $previous=false) {

		foreach  ($menu as $name => $value)
		{
			if ($previous == $name)
			{
				$this->main($image='null', $name, $value ,$t2='null',$t3='null');
			} else {
			
				if (is_array($value))
				{
					$this->menuarray($value, $name);
				} else {
					$this->sub($image='null', $name, $value ,$t2='null',$t3='null');
				}
			}
		}
		$this->options .= "],\n";
	}
	
	function menuclose() {
			$this->options = substr($this->options, 0, -2). ";\ncmDraw ('AdminMenu', AdminM, 'hbr', cmThemeOffice, 'ThemeOffice');
</script>\n"; 
	}
}

?>