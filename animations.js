$(document).ready(function () {

    // ================== FADE IN ENTIRE PAGE ==================
    $(".container, .form-container, .page-container").hide().fadeIn(600);

    // ================== BUTTON HOVER ==================
    $("button, .btn").hover(
        function () {
            $(this).stop(true, true).css({
                transform: "scale(1.03)",
                boxShadow: "0 6px 16px rgba(0, 56, 128, 0.3)"
            });
        },
        function () {
            $(this).stop(true, true).css({
                transform: "scale(1)",
                boxShadow: "none"
            });
        }
    );

    // ================== INPUT FOCUS ==================
    $("input, select, textarea").focus(function () {
        $(this).css({
            borderColor: "#0056b3",
            boxShadow: "0 0 4px rgba(0,86,179,0.25)"
        });
    }).blur(function () {
        $(this).css({
            borderColor: "#cfd8e3",
            boxShadow: "none"
        });
    });

    // ================== TABLE ROW HOVER ==================
    $(".order-table tbody tr, .cart-table tbody tr").hover(
        function () { $(this).css("background-color", "#f5f5f5"); },
        function () { $(this).css("background-color", ""); }
    );

    // ================== COLLAPSIBLE SECTIONS ==================
    $(".collapsible").click(function () {
        $(this).next(".content").slideToggle(300);
        $(this).toggleClass("active");
    });

    // ================== CART ADD/REMOVE ==================
    $(".cart-item .remove, .cart-item .add").click(function () {
        $(this).closest(".cart-item").fadeOut(300, function () {
            $(this).remove();
        });
    });

    // ================== TOAST NOTIFICATIONS ==================
    function showToast(message, type = "success") {
        const toast = $(`<div class='toast ${type}'>${message}</div>`).hide();
        $("body").append(toast);
        toast.fadeIn(400).delay(2000).fadeOut(400, function () { $(this).remove(); });
    }
    // Example: showToast("Item added to cart!");
});
