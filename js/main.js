var keepCountdown = true;
jQuery(document).ready(function($){	

	if(!location.hash){
		location.hash = $('#feed_left_nav a:visible:first').attr('href').replace( /^#/, '' );
	}
	
	String.prototype.format = function() {
		var args = arguments;
		return this.replace(/{(\d+)}/g, function(match, number) { 
			return typeof args[number] != 'undefined' ? args[number] : '{' + number + '}';
		});
	};

	var loadedBefore = [], timer;
	
	/* PAGE CHANGE
	 * 
	 * Change the content depending on #target
	 ************************************************************/
	
		  
	// Bind an event to window.onhashchange that, when the hash changes, gets the
	// hash and adds the class "selected" to any matching nav link.
	$(window).hashchange( function(){
		var fallbackHash = $('#feed_left_nav a:visible:first').attr('href');
		var hash = ( location.hash.replace( /^#/, '' ) || fallbackHash.replace( /^#/, '' ) );
		
		//if it exist within the plugin it mean its a valid hash
		if( $('#feed_wrapper a[href=#'+hash+']').length > 0)
			changePage(hash);
	})
	  
	// Since the event is only triggered when the hash changes, we need to trigger
	// the event now, to handle the hash the page may have loaded with.
	$(window).hashchange();
	
	function changePage(hash){
		$('#feed_wrapper a').removeClass('active');
		$('#feed_wrapper a[href=#'+hash+']').addClass('active');
		$('#feed_content > div').hide();
		
		if(!loadedBefore[hash]){
			loadedBefore[hash] = true;

			var data = {
				action: 'feed_command',
				feed_cmd: 'get_tab',
				tab: hash
			}
			
			$.post(ajaxurl, data, function(response){
				$('#feed_content').append('<div id="{0}">{1}</div>'.format('feed_'+hash, response));
				
				//necessary if spaming all links
				if(!fallbackHash){
					var fallbackHash = $('#feed_left_nav a:visible:first').attr('href');
				}
				var activeHash = ( location.hash.replace( /^#/, '' ) || fallbackHash.replace( /^#/, '' ) );
				$('#feed_content > div').hide();
				$('#feed_'+activeHash).show();
				
				if(hash == 'android') buildAndroidPage(); 
				if(hash == 'ios') buildIOSPage();
				if(hash == 'your_apps') checkForPendingBuild();
				
				makeDynamickForms();
			});
		}else{
			$('#feed_'+hash).show();
		}
		return false;
	}
	
	

	/* DYNAMIC FORMS
	 * 
	 * We make all forms to do ajax/ifram if contain img
	 * And display progress
	 ************************************************************/
	function makeDynamickForms(){
		var callback = function(){};
		$('#feed_content form').not('[target=_blank]').ajaxForm({
		    beforeSubmit:function(data,form,options){
				$.each(data, function(i, v){
					if(v.name == 'cansel_apk' || v.name == 'generate_apk'){
						$('#android_apk pre').remove();
						togglePendingApkBuild();
					}
					if(v.name == 'publish_apk'){
						pushLive();
					}
					if(v.name == 'live_login'){
						callback = update_live_input;
					}
				});
			},
	        success: function(data){
				//console.log(data);
	        	$('.display_live_feedback_response').html(data);
	        	setTimeout(function(){
	        		$('.display_live_feedback_response').html('');
	        	},5000);
				try{
					var json = $.parseJSON( data );
					if( json ){
						if(json.errors){
							$('.feed_ApkTimer').after('<pre style="color: red;">'+json.errors.join('')+'</pre>');
							$('#android_apk').removeClass('has_pending_build');
							keepCountdown = false;
							return false;
						}
					} else {
						//console.log('no json');
					}
				}
				catch (e) {
					//console.log(e);
				}
				callback(data);
				callback = function(){};
			}
		});
	}


	
	function pushLive(){
		$('#feed_your_apps .feed_beta').addClass('feed_live').removeClass('feed_beta').html('Live');
		$('#feed_your_apps input[name=publish_apk]').remove();
		$('input[name=A_next_version_name]').val('');
		$('input[name=A_next_version_code]').val('');
	}
	
	function togglePendingApkBuild(){
		$('#android_apk').toggleClass('has_pending_build');
		keepCountdown = false;
		$('#feed_your_apps time').html('9');
		checkForPendingBuild();
	}
	

	
	function checkForPendingBuild(){
		if($('#android_apk').hasClass('has_pending_build')){
			keepCountdown = true;
			
			$('#feed_your_apps time').countDown({
				callBack: function(){
					if($('#android_apk').hasClass('has_pending_build')){
						
						var data = {
							action: 'feed_command',
							feed_cmd: 'get_tab',
							tab: 'your_apps'
						}
							
						$.post(ajaxurl, data, function(response){
							$('#feed_your_apps').remove();
							$('#feed_content').append('<div id="{0}">{1}</div>'.format('feed_your_apps', response));
							
							var activeHash = location.hash.replace( /^#/, '');
							if(activeHash != 'your_apps') $('#feed_your_apps').hide();
							checkForPendingBuild();
							makeDynamickForms();
						});
					}
				}
			});
			
		}
	}
	
	/*	DISPLAY IMAGE
	 * 
	 * Here we read the image from files selected on new 
	 * inputs field and display them in the UI
	 ************************************************************/
	function displayImage(evt, id, gui) {
		var file = evt.target.files[0]; // FileList object
		
			if (!file.type.match("image/png")) {
				// TODO convert image to png with canvas
				alert('Only png please');

				//For IE
				$("input[name="+id+"]").replaceWith($("input[name="+id+"]").clone(true));
				//For other browsers
				$("input[name="+id+"]").val(""); 
				
				return false;
			}

			var reader = new FileReader();

			// Closure to capture the file information.
			reader.onload = (function(){
				return function(e) {
					// Render thumbnail.
					if(gui == 'ios'){
						$('img[data-name='+id+']').attr('src', e.target.result);
					} else {
						$('img[data-name='+id+']').attr('src', e.target.result);
						if(id == 'mybtn_normal'){
							$('div[data-name='+id+']').css('-webkit-border-image', 'url('+e.target.result+') 0 7 0 7');
							$('div[data-name='+id+']').css('-moz-border-image', 'url('+e.target.result+') 0 7 0 7');
							$('div[data-name='+id+']').css('border-image', 'url('+e.target.result+') 0 7 0 7');
						}
					}
				};
			})(file);

			
			// Read in the image file as a data URL.
			reader.readAsDataURL(file);
	}
	
	function buildIOSPage(){
		
		/* NAVIGATE BETWEEN IOS SCREENS
		 ************************************************************/
		var activeScreen = $('#feed_ios .feed_screen div:visible:first');
		$('#feed_ios .feed_prev').click(function(){
			
			if(activeScreen.prev().prev().length == 0)
				$(this).attr('disabled', true);
			$('#feed_ios .feed_next').attr('disabled', false);
			activeScreen = activeScreen.prev();
			$('#feed_ios .'+activeScreen.next().attr('class')).hide();
			$('#feed_ios .'+activeScreen.attr('class')).show();
			if(activeScreen.is(':first-child')){
				$('#feed_ios .feed_dock').hide();
			}
			return false;
		});

		$('#feed_ios .feed_next').click(function(){
			if(activeScreen.next().next().next().length == 0) 
				$(this).attr('disabled', true);
			$('#feed_ios .feed_prev').attr('disabled', false);
			activeScreen = activeScreen.next();
			$('#feed_ios .'+activeScreen.prev().attr('class')).hide();
			$('#feed_ios .'+activeScreen.attr('class')).show();
			$('#feed_ios .feed_dock').show();
			return false;
		});

		
		
		/* HIGHLIGHT EDITING
		 * 
		 * the following will change the text inte the iphone
		 * screen and also change the image in modern browser
		 * 
		 * when its beeing focus or mouse enters, the selected
		 * object in the phone will blink twice
		 ************************************************************/
		$("#feed_ios .feed_tools .button").bind('mouseenter', function() {
			$('#feed_ios :[data-name]').stop().stop().css("opacity", "1");
			var name = $(this).find('input, textarea').attr('name');
			$('#feed_ios :[data-name='+name+']').fadeOut().fadeIn().fadeOut().fadeIn();
		});
		
		// text
		$("#feed_ios input[type='text']").bind('keydown keypress keyup change', function() {
			var value = (this.value) ? this.value : $(this).attr('placeholder');
			var name = $(this).attr('name');
			$('#feed_ios :[data-name='+name+']').html(value);
		}).trigger('keypress').bind('focus', function() {
			$('#feed_ios :[data-name]').stop().stop().css("opacity", "1");
			var name = $(this).attr('name');
			$('#feed_ios :[data-name='+name+']').fadeOut().fadeIn().fadeOut().fadeIn();
		});;

		//textarea
		$("#feed_ios textarea").bind('keydown keypress keyup change', function() {
			var value = (this.value) ? this.value : $(this).attr('placeholder');
			var name = $(this).attr('name');
			value = value.replace(/\n/g, "<br />");
			$('#feed_ios :[data-name='+name+']').html(value);
		}).trigger('keypress');
		
		//file
		$("#feed_ios input[type=file]").bind('keydown keypress keyup change', function() {
			var value = $(this).val();
			var name = $(this).attr('name');
			value = value.replace(/\n/g, "<br />");
			$('#feed_ios :[data-name='+name+']').html(value);
		}).trigger('keypress').change(function(e){
			displayImage(e, this.name, 'ios');
		});
		
	}
	
	
	
	function buildAndroidPage(){

		/* NAVIGATE BETWEEN ANDROID SCREENS
		 ************************************************************/
		var activeScreen = $('#feed_android .feed_screen div:visible:first');
		$('#feed_android .feed_prev').click(function(){
			
			if(activeScreen.prev().prev().length == 0)
				$(this).attr('disabled', true);
			$('#feed_android .feed_next').attr('disabled', false);
			activeScreen = activeScreen.prev();
			$('#feed_android .'+activeScreen.next().attr('class')).hide();
			$('#feed_android .'+activeScreen.attr('class')).show();
			if(activeScreen.is(':first-child')){
				$('#feed_android .feed_dock').hide();
			}
			return false;
		});

		$('#feed_android .feed_next').click(function(){
			if(activeScreen.next().next().next().length == 0) 
				$(this).attr('disabled', true);
			$('#feed_android .feed_prev').attr('disabled', false);
			activeScreen = activeScreen.next();
			$('.'+activeScreen.prev().attr('class')).hide();
			$('.'+activeScreen.attr('class')).show();
			$('#feed_android .feed_dock').show();
			return false;
		});

		
		
		/* HIGHLIGHT EDITING
		 * 
		 * the following will change the text inte the iphone
		 * screen and also change the image in modern browser
		 * 
		 * when its beeing focus or mouse enters, the selected
		 * object in the phone will blink twice
		 ************************************************************/
		$("#feed_android .feed_tools .button").mouseenter(function() {
			$(':[data-name]').stop().stop().css("opacity", "1");
			var name = $(this).find('input, textarea').attr('name');
			$('#feed_android :[data-name='+name+']').fadeOut().fadeIn().fadeOut().fadeIn();
		});
		
		$("#feed_android .feed_tools .button").hover(function(){
			var name = $(this).find('input, textarea').attr('name');
			if($('#feed_android table :[data-name='+name+']').length > 0){
				$('#feed_android table').show();
				clearTimeout(timer);
			}
		}, function(){
			timer = setTimeout(function(){
				$('#feed_android table').hide();
			}, 1000);
		});
		
		// text
		$("#feed_android input[type='text']").not('.colorpicker_select').bind('keydown keypress keyup change', function() {
			var value = $(this).val();
			var name = $(this).attr('name');
			if(value == '')
			value = $(this).attr('placeholder');
			
			if(name == 'new_items')
			value = value.replace(/NUMBERS_OF/, '7');
			
			$('#feed_android :[data-name='+name+']').html(value);
		}).trigger('keypress').focus(function() {
			$('#feed_android :[data-name]').stop().stop().css("opacity", "1");
			var name = $(this).attr('name');
			$('#feed_android :[data-name='+name+']').fadeOut().fadeIn().fadeOut().fadeIn();

			
			if($('#feed_android table :[data-name='+name+']').length > 0)
				$('#feed_android table').show();
		}).blur(function(){
			$('#feed_android table').hide();
		});

		//textarea
		$("#feed_android textarea").bind('keydown keypress keyup change', function() {
			var value = $(this).val();
			var name = $(this).attr('name');
			
			if(value == '')
			value = $(this).attr('placeholder'); 
			
			value = value.replace(/\n/g, "<br />");
			$('#feed_android :[data-name='+name+']').html(value);
		}).trigger('keypress');
		
		//file
		$("#feed_android input[type=file]").change(function(e){
			displayImage(e, this.name, 'android');
		});

		
		$('#feed_android .colorpicker_select').each(function(){
			if(this.value == '') this.value = $(this).attr('placeholder');
			$(this).css("backgroundColor", "#"+this.value);
			
			var cssAttributes = {'border' : 'borderColor', 'text' : 'color'}
			var cssAttr = (cssAttributes[this.name]) ? cssAttributes[this.name] : 'backgroundColor';
			
			$('#feed_android :[data-'+this.name+']').css(cssAttr, "#"+this.value);
			
		}).ColorPicker({
	
			onChange: function (hsb, hex, rgb, el) {
				
				var cssAttributes = {'border' : 'borderColor', 'text' : 'color'}
				var cssAttr = (cssAttributes[el.name]) ? cssAttributes[el.name] : 'backgroundColor';
				
				$('#feed_android :[data-'+el.name+']').css(cssAttr, "#"+hex);
				
				$(el).val(hex);
				$(el).css("backgroundColor", "#"+hex);
			}
	
		});
		/*
		var f = $.farbtastic('#feed_picker', function(a){
			//console.log('change ' + a);
		});
		var selected;
		$('#feed_android .colorpicker_select')
			.each(function () { f.linkTo(this); })
			.focus(function() {
				selected = this;
				f.linkTo(function(hex){
					//console.log(this);
					$(selected).css('backgroundColor', hex).val(hex);
					$(selected).css('color', (this.hsl[2] > 0.5) ? '#000' : '#fff');
					var cssAttr = 'backgroundColor';
					cssAttr = (selected.name == 'text') ? 'color' : cssAttr; 
					cssAttr = (selected.name == 'border') ? 'borderColor' : cssAttr; 
					
					$('#feed_android :[data-'+selected.name+']').css(cssAttr, hex);
				});
			});
		*/
		
		w = $('#feed_android input[type=file]:first').outerWidth();
		$('#feed_android input[type=text]').width(w);
		$('#feed_android textarea').width(w);
		
		var button_image_src = $('div[data-name=mybtn_normal]').attr('data-src');
		$('div[data-name=mybtn_normal]').css('-webkit-border-image', 'url('+button_image_src+') 0 7 0 7');
		$('div[data-name=mybtn_normal]').css('-moz-border-image', 'url('+button_image_src+') 0 7 0 7');
		$('div[data-name=mybtn_normal]').css('border-image', 'url('+button_image_src+') 0 7 0 7');
	}
	
});


(function($) {
	jQuery.fn.countDown = function(settings,to) {
		settings = jQuery.extend({
			startFontSize: '36px',
			endFontSize: '12px',
			duration: 1000,
			startNumber: 9,
			endNumber: 0,
			callBack: function() { }
		}, settings);
		return this.each(function() {
			
			if(!to && to != settings.endNumber) { to = settings.startNumber; }

			$(this).animate({
				'color': '#000'
			},settings.duration,'',function() {
				if(to > settings.endNumber) {
					if(keepCountdown)
					$(this).text(to - 1).countDown(settings,to - 1);
				}
				else
				{
					settings.callBack(this);
				}
			});
					
		});
	};

})(jQuery);

function update_live_input(d){
	var $ = jQuery;
	//console.log($.parseJSON(d));
	try {
		var jsonData = $.parseJSON(d);
		if( jsonData.credsRatio > 0 ){
			
			$("#feed_live_settings .is_logged_in").show();
			$("#feed_live_settings .is_not_logged_in").hide();
			$("#feed_live_settings input[name=feed_cmd]").attr('value', 'save_live_settings');
			$("#feed_live_settings input[name=live_login]").remove();
			
			$.each(jsonData, function(i, v){
				$("#feed_live_settings input[name="+i+"][type=text]").val(v);
				if(i == 'disableAds'){
					$("#feed_live_settings input[name="+i+"][value="+v+"]").click();
				}
			});
		
		} else {
			//console.log('error parsing credsratio');
			alert(jsonData);
		}
	} catch(e) {
		//console.log('error parsing json');
		alert(d);
	}
	
}