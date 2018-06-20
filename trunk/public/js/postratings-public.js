(function ($) {
  'use strict';

  $(document).on('click', '.postrating-star', function (e) {
    var clickedStar = $(e.target),
      parent = clickedStar.parent(),
      allStars = parent.find('.postrating-star');


    if (parent.hasClass('no-action')) {
      return;
    }

    // disabled ajax-submit
    if (parent.hasClass('no-submit')) {
      parent.data('newvalue', clickedStar.index());
      allStars.each(function (index, element) {
        $(element)[0].className = 'postrating-star';
        if ($(element).index() <= clickedStar.index()) {
          $(element).addClass('postrating-star-full');
        }
      });


      // wrote to input
      var form = parent.closest('form'),
        ratingInput = form.find('.postrating-values'),
        values = {};

      if (!form.length || !ratingInput.length) {
        return;
      }

      form.find('.postratings').each(function (index, element) {
        if ($(element).data('newvalue')) {
          values[$(element).data('key')] = $(element).data('newvalue');
        }
      });

      ratingInput.val(JSON.stringify(values));

      return;
    }


    // is single field, send to ajax an save
    jQuery.post(postratings.adminAjax + '?action=postrating&rating=' + (clickedStar.index() + 1)
      + '&postid=' + parent.data('postid')
      + '&key=' + parent.data('key'), function (data) {

      parent.html(data.html);
      parent.data('ratingall', data.ratingAll);
      parent.data('ratingcount', data.ratingCount);
      parent.data('rating', data.ratingResult);
      parent.data('ratingpercent', (data.ratingResult / 5 * 100) + '%');

      // callback
      jQuery(document).trigger('postrating_success', data);
    });
  });

  $(document).on('mouseenter', '.postrating-star', function (e) {
    var star = $(e.target),
      allStars = star.parent().find('.postrating-star');

    if (star.parent().hasClass('no-action')) {
      return;
    }

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
