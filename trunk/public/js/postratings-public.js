(function ($) {
  'use strict';

  $(document).on('click', '.postrating-star', function (e) {
    console.info('star-click', e.target, $(e.target).index());
    var clickedStar = $(e.target),
      parent = clickedStar.parent();

    jQuery.post(postratings.adminAjax + '?action=postrating&rating=' + (clickedStar.index() + 1)
      + '&postid=' + parent.data('postid'), function (data) {

      parent.html(data.html);
      parent.data('ratingall', data.ratingAll);
      parent.data('ratingcount', data.ratingCount);
      parent.data('rating', data.ratingResult);
      parent.data('ratingpercent', (data.ratingResult / 5 * 100 ) + '%');

      console.info('postrating', data);

      // callback
      jQuery(document).trigger('postrating_success', data);
    });
  });

  $(document).on('mouseenter', '.postrating-star', function (e) {
    var star = $(e.target),
      allStars = star.parent().find('.postrating-star');

    allStars.removeClass('hover');

    allStars.each(function (index, element) {
      if ($(element).index() <= star.index()) {
        $(element).addClass('hover');
      }
    });
  });

  $(document).on('mouseleave', '.postratings', function (e) {
    $('.postrating-star').removeClass('hover');
  });

})(jQuery);
