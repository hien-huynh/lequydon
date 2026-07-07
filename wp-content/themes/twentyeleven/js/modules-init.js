(function($){
    $(document).ready(function(){
        // Initialize sliders: ensure each slider initializes once
        $('.module-slider .slider-wrap').each(function(){
            var $wrap = $(this);
            if ( $wrap.data('init') ) return; // already initialized
            $wrap.data('init', true);
            var $slides = $wrap.find('.slides .slide');
            var idx = 0;
            $slides.hide().eq(0).show().addClass('active');
            setInterval(function(){
                $slides.eq(idx).fadeOut(400).removeClass('active');
                idx = (idx + 1) % $slides.length;
                $slides.eq(idx).fadeIn(400).addClass('active');
            }, 4000);
        });
    });
})(jQuery);
