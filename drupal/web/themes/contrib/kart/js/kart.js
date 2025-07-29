jQuery(function() {
  // hero slider
  jQuery('.slider').slick({
    slidesToScroll: 1,
    autoplay: true,
    dots: true,
    arrows: false,
  });
});
/* Load jQuery
-----------------*/
jQuery(document).ready(function ($) {
  // placeholder for search form
  $('.header-search input[type="search"]').attr('placeholder', Drupal.t('search here ...'));
  // Mobile menu.
  $('.mobile-menu-icon').click(function () {
    $(this).toggleClass('menu-icon-active');
    $('.primary-menu-wrapper').toggleClass('active-menu');
  });
  $('.close-mobile-menu').click(function () {
    $(this).closest('.primary-menu-wrapper').toggleClass('active-menu');
    $('.mobile-menu-icon').removeClass('menu-icon-active');
  });
  
  // Scroll To Top.
  $(window).scroll(function () {
    if ($(this).scrollTop() > 80) {
      $('.scrolltop').css('display', 'flex');
    } else {
      $('.scrolltop').fadeOut('slow');
    }
  });
  $('.scrolltop').click(function () {
    $('html, body').scrollTop(0);
  });
  // product variation images
  $('.product-variation-image > .field-images').slick({
    dots: true,
  });
  // product images
  $('.product-main-image > .field-images').slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    asNavFor: '.product-main-image-nav > .field-images'
  });
  $('.product-main-image-nav > .field-images').slick({
    slidesToShow: 3,
    slidesToScroll: 1,
    asNavFor: '.product-main-image > .field-images',
    centerMode: true,
    focusOnSelect: true,
  });
// End document ready.
});