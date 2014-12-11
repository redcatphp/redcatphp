$.escape = function(str) {
  if(str)
	return str.replace(/&/g,'&amp;').replace(/>/g,'&gt;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
}
$.nl2br = function(str) {
  if(str)
	return str.replace("\n",'<br />');
}
// jQuery function that send Post request to json rpc
$.messageService = function(method, params,callback,error_handler) {
  $.post('RPC',{
		"method":method,
		"params":params,
		"id":Math.random()
	  },
	  function(obj) {
		if(obj.error) {
		  error_handler ? error_handler(obj.error) : NotificationObj.showError(obj.error.message + "  " + method);
		} else {
		  callback(obj.result);
		}
	  },
	  "json"
  );     
};

// Object that handles Error Notifications and Display Messages
var NotificationObj = (function() {
  var _init = function(){
	  $('body').on('click', '#errors #hideBtn',function(){ hideError(); });
	  $('body').on('click', '#messages #hideBtn',function(){ hideMessage();  });
  };
  var hideAll = function(){
	  hideError();
	  hideMessage();
  };
  var showError = function(msg){
	  $('<span />').html("<br />" + msg).appendTo('#errors p');
	  $('#errors').fadeIn();
	  $('#next').hide();
  };
  var hideError = function(){
	  $('#errors').fadeOut();
  };
  var showMessage = function(msg){
	  $('#messages').stop().css({opacity:0});
	  $('#messages span').text(msg);
	  $('#messages').animate({opacity:1},300, function(){ $('#messages').animate({opacity:0}, 3000); });
  };
  var hideMessage = function(){
	  $('#messages').css({opacity:0});
  };

  return {
	init: function(){
	  _init();
	},
	hideAll: function(){
	  hideAll();
	},
	showError: function(msg){
		showError(msg);
	},
	hideError: function(){
	  hideError();
	},
	showMessage: function(msg){
		showMessage(msg);
	},
	hideMessage: function(){
	  hideMessage();
	}
  };
})();
// GLOBAL 
  
  var cat_id = parseInt(window.location.href.match(/cat_id=(\d+)/)[1]);
  var limitation,page,count,pages,sort,order;
  limitation = 15;
  page = window.location.href.match(/page=(\d+)/);
  sort = window.location.href.match(/sort=((?:[a-z][a-z0-9_]*))/);
  order = window.location.href.match(/order=((?:[a-z][a-z0-9_]*))/);
  if(order)
    order = order[1];
  else
	order = 'msgid';
  if(sort)
    sort = sort[1];
  else
	sort = 'asc';
  if(page)
	page = parseInt(page[1]);
  else
    page = 1;
  $(function(){
	$('#msg_table_head thead th.'+order).addClass('sort-'+sort);
  });
var appController = (function() {
  var msgs;
    
    $.messageService('getCountMessages',[cat_id],function(total){
		pages = Math.ceil(total/limitation);
		$('#pagination').pagination({
			pages: pages,
			currentPage: page,
			cssStyle: 'compact-theme',
			onPageClick: function(pageNumber, event){
				var nhref = window.location.href;
				if(nhref.match(/page=(\d+)/))
					nhref = nhref.replace('page='+page,'page='+pageNumber);
				else
					nhref += '&page='+pageNumber;
				document.location.href = nhref;
			}
		});
	});
 
  var initEvents = function() {
	// Add click event to message table entries
	$('body').on("click",'#msg_table td',function() {
	  selectMessage($(this).parent('tr').prevAll().length );
	});

	// Add click event to next button in the edit bar
	$('body').on("click", '#next',function(e){
	  e.preventDefault();
	  moveBy(1);
	  $('#msgstr').focus();
	});
	$('#fuzz').click(function(){
		beforeBlur();
	});
	$('#msgstr')
		.on('blur',function(e){
			beforeBlur();
		})
	;

	$(document).keydown( function(e) {
	  
	  var nt = e.target.type,code;
	  if( !(nt == 'input' || nt == 'textarea') ) {
		code = e.which || e.keyCode;
		switch(e.which || e.keyCode) {
		  case 37:
		  case 38:
			moveBy(-1);
			e.preventDefault();
			break;
		  case 39:
		  case 40:
			moveBy(1);
			e.preventDefault();
			break;
		}
	  }
	}).keypress(function(e) {
	  
	}).keyup(function() {
	  
	});
  };

  var moveBy = function(num) {
	var moveTo = getCurrentMessage() + num;
	moveTo < msgs.length && moveTo >= 0 && selectMessage(moveTo);
  };

  var selectMessage = function (index) {
	//beforeBlur();
	$('#msg_table').find('tbody tr.selected').removeClass('selected');
	var arr_index = $('#msg_table').find('tbody tr:eq(' +(index)+ ')').addClass('selected').data('index');
	fillEditBar(arr_index);
	setScroll();
  };

  var getCurrentMessage = function() {
	return $('#msg_table tr.selected').prevAll().length;
  };
  
  var beforeBlur = function(){
	if ( !$('#msg_table tr.selected').length ) return;
	var $row = $('#msg_table tr:eq(' + getCurrentMessage() + ')');    

	var msg = msgs[$row.data('index')];
	var dirty = $('#msgstr').val() != msg.msgstr || 
				$('#comments').val() != msg.comments || 
				$('#fuzz').prop('checked') != msg.fuzzy;
				
	if (dirty) {
	  msg.msgstr = $('#msgstr').val().replace(/\n+$/,'');
	  msg.fuzzy = $('#fuzz').prop('checked');
	  msg.comments = $('#comments').val();
	  $row.trigger('sync');
	  $.messageService('updateMessage', 
					  [msg.id, msg.comments, msg.msgstr, msg.fuzzy], 
					  function() {} 
					  );
	}
  };
  
  var setScroll = function() {
	var ot = $('#msg_table tr.selected').position().top - $('#msg_table').position().top;
	var rh = $('#msg_table tr.selected').height();
	var sh = $('#scroll_container').height();
	var st = $('#scroll_container').scrollTop();
	if(ot < st) {
	  $('#scroll_container').scrollTop(ot);
	}
	if(ot + rh - sh > st) {
	  $('#scroll_container').scrollTop(ot+rh-sh);
	}
  };
  var sync = function () {

	var $row2 = $(this);
	var msg2 = msgs[$row2.data("index")];

	$row2.find('td.msgid div').text(msg2.msgid).end()
		 .find('td.msgstr div').text(msg2.msgstr);
	msg2.msgstr == "" ? $row2.addClass('empty') : $row2.removeClass('empty');
	msg2.fuzzy == 1 ? $row2.addClass('f').find('.fuzzy').text('F') : $row2.removeClass('f').find('.fuzzy').text('');
	msg2.isObsolete && $row2.addClass('d').find('.depr').text('D');
  };
  // Fill the table with all of the messages
  var fillMsgTable = function(catalogue_id) {
	$('#loading_indicator').show();
	if ($('#errors span').text() != "") return;
	$.messageService('getMessages',[catalogue_id,page,order,sort],function(d){
	  msgs = d; // save data to global messages
	  if (!(msgs && msgs.length) ) {
		NotificationObj.showError("No Messages Found");
	  } else {
		var $tbody = $('#msg_table tbody').empty(), html="";
		
		$.each(msgs,function(i,e){
		  html += renderRowAsString(e);
		})
		$tbody.append(html)
		  .find('tr')
		  .each(function(i,e){
			$(e).data('index',i)
			.bind('sync',sync);
		  });
		selectMessage(0);
	  }
	  $('#loading_indicator').hide();
	});
  };
  var renderRowAsString = function(obj) {
	var tr_class = "" 
				  + (!obj.msgstr.length ? 'empty ' : '')
				  + (obj.fuzzy == 1 ? 'f ' : '')
				  + (obj.isObsolete ? 'd ' : ''); 
	return  ''
			+  '<tr class="' + tr_class + '"><td class="msgid"><div><span>'
			+ $.escape(obj.msgid) + '</span></div></td>'
			+ '<td class="msgstr"><div>'
			+ (obj.msgstr?$.escape(obj.msgstr):'') + '</div></td>'
			+ '<td class="fuzzy">'
			+ (obj.fuzzy == 1 ? 'F' : '')
			+ '</td>'
			+ '<td class="depr">'
			+ (obj.isObsolete ? 'D' : '')
			+ '</td>';
  }

  // Fill the Edit Bar with the selected message
  var fillEditBar = function(index){
	var msg = msgs[index];
	$('#ref_data').html( $.nl2br($.escape(msg.reference)) || '-' );
	$('#com_data').html( $.nl2br($.escape(msg.extractedComments)) || '-' );
	$('#update_data').html( msg.updatedAt || '-' );
	$('#comments').val(msg.comments);
	$('#msgid').html( $.nl2br($.escape(msg.msgid)) || "-" );
	$('#msgstr').val(msg.msgstr?msg.msgstr:'');
	( msg.fuzzy == 1 ) ? $('#fuzz').prop('checked',true) : $('#fuzz').prop('checked',false);
	$('#edit_id').attr( 'value', msg.id );
  };
  
  var _init = function() {
	getCatalogues(cat_id);
	 // Sort Table by the different headers
	sortController.init();
	  
	initPanels();
	fillMsgTable(cat_id);
	NotificationObj.init();
	initEvents();
  }
  return {
	init: function() {
	  _init();
	}
  };
})();
$(appController.init);      

var sortController = {
  init: function() {
	$('#msg_table_head thead th').click(function() {
	  var column_index = $(this).closest('thead').find('th').index(this);
	  var direction = !!$(this).hasClass('sort-desc') || !$(this).hasClass('sort-asc');
		if(pages==1){			
		  $(this).siblings().andSelf().removeClass('sort-asc').removeClass('sort-desc');
		  $(this).addClass(direction ? 'sort-asc' : 'sort-desc');
		  $('#msg_table').tsort(column_index,direction);
		}
		else{
			var nhref = window.location.href;
			if(nhref.match(/order=((?:[a-z][a-z0-9_]*))/))
				nhref = nhref.replace('order='+order,'order='+$(this).attr('class').split(' ')[0]);
			else
				nhref += '&order='+order;
			if(nhref.match(/sort=((?:[a-z][a-z0-9_]*))/))
				nhref = nhref.replace('sort='+sort,'sort='+(direction?'asc':'desc'));
			else
				nhref += '&sort='+(direction?'asc':'desc');
			document.location.href = nhref;
		}
	});
  }
};

// Get the stored catalogues
var getCatalogues = function( catId ){
  $.messageService('getCatalogues', [], function(data){
	if (data.length == 0){
	  NotificationObj.showError("No PO Catalalogues Found.");
	  return;
	}
	for (var i = 0; i<data.length; ++i){
		var opt = $("<option />");
		opt.attr('id',data[i]['id']).text(data[i]['name']);
		if(data[i]['id']==catId){
			opt.attr('selected','selected');
		}
		opt.appendTo($('#catalogue_list'));
		if (data[i]['id'] == catId) {
			$('#cat_name').text(data[i]['name']);
		}
	}
  });
};

var initPanels = function(){
  // Jump to other Catalogues through drop down menu
  $('#catalogue_list').change( function(){
	window.location.href = "edit?cat_id=" + ( $('#catalogue_list option:selected').attr('id') );
  });
  
  // Add toggle effect
  $('#edit_bar .block h3 a.expand').click(function(){
	$(this).parents('.block').find('.data').toggle('fast');
  }).parents('.block').find('.data').toggle();
  
  // Add rollover effect to Side Bar
  var left = $('div.block').offset().left;
  var top = $('div.block').offset().top + $('div.block').height();
  $('body').on("mouseover", '#ref_head',function(){
	$('#ref_data').css({'left':left, 'top':top}).fadeIn('fast');
  }).on("mouseout", '#ref_head',function(){
	$('#ref_data').fadeOut();
  });
  $('body').on("mouseover", '#update_head',function(){
	$('#update_data').css({'left':left, 'top':top}).fadeIn('fast');
  }).on("mouseout",'#update_head', function(){
	$('#update_data').fadeOut();
  });
  $('body').on("mouseover", '#src_com_head',function(){
	$('#com_data').css({'left':left, 'top':top}).fadeIn('fast');
  }).on("mouseout", '#src_com_head',function(){
	$('#com_data').fadeOut();
  });
  
  $(this).resize(function() {
	var w = $(this).width();
	var h = $(this).height();
	var scroll_height = h - $('#scroll_container').offset().top - ( $('#foot').outerHeight());
	var l = $('#message_container').offset().left;
	$('#message_container').css({width:w-l});
	$('#msg_table,#msg_table_head').css({width:w-l-20});
	$('#scroll_container').css('height',scroll_height);
  }).resize();
  
	$('#clean_obsolete').click(function(e){
		$('body').css('opacity',0.2);
		e.preventDefault();
		$.post('RPC',{method:'cleanObsolete'},function(){
			window.location.href = window.location.href;
		});
		return false;
	});
  
};