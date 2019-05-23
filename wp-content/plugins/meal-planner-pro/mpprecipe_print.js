/*
Plugin Name: MealPlannerPro Recipe Plugin
Plugin URI: http://www.mealplannerpro.com/recipe_plugin
Plugin GitHub: https://github.com/Ziplist/recipe_plugin
Description: A plugin that adds all the necessary microdata to your recipes, so they will show up in Google's Recipe Search
Version: 7.6.8
Author: MealPlannerPro.com
Author URI: http://www.mealplannerpro.com/
License: Direct license by original author - http://www.808.dk/?code-javascript-print

Copyright 2011, 2012, 2013, 2014 MealPlannerPro, Inc.
This code is derived from an example provided by Ulrik D. Hansen and licensed under the Creative Commons Attribution License.
You can see the original post at http://www.808.dk/?code-javascript-print
*/

/*
    This section refers to any extensions made to the original work.

    This file is part of MealPlannerPro Recipe Plugin.

    MealPlannerPro Recipe Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MealPlannerPro Recipe Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MealPlannerPro Recipe Plugin. If not, see <http://www.gnu.org/licenses/>.
*/

var win=null;
function zlrPrint(id, plugindir, design, noimage, headingcolor )
{
    design  = design || 'mpprecipe-std'

	var content = document.getElementById(id).innerHTML;
	win = window.open();
	self.focus();
	win.document.open();
	win.document.write('<html><head>');

    var pre  = '<link charset=\'utf-8\' href=\'' + plugindir
    var post = '\' rel=\'stylesheet\' type=\'text/css\' />'

    if( noimage )
	    var css = pre + 'mpprecipe-print-bare.css' + post
    else
    {
	    var css = pre + design + '.css'        + post
        css    += pre + 'mpprecipe-common.css' + post
	    css    += pre + 'mpprecipe-print.css'  + post

        if( headingcolor )
            win.document.write('<style>.mpprecipe .h-4, #mpprecipe-title { color:' + headingcolor + ' !important; }</style>');
    }


	win.document.write('</head>')
    win.document.write( css )
    win.document.write('<body onload="print();">');
	win.document.write('<div id=\'mpprecipe-print-container\' >');
	win.document.write( content );
	win.document.write('</div>');
	win.document.write('</body></html>');

	win.document.close();
}
