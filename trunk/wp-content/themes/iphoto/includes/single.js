jQuery(document).ready(function(i) {
    var h = i("#sidebar-inner"),
        f = i("div.post-content  > p > a:has(img)"),
        j = i(window),
        g = h.offset().top;
    f.css("display", "block");
    f.phzoom({});
    j.scroll(function() {
        var a = j.scrollTop();
        if (a + 20 >= g) {
            h.css({
                position: "fixed",
                top: 20
            })
        } else {
            if (a + 20 < g) {
                h.css({
                    position: "static",
                    top: ""
                })
            }
        }
    })
});