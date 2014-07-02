(function($){
	
	$.fn.tmMobileMenu = function(options) {
	
		var settings = $.extend( {
			label : 'Menu',
			menuBg: '#000000',
			menuColor: '#ffffff',
			menuIndicatorBg:'#6F94AB',
			subMenuBg: '#f5f5f5',
			subMenuItemHover: '#e0e0e0',
			subMenuItemColor: '#000'
		}, options);

		return this.each(function(options) {
			
			var ele = this,
				$window   = $(window),
			    resize_ok = true,
			    timer;
			
			trigger = $('<a></a>',{
				text: settings.label,
				href:'#',
				id:'mobile-menu-trigger'
			}).css({ 
				'background-color': settings.menuBg,
				'color' : settings.menuColor
			});

			trigger.insertBefore(ele).click(toogleMenu);
			
			$(ele).clone(true)
				.removeClass()
				.attr('id', 'new-main-menu')
				.insertAfter($('#mobile-menu-trigger'))
				.hide();

			$('#new-main-menu').css({
				'background': settings.subMenuBg,
				'color' : settings.subMenuItemColor,
			});

			$('#new-main-menu li').css({
				'width': '100%'
			});

			$('#new-main-menu a').css({
				'color': settings.subMenuItemColor,
			})

			$('#new-main-menu a').hover(
				function(){
					$(this).css({
						'background': settings.subMenuItemHover,
					})	
				},
				function (){
					$(this).css({
						'background': settings.subMenuBg,
					})	
				}
			)

			$('#new-main-menu ul, #new-main-menu li, #new-main-menu a, #new-main-menu a span').removeClass().show();



			function toogleMenu(ele){
				ele.preventDefault();
				if($(this).hasClass('down')){
					$(this).removeClass('down')
				}else{
					$(this).addClass('down')
				}
				$('#new-main-menu').slideToggle();				
			}
			/*Just in case the resize the window w/ the menu open.*/
			$window.resize(function() {
		        if($window.width() >= 450) {
		            $('#new-main-menu').hide()
		            $('#mobile-menu-trigger').removeClass('down');
		        }
			});
		});
	
	};
})(jQuery);