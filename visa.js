$(document).ready(function () {
    const $card = $('#card');
    const $inputNum = $('#inputNum');
    const $inputName = $('#inputName');
    const $inputDate = $('#inputDate');
    const $inputCvc = $('#inputCvc');

    const $displayNum = $('#displayNum');
    const $displayName = $('#displayName');
    const $displayDate = $('#displayDate');
    const $displayCvc = $('#displayCvc');

    const $submitBtn = $('.btn-submit');

    const $errorCard = $("#errorCard");
    const $errorMonth = $("#errorMonth");
    const $errorYear = $("#errorYear");
    const $errorCvc = $("#errorCvc");
    const $errorName = $("#errorName");

    const $popup = $("#visaSuccessPopup");
    const $closeBtn = $("#closeVisaPopup");

    $inputCvc.on('focus', () => $card.addClass('is-flipped'));
    $inputCvc.on('blur', () => $card.removeClass('is-flipped'));

    $inputNum.on('input', function () {
        let val = $(this).val().replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
        $(this).val(val);
        $displayNum.text(val || '•••• •••• •••• ••••');
        $errorCard.toggle(val.replace(/\s/g, '').length !== 16);
    });
    $inputName.on('input', function () {
        let val = $(this).val().replace(/[^a-zA-Z\s]/g, '');
        $(this).val(val);
        $displayName.text(val.toUpperCase() || 'FULL NAME');
        $errorName.toggle(val.trim() === "");
    });
    $inputDate.on('input', function () {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length >= 3) val = val.substring(0, 2) + '/' + val.substring(2, 4);
        $(this).val(val);
        $displayDate.text(val || '••/••');

        const month = parseInt(val.substring(0, 2));
        const year = parseInt(val.substring(3, 5));
        $errorMonth.toggle(!month || month < 1 || month > 12);
        $errorYear.toggle(!year || val.length < 5);
    });
    $inputCvc.on('input', function () {
        let val = $(this).val().replace(/\D/g, '');
        $(this).val(val);
        $displayCvc.text(val || '•••');
        $errorCvc.toggle(val.length !== 3);
    });


    function validateForm() {
        let hasError = false;
        if ($inputName.val().trim() === "") { $errorName.show(); hasError = true; } else $errorName.hide();
        if ($inputNum.val().replace(/\s/g, '').length !== 16) { $errorCard.show(); hasError = true; } else $errorCard.hide();

        const val = $inputDate.val();
        const month = parseInt(val.substring(0, 2));
        const year = parseInt(val.substring(3, 5));

        if (!month || month < 1 || month > 12) { $errorMonth.show(); hasError = true; } else $errorMonth.hide();
        if (!year || val.length < 5) { $errorYear.show(); hasError = true; } else $errorYear.hide();
        if ($inputCvc.val().length !== 3) { $errorCvc.show(); hasError = true; } else $errorCvc.hide();

        return !hasError;
    }

    function showPopup() { $popup.css('display', 'flex'); }

    $closeBtn.on('click', function () {
        $popup.hide();

        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');

        if (orderId) {
            window.location.href = "thankyou.php?order_id=" + orderId;
        } else {
            window.location.href = "thankyou.php";
        }
    });
    $submitBtn.on('click', function (e) {
        e.preventDefault();
        if (validateForm()) showPopup();
    });
});