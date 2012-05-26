<script type="text/javascript">
var mp_protocol = (("https:" == document.location.protocol) ? "https://" : "http://");
document.write(unescape("%3Cscript src='" + mp_protocol + "api.mixpanel.com/site_media/js/api/mixpanel.js' type='text/javascript'%3E%3C/script%3E"));
</script>

<script type="text/javascript">
try {
//Live:    var mpmetrics = new MixpanelLib('f7c6d4e97eb60ef37009ae54802c1569');
//Beta:
        var mpmetrics = new MixpanelLib('129ca0e2363946ae625af0d1f382c655');
} catch(err) {
    var null_fn = function () {};
    var mpmetrics = {
        track: null_fn,
        track_funnel: null_fn,
        register: null_fn,
        register_once: null_fn,
        register_funnel: null_fn,
        identify: null_fn
    };
}
</script>

