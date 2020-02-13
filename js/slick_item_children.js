 $(document).ready(function () {
     $('.children_carousel').slick({
         infinite: false,
         slidesToShow: 3,
         slidesToScroll: 1,
         speed: 0,
         prevArrow: '<button class="slide-arrow prev-arrow"></button>',
         nextArrow: '<button class="slide-arrow next-arrow"></button>',
         variableWidth: false,
         responsive: [
             {
                 breakpoint: 1200,
                 settings: {
                     slidesToShow: 2,
                     slidesToScroll: 1
                 }
                            },
             {
                 breakpoint: 800,
                 settings: {
                     slidesToShow: 1,
                     slidesToScroll: 1
                 }
                            }

                          ]
     });
 });
