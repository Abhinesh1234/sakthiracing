"use strict";
$(function() {
    // Menu js
    $('.post-gallery-slider').slick({
        dots: false,
        arrows: true,
        infinite: true,
        autoplay: true,
        speed: 800,
        prevArrow: '<div class="prev"><i class="fas fa-angle-left"></i></div>',
        nextArrow: '<div class="next"><i class="fas fa-angle-right"></i></div>',
        slidesToShow: 1,
        slidesToScroll: 1,
        rtl: rtl == 1 ? true : false
    });

    //===== Magnific Popup

    $('.image-popup').magnificPopup({
        type: 'image',
        gallery:{
          enabled:true
        }
    });
});