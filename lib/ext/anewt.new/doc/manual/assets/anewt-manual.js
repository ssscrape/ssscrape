$(document).ready(function(){
	/* Toggle member details on click */
	$("div.class-member").each(function() {
		var member = $(this);
		var memberDetail = $("div.class-member-detail", member);
		if (memberDetail.length) {
			$("strong a[href]", $(this)).click(function() {
				memberDetail.slideToggle();
				$.scrollTo(member, 'slow');
				return false;
			});
		}
	});
});

$(document).ready(function(){

	/* Hide all member details */
	$("div.class-member-detail").each(function() {
		$(this).hide();
	});

	/* If there's a url fragment in the location, scroll to it and expand the
	 * details if this url fragment points to a class member definition. */
	if (document.location.hash) {
		var whereToScrollTo = $(document.location.hash);
		$.scrollTo(whereToScrollTo);
		if (whereToScrollTo.is('div.class-member')) {
			var memberDetail = $('div.class-member-detail', whereToScrollTo);
			memberDetail.show();
		}
	}
});

