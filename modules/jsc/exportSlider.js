<link rel="stylesheet" href="modules/jsc/ion_rangeSlider/css/ion.rangeSlider.min.css"/>
<script src="modules/jsc/ion_rangeSlider/js/ion.rangeSlider.min.js"></script>
<script src="modules/jsc/momentjs/moment.min.js"></script>

<div class="range-slider">
   <input type="text" class="js-range-slider" value="" />
</div>

<script>

                    var $range = $(".js-range-slider"),
                    $inputFrom = $(".timefrom"),
                    $inputTo = $(".timeto"),
                    instance,
                    min = 0,
                    max = 0,
                    from = 0,
                    to = 0;

                $range.ionRangeSlider({
                    skin: "round",
                    type: "double",
                    grid: true,
                        grid_num: 12,
                    min: moment("0000", "hhmm").valueOf(),
                    max: moment("2359", "hhmm").valueOf(),
                    from: moment("0942", "hhmm").valueOf(),
                    to: moment("1234", "hhmm").valueOf(),
                    onStart: updateInputs,
                    onChange: updateInputs,
                    force_edges: false,
                    drag_interval: true,
                    step: 1,

                    prettify: function (num) {
                        return moment(num).format('HH:mm');
                        }

                });

                instance = $range.data("ionRangeSlider");

                function updateInputs (data) {
                    from =  moment(data.from).format('HH:mm');
                    to = moment(data.to).format('HH:mm');

                    $inputFrom.prop("value", from);
                    $inputTo.prop("value", to);
                }

                $inputFrom.on("input", function () {
                    var val = $(this).prop("value");
                    if (val < min) {
                        val = min;
                    } else if (val > to) {
                        val = to;
                    }

                    instance.update({
                        from: val
                    });
                });

                $inputTo.on("input", function () {
                    var val = $(this).prop("value");

                    if (val < from) {
                        val = from;
                    } else if (val > max) {
                        val = max;
                    }

                    instance.update({
                        to: val
                    });
                });
            </script>
