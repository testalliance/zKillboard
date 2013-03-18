$(document).ready(function() {
	checkAds();
	
    if ($("[rel=tooltip]").length) {
		$("[rel=tooltip]").tooltip({
			placement: "bottom",
			animation: false
		});
    }
	 
    $('.dropdown-toggle').dropdown();
    
    // Javascript to enable link to tab
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-tabs a[href=#'+url.split('#')[1]+']').tab('show') ;
    } 

    // Change hash for page-reload
    $('.nav-tabs a').on('shown', function (e) {
        window.location.hash = e.target.hash;
    })

    // Javascript to enable link to tab
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-pills a[href=#'+url.split('#')[1]+']').tab('show') ;
    } 

    // Change hash for page-reload
    $('.nav-pills a').on('shown', function (e) {
        window.location.hash = e.target.hash;
    })

    jQuery(document).ready(function() {
      jQuery("abbr.timeago").timeago();
    });
    $(".alert").alert()

	// hide #back-top first
	$("#back-top").hide();
	
	// fade in #back-top
	$(function () {
		$(window).scroll(function () {
			if ($(this).scrollTop() > 500) {
				$('#back-top').fadeIn();
			} else {
				$('#back-top').fadeOut();
			}
		});

		// scroll body to 0px on click
		$('#back-top a').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 100);
			return false;
		});
	});
	
	// Autocomplete search
    $("#searchbox").typeahead({
		source: function(typeahead, query) {
			//clear the old rate limiter
			clearTimeout($('#typeahead').data('limiter'));

			var ajax_request = function()
			{
				$.ajax({
					url: "/autocomplete/",
					type: "GET",
					data: "q=" + query,
					dataType: "JSON",
					async: true,
					success: function(data) {
						typeahead.process(data);
					}
				});
			}
            //start the new timer
            $('#typeahead').data('limiter', setTimeout(ajax_request, 500));
		},
		onselect: function(obj) {
			$('form[name="search"]').submit();
		}
   });
});

function checkAds() {
	if(document.getElementsByTagName("iframe").item(0) == null)
	{
		$message = "<div>Advertising seems to be blocked by your browser.<br/>This isn't very nice as the ads pay for the servers!<br/>May all your ships quickly become wrecks...</div>";
		$("#adsensetop").html($message);
		$("#adsensebottom").html($message);
	};
}

function updateKillsLastHour() {
	return;
	$("#lasthour").load("/killslasthour/");
	setTimeout(updateKillsLastHour, 60000);
}

$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });