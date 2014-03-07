var ipExportImport = new function () {
    "use strict";

    var $this = $(this);


    this.init = function () {
//        $('form').validator(validatorConfig);


        $('form').submit(function (e) {
            var form = $(this);

            // client-side validation OK.
            if (!e.isDefaultPrevented()) {

                $('.ipsLoading').removeClass('hidden');
                $('.ipsImportForm').addClass('hidden');

                $.ajax({
                    url: ip.baseUrl,
                    dataType: 'json',
                    type: 'POST',
                    data: form.serialize(),
                    success: processResponse,
                    error: showError

                });
            }
            e.preventDefault();
        });
    };


    var processResponse = function (response) {
        if (response.status && response.status == 'success') {
            //form has been successfully submitted.


            $('.ipsLoading').addClass('hidden');

            var toClone = $('.ipsLogRecord').first();

            response.log.forEach(function (logRecord) {

                var newClone = toClone.clone();

                newClone.html(logRecord.message);
                switch (logRecord.status) {
                    case 'danger':
                        newClone.addClass('alert-danger');
                        break;
                    case 'info':
                        newClone.addClass('alert-info');
                        break;
                    case 'success':
                        newClone.addClass('alert-success');
                        break;
                    case 'warning':
                        newClone.addClass('alert-warning');
                        break;
                    default:
                        newClone.addClass('alert-warning');
                }

                $('.ipsLog').append(newClone);


            });

            $('.ipsLogRecord').first().remove();

            $('.ipsLog').removeClass('hidden');
            $('.ipsLogRecord').removeClass('hidden');

        } else {
            //PHP controller says there are some errors
            if (response.errors) {
                form.data("validator").invalidate(response.errors);
            }
        }
    };

    var showError = function (response) {
        alert(response);
    }


};


$(document).ready(function () {
    "use strict";
    ipExportImport.init();
});