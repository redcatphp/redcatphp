/**
 * Known issues:
 * 1) Does not work with colspans
 * 2) Issue when th's within table body.	Unpredictable behavior.
 */
(function($) {

	var comparators = {
		STRING: function(a,b) {
			// Separates and sorts by case
			return a > b ? 1 : -1;
		},
		NUMERIC: function(a,b) {
			return parseFloat(a) > parseFloat(b) ? 1 : -1;
		},
		STRING_INSENSITIVE: function(a, b) {
			return a.toLowerCase() > b.toLowerCase() ? 1 : -1 ;
		}
	}

	$.fn.tsort = function(column,direction,compare) {

		var d = direction == -1 ? -1 : !!direction ? 1 : -1;

		var comp = $.isFunction(compare) ? compare :
									 comparators[compare] ? comparators[compare] : comparators.STRING_INSENSITIVE;
									

		return this.each(function() {
			var $table = $(this);
			var arrayRows = $(this).find('tbody tr').get();
			
			/* this is a preformance hack.  We precalculate the text we are going to compare, and 
			 	 pass that to the sort function.  This is much faster, than including this code in the sort
			*/	
			var re = /<[^>]*>/g;
			for(var i in arrayRows) {
				arrayRows[i] = {"dom_element":arrayRows[i],
												"compare_text":$('td',arrayRows[i])[column].innerHTML.replace(re,'')
												};
			}
			
			arrayRows.sort(function(rowA,rowB) {
				return d*comp(rowA.compare_text,rowB.compare_text );
			});
			
			// rebuild the array of dom elements
			for(var i in arrayRows) {
				arrayRows[i] = arrayRows[i].dom_element
			}
			$table.find('tbody').append( $(arrayRows) );  // append is faster than prepend
			
		});
	}
})(jQuery);