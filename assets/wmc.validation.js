wmc.validation = (function ($) {
    var pub = {
        onerequired: function (value, messages, options) {
            var emptyCount = 0;
            $.each(options.inputIds, function (index) {
                if (yii.validation.isEmpty($("#"+this+"").val())) {
                    emptyCount++;
                }
            });
            if (options.inputIds.length - emptyCount != 1) {
                var message = emptyCount == 0 ? options.messages.tooMany : options.messages.notEnough;
                yii.validation.addMessage(messages, message, value);
            }
        }
    }

    return pub;
})(jQuery);