const VocElements = {
	selectors: {
		modalContainer: '#vocalizrModal',
		modalTriggers: 'a[href][data-toggle=vmodal]',
		hoverTriggers: '[data-hover-text]',
		ajaxTriggers: 'a[href][data-role=ajax-trigger]',
	},

	events: {
		ajax: 'vocalizr.ajax.succeed',
		modal_triggered: 'vocalizr.modal.triggered',
		modal: 'vocalizr.modal.shown',
	},

	init: function() {
		const $root = $('body');
		$root.on('click', this.selectors.modalTriggers, function (e) {
			e.preventDefault();
			const $trigger = $(this);
			$trigger.trigger(VocElements.events.modal_triggered);
			VocElements.loadAsModal($trigger.attr('href'));
		});

		$root.on('click', this.selectors.ajaxTriggers, function (e) {
			e.preventDefault();
			const $trigger = $(this);
			$.ajax({
				url: $trigger.attr('href'),
				success: function (responseData) {
					$trigger.trigger(VocElements.events.ajax, [responseData, $trigger]);
				}
			});
		});

		$root.on('mouseover mouseout', this.selectors.hoverTriggers, function (e) {
			const $element = $(this);
			if (e.type === 'mouseover') {
				$element.css('min-width', $element.innerWidth() + 'px');
				$element.css('min-height', $element.innerHeight() + 'px');
			} else if ($element.data('init') === undefined) {
				return;
			}
			$element.data('init', true);
			const $text = $element.find('.text');
			const swapText = $text.html();
			$text.html($element.data('hover-text'));
			$element.data('hover-text', swapText);
		});

		$root.on(this.events.ajax, function (e, response, $trigger) {
			if ($trigger.data('after') === 'refresh') {
				document.location.reload();
			}
		});
	},

	loadAsModal: function(url) {
		$.ajax({
			url: url,
			success: function (responseData) {
				VocElements.toModal(responseData);
			}
		});
	},

	toModal: function(data) {
		const $data = $(data);
		const $container = $(VocElements.selectors.modalContainer);
		$container.html($data);
		$container.modal('show');
		$container.trigger(VocElements.events.modal, [$data]);
	},
};

$(function () {
	VocElements.init();

	$('.js-vote-tag').on(VocElements.events.ajax, function (e, responseData, $trigger) {
		const id = $trigger.data('id');

		let toast = '';

		if (responseData.success) {
			const $likeImage = $trigger.find('.tooltip-text img');
			const swapImage = $likeImage.data('swap-image');

			$likeImage.data('swap-image', $likeImage.attr('src'));
			$likeImage.attr('src', swapImage);

			$('.js-vote-tag-count[data-id=' + id + ']').text(responseData.count);
			if (responseData.vote_added) {
				toast = 'Vote added';
			} else {
				toast = 'Vote removed';
			}
		} else {
			toast = 'Vote is not allowed';
		}

		Toastify({text: toast}).showToast();
	});

	$('.lists__number').click(function () {
		$('.lists__number').removeClass('playing');
		$(this).addClass('playing');
	});

})

$(document).ready(function(){
	$('.reviews__slider').slick({
		infinite: true,
	  	slidesToShow: 4,
		slidesToScroll: 1,
		prevArrow: '<p class="slider-arrow left"><i class="left"></i></p>',
		nextArrow: '<p class="slider-arrow"><i class="right"></i></p>',
		responsive: [
		    {
		      breakpoint: 1630,
		      settings: {
		        slidesToShow: 3,
		      }
		    },
		    {
		      breakpoint: 1200,
		      settings: {
		        slidesToShow: 2,
		      }
		    },
		]
	});
});

$(document).ready(function(){
	$('.connections__slider').slick({
		infinite: true,
	  	slidesToShow: 6,
		slidesToScroll: 1,
		prevArrow: '<p class="slider-arrow left"><i class="left"></i></p>',
		nextArrow: '<p class="slider-arrow"><i class="right"></i></p>',
		responsive: [
		    {
		      breakpoint: 1630,
		      settings: {
		        slidesToShow: 4,
		      }
		    },
		    {
		      breakpoint: 1200,
		      settings: {
		        slidesToShow: 3,
		      }
		    },
		]
	});
});

$(document).ready(function(){
	$('.videos__slider').slick({
		infinite: true,
	  	slidesToShow: 2,
		slidesToScroll: 1,
		prevArrow: '<p class="slider-arrow left"><i class="left"></i></p>',
		nextArrow: '<p class="slider-arrow"><i class="right"></i></p>',
	});
});

$(document).ready(function(){
	$('.about-me__slider').slick({
		infinite: true,
	  	slidesToShow: 1,
		slidesToScroll: 1,
		arrows: false,
		dots: true,
		dotsClass: 'about-dots',		
	});
});

$('img.img-svg').each(function(){
  var $img = $(this);
  var imgClass = $img.attr('class');
  var imgURL = $img.attr('src');
  $.get(imgURL, function(data) {
    var $svg = $(data).find('svg');
    if(typeof imgClass !== 'undefined') {
      $svg = $svg.attr('class', imgClass+' replaced-svg');
    }
    $svg = $svg.removeAttr('xmlns:a');
    if(!$svg.attr('viewBox') && $svg.attr('height') && $svg.attr('width')) {
      $svg.attr('viewBox', '0 0 ' + $svg.attr('height') + ' ' + $svg.attr('width'))
    }
    $img.replaceWith($svg);
  }, 'xml');
});

$(document).on('click', 'a.lists__heart-like', function (e) {
	e.preventDefault();

	var $link = $(this);
	var $likesCounter = $(this).parents('.likes-wrapper').find('.likes-count');

	$.ajax({
		url: $(this).attr('href'),
		success: function (d) {
			if (d.success) {
				$likesCounter.text(d.count);
				if (d.changed) {
					$link.toggleClass('liked');
					const swapHref = $link.data('swap-href');
					$link.data('swap-href', $link.attr('href'));
					$link.attr('href', swapHref);
					if ($link.hasClass('liked')) {
						Toastify({text: 'Like added'}).showToast();
					} else {
						Toastify({text: 'Like removed'}).showToast();
					}
				}
			}
		}
	})

});

