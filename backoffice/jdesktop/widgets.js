/**
 * nJDesktop Virtual Desktop widget helper plugin
 * Copyright (C) 2012 Nagy Ervin
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by    
 * the Free Software Foundation, either version 3 of the License, or    
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * -----------------------------------------------------------------------
 * Nagy Ervin
 * nagyervin.bws@gmail.com
 * 
 * License: GPL v.3, see COPYING
 * 
 * If you wish to fork this, please let me know: nagyervin.bws@gmail.com.
 * 
 * Please leave this header intact
 * 
 * -----------------------------------------------------------------------
 * Insert your name below, if you have modified this project. If you wish 
 * that change become part of this project (aka i will endorse it), please 
 * send it to me.
 * 
 * I must remind you, that your changes will be subject to the GPL v.3.
 * 
 */

(function(wnd,d,$){
	nJDSK.widgets = {
		/**
		 * Adds a new widget
		 * @param string wdgId 			widget id
		 * @param string wdgTitle 		widget title
		 * @param string wdgContent		widget content
		 * @param function wdgFunction	widget init function (can implement widget behavior)
		 */
		addItem:function(wdgId,wdgTitle,wdgContent,wdgFunction)
		{
			$('#widgets').append('<div id="'+wdgId+'" class="widget"><h3>'+wdgTitle+'</h3><div class="widget-content"></div></div>');
			if (typeof(wdgFunction) == 'function')
			{
				wdgFunction(wdgId);
			}
			
		}
	} 
})(window,document,jQuery);