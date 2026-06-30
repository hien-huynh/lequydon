jQuery(function ($) {
    var $tabs = $('.showcase-slider__tab');
    var $panels = $('.showcase-slider__panel');
    var index = 0;
    var intervalTime = 2500;

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

    // setInterval(function () {
    //     var nextIndex = (index + 1) % $tabs.length;
    //     showTab(nextIndex);
    // }, intervalTime);
});
