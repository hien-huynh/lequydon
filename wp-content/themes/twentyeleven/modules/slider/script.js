jQuery(function ($) {
    $('.showcase-slider').each(function () {
        var $slider = $(this);
        var $tabs = $slider.find('.showcase-slider__tab');
        var $panels = $slider.find('.showcase-slider__panel');
        var index = 0;
        var intervalTime = 2300;

        function showTab(nextIndex) {
            index = nextIndex;
            $tabs.removeClass('is-active ui-tabs-selected');
            $panels.removeClass('is-active');

            $tabs.eq(index).addClass('is-active ui-tabs-selected');
            $panels.eq(index).addClass('is-active');
        }

        $tabs.on('click', function (e) {
            e.preventDefault();
            showTab($(this).index());
        });

        if ($tabs.length > 1) {
            setInterval(function () {
                var nextIndex = (index + 1) % $tabs.length;
                showTab(nextIndex);
            }, intervalTime);
        }
    });
});
