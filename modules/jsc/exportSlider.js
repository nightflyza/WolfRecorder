<link rel="stylesheet" href="modules/jsc/ion_rangeSlider/css/ion.rangeSlider.min.css"/>
<script src="modules/jsc/ion_rangeSlider/js/ion.rangeSlider.min.js"></script>
<script src="modules/jsc/momentjs/moment.min.js"></script>

<div class="range-slider" style="margin-top: 0;">
   <input type="text" class="js-range-slider" value="" />
</div>

<script>

                    var $range = $(".js-range-slider"),
                    $inputFrom = $(".timefrom"),
                    $inputTo = $(".timeto"),
                    instance,
                    minTs = 0,
                    maxTs = 0,
                    fromStr = '09:42',
                    toStr = '12:34';

                function timeToTs(timeStr) {
                    var parsed = moment(timeStr, 'HH:mm', true);
                    if (!parsed.isValid()) {
                        return null;
                    }
                    return parsed.valueOf();
                }

                function syncInputFromSlider(data) {
                    fromStr = moment(data.from).format('HH:mm');
                    toStr = moment(data.to).format('HH:mm');
                    minTs = data.min;
                    maxTs = data.max;

                    $inputFrom.prop("value", fromStr);
                    $inputTo.prop("value", toStr);
                }

                var initFromStr = $inputFrom.prop("value");
                var initToStr = $inputTo.prop("value");
                var initFromTs = initFromStr ? timeToTs(initFromStr) : moment("0942", "hhmm").valueOf();
                var initToTs = initToStr ? timeToTs(initToStr) : moment("1234", "hhmm").valueOf();
                if (initFromTs === null) {
                    initFromTs = moment("0942", "hhmm").valueOf();
                }
                if (initToTs === null) {
                    initToTs = moment("1234", "hhmm").valueOf();
                }
                if (initFromTs > initToTs) {
                    initFromTs = moment("0942", "hhmm").valueOf();
                    initToTs = moment("1234", "hhmm").valueOf();
                }

                $range.ionRangeSlider({
                    skin: "round",
                    type: "double",
                    grid: true,
                        grid_num: 12,
                    min: moment("00:00", "HH:mm").valueOf(),
                    max: moment("23:59", "HH:mm").valueOf(),
                    from: initFromTs,
                    to: initToTs,
                    onStart: syncInputFromSlider,
                    onChange: syncInputFromSlider,
                    force_edges: false,
                    drag_interval: true,
                    step: 1,

                    prettify: function (num) {
                        return moment(num).format('HH:mm');
                        }

                });

                instance = $range.data("ionRangeSlider");

                function applyManualFrom() {
                    var val = $inputFrom.prop("value");
                    var ts = timeToTs(val);
                    var toTs = timeToTs($inputTo.prop("value"));

                    if (ts === null) {
                        $inputFrom.prop("value", fromStr);
                        return;
                    }
                    if (ts < minTs) {
                        ts = minTs;
                    }
                    if (toTs !== null && ts > toTs) {
                        ts = toTs;
                    }

                    $inputFrom.prop("value", moment(ts).format('HH:mm'));
                    instance.update({
                        from: ts
                    });
                }

                function applyManualTo() {
                    var val = $inputTo.prop("value");
                    var ts = timeToTs(val);
                    var fromTs = timeToTs($inputFrom.prop("value"));

                    if (ts === null) {
                        $inputTo.prop("value", toStr);
                        return;
                    }
                    if (fromTs !== null && ts < fromTs) {
                        ts = fromTs;
                    }
                    if (ts > maxTs) {
                        ts = maxTs;
                    }

                    $inputTo.prop("value", moment(ts).format('HH:mm'));
                    instance.update({
                        to: ts
                    });
                }

                $inputFrom.on("change blur", applyManualFrom);
                $inputTo.on("change blur", applyManualTo);
            </script>
