/**
 * Window Size Bookmarklet (unminified) 0.2.3 by @josscrowcroft
 * http://www.josscrowcroft.com/2011/code/window-size-bookmarklet/
 * 
 * No warranty - but FWIW, I'm pretty sure it won't break the internet.
 * 
 * No license - backlinks and improvement suggestions very welcome!
 */

// Create new div and text for style attribute, create function for window resize:
(function() {
	
    var d = document,
        w = window,
        j = d.createElement('div'),
        s = 'position:fixed;top:0;left:0;color:#fff;background:#222;padding:5px 1em;font:14px sans-serif;z-index:999999',
        r = function() {
            // Set div's content:
            if ( w.innerWidth === undefined )
                // IE 6-8:
                j.innerText = d.documentElement.clientWidth + 'x' + d.documentElement.clientHeight;
            else if ( d.all )
                // Others:
                j.innerText = w.innerWidth + 'x' + w.innerHeight;
            else
                // Firefox:
                j.textContent = window.innerWidth + 'x' + window.innerHeight;
		};
	
	// Append new div to body element:
	d.body.appendChild( j );
	
	// Add style attribute to div:
	if( typeof j.style.cssText !== 'undefined' )
		j.style.cssText = s;
	else
		j.setAttribute('style', s);
	
	// Set div's content:
	r();
	
	// Bind window resize event:
    if ( w.addEventListener )
	    w.addEventListener('resize', r, false);
    else if ( w.attachEvent )
        w.attachEvent('onresize', r);
    else
        w.onresize = r;
	
})();