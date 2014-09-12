(function($) {

    var limitValue = function (value, min, max) {
        value = Math.min(max, value);
        value = Math.max(min, value);

        return value;
    };

    var counter = 0;
    var data = {};

    $.fn.panCrop = function(method) {
        var methods = {
            init : function(options) {
                this.panCrop.settings = $.extend({}, this.panCrop.defaults, options);

                return this.each(function() {
                    var $element = $(this),
                        settings = $element.panCrop.settings;

                    loadOriginalImageSize($element[0].src, function (originalImageSize) {
                        var id = counter;
                        counter++;

                        $element.data('panCropId', id);
                        data[id] = {};

                        data[id].settings = settings;
                        data[id].crop = {
                            x: 0,
                            y: 0,
                            w: settings.width,
                            h: settings.height,
                            s: Math.max(Math.max(1, settings.width / originalImageSize.w), settings.height / originalImageSize.h)
                        };
                        data[id].state = {
                            originalSize: originalImageSize,
                            minScale: Math.max(settings.width / originalImageSize.w, settings.height / originalImageSize.h)
                        };

                        createWrapper($element);
                        initializeCss($element);
                        bindMouseDragEvents($element);

                        if (settings.onLoad) {
                            settings.onLoad.call($element);
                        }
                    });
                });
            },

            destroy : function () {
                var $element = $(this);

                if (getElementData($element)) {
                    for (var prop in getElementData($element).cssBackup) {
                        $element[0].style[prop] = getElementData($element).cssBackup[prop];
                    }
                    delete getElementData($element).$wrapper;
                    $element.unwrap();

                    // Unbind events only for this instance of cropper
                    var id = $element.data('panCropId');
                    $element.unbind('mousemove.pancrop.' + id + ' mouseup.pancrop.' + id + ' mousedown.pancrop.' + id);
                }
            },

            scale : function (scale) {
                var $element = $(this);
                var settings = getElementData($element).settings;
                var cropValues = getElementData($element).crop;
                var originalSize = getElementData($element).state.originalSize;

                var oldWidth  = $element.width();
                var oldHeight = $element.height();

                var scale = limitValue(limitValue(scale, settings.width / originalSize.w, 1), settings.height / originalSize.h, 1);
                cropValues.s = scale;
                syncViewToState($element, cropValues);

                var widthRatio  = $element.width() / oldWidth;
                var heightRatio = $element.height() / oldHeight;

                var newX1 = (cropValues.x + settings.width / 2) * widthRatio - settings.width / 2;
                var newY1 = (cropValues.y + settings.height / 2) * heightRatio - settings.height / 2;

                cropValues.x = limitValue(newX1, 0, $element.width() - settings.width);
                cropValues.y = limitValue(newY1, 0, $element.height() - settings.height);
                syncViewToState($element, cropValues);

                return cropValues.s;
            },

            getCropData : function () {
                var data = $.extend({}, getElementData($(this)).crop);

                data.sx = parseInt(data.x / data.s);
                data.sy = parseInt(data.y / data.s);
                data.sw = parseInt(data.w / data.s);
                data.sh = parseInt(data.h / data.s);

                return data;
            }
        };

        var getElementData = function ($element) {
            return data[$element.data('panCropId')] || null;
        };

        var createWrapper = function ($element) {
            var settings = getElementData($element).settings;
            var $wrapper = $('<div/>')
                .css({
                    width: settings.width,
                    height: settings.height,
                    overflow: 'hidden'
                });
            getElementData($element).$wrapper = $wrapper;
            $element.wrap($wrapper);
        };

        var initializeCss = function ($element) {
            getElementData($element).cssBackup          = {};
            getElementData($element).cssBackup.position = $element[0].style.position;
            getElementData($element).cssBackup.top      = $element[0].style.top;
            getElementData($element).cssBackup.left     = $element[0].style.left;

            $element.css('position', 'relative');

            syncViewToState($element, getElementData($element).crop);
        };

        var bindMouseDragEvents = function ($element) {
            var settings = getElementData($element).settings;
            var dragging = false;
            var startX   = 0;
            var startY   = 0;
            var id       = $element.data('panCropId');

            $element.on('mousedown.pancrop.' + id, function (e) {
                e.preventDefault();

                dragging = true;
                startX = e.pageX;
                startY = e.pageY;
            });

            $(document).on('mouseup.pancrop.' + id, function () {
                dragging = false;
            });

            $element.on('mousemove.pancrop.' + id, function (e) {
                if (dragging) {
                    var deltaX = e.pageX - startX;
                    var deltaY = e.pageY - startY;
                    startX = e.pageX;
                    startY = e.pageY;
                    var cropValues = getElementData($element).crop;
                    
                    cropValues.x = - limitValue(- cropValues.x + deltaX, settings.width - $element.width(), 0);
                    cropValues.y = - limitValue(- cropValues.y + deltaY, settings.height - $element.height(), 0);

                    syncViewToState($element, cropValues);
                }
            });
        };

        var syncViewToState = function ($element, stateObj) {
            var originalSize = getElementData($element).state.originalSize;
            $element.width(originalSize.w * stateObj.s);
            $element.height(originalSize.h * stateObj.s);
            $element.css({
                left: - stateObj.x,
                top: - stateObj.y
            });
        };

        var loadOriginalImageSize = function (src, callback) {
            var img = new Image();
            img.onload = function () {
                callback({
                    w: img.width,
                    h: img.height
                });
            };
            img.src = src;
        };

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error( 'Method "' +  method + '" does not exist in panCrop plugin!');
        }

    };

    $.fn.panCrop.defaults = {};

    $.fn.panCrop.settings = {};

})(jQuery);