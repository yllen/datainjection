<?php


/*
   ----------------------------------------------------------------------
   GLPI - Gestionnaire Libre de Parc Informatique
   Copyright (C) 2003-2008 by the INDEPNET Development Team.

   http://indepnet.net/   http://glpi-project.org/
   ----------------------------------------------------------------------

   LICENSE

   This file is part of GLPI.

   GLPI is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with GLPI; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
   ------------------------------------------------------------------------
 */

// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	define('GLPI_ROOT', '../../..');
}

$NEEDED_ITEMS=array("datainjection");
include (GLPI_ROOT."/inc/includes.php");

commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],"config","plugins");

echo "<div align='center'>";
echo "<table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$DATAINJECTIONLANG["setup"][1]."</th></tr>";

if (plugin_datainjection_needUpdateOrInstall() != 0)
{
	// If not installed => install or upgrade
	echo "<tr class='tab_bg_1'><td align='center'>";

	if (plugin_datainjection_needUpdateOrInstall() == 1)
		echo "<a href='plugin_datainjection.install.php'>".$DATAINJECTIONLANG["setup"][3]."</a></td></tr>";
	else
		echo "<a href='plugin_datainjection.install.php'>".$DATAINJECTIONLANG["setup"][4]."</a></td></tr>";			
	
}elseif(isset($_SESSION["glpi_plugin_datainjection_installed"]) && $_SESSION["glpi_plugin_datainjection_installed"]>0) {
	echo "<tr class='tab_bg_1'><td align='center'><a href='http://glpi-project.org/wiki/doku.php?id=".substr($_SESSION["glpilanguage"],0,2).":plugins:datainjection_use' target='_blank'>".$DATAINJECTIONLANG["setup"][11]."</a></td></tr>";

	// If installed => configure
	if (haveRight("profile","w")){
		echo "<tr class='tab_bg_1'><td align='center'><a href=\"../front/plugin_datainjection.profile.php\">".$DATAINJECTIONLANG["setup"][9]."</a></td/></tr>";
	}
	if (TableExists("glpi_plugin_datainjection_models") && haveRight("config","w")){
		// If installed (or need upgrade) => uninstall
	
		echo "<tr class='tab_bg_1'><td align='center'><a href=\"../front/plugin_datainjection.uninstall.php\">".$DATAINJECTIONLANG["setup"][5]."</a></td/></tr>";
	}
}

echo "</table></div>";

commonFooter();
?>
