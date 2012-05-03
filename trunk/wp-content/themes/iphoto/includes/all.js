jQuery(document).ready(function(i) {
    var a = !-[1, ] && !window.XMLHttpRequest,
        l = i("#login a"),
        j = i("#info"),
        m = i("body"),
        b = function() {
            i("#login-form").fadeOut();
            i("#login-lay").fadeOut()
        },
        k = function() {
            if (!i("#info-content").is(".hidden")) {
                i("#info").removeClass("active");
                i("#info-content").addClass("hidden")
            }
        };
    m.click(function() {
        b();
        k()
    });
    l.click(function(c) {
        c.preventDefault();
        m.append('<div id="login-lay"></div>');
        if (!a) {
            i("#login-lay").fadeTo(500, 0.7)
        }
        i("#login-form").fadeIn();
        i("#login-form input:first").focus()
    });
    i("#login-form,#login-form input,#login a,#info").click(function(c) {
        c.stopPropagation()
    });
    j.click(function() {
        i(this).toggleClass("active");
        if (i("#info-content").hasClass("hidden")) {
            i("#info-content").removeClass("hidden")
        } else {
            i("#info-content").addClass("hidden")
        }
        return false
    });
    j.mouseover(function() {
        i(this).addClass("active");
        if (i("#info-content").hasClass("hidden")) {
            i("#info-content").removeClass("hidden")
        }
    })
});