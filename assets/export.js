(function ($) {
    "use strict";

    var ipExport = new function () {

        var $this = $(this);


        this.init = function () {
            $('.ipsExportForm').on('ipSubmitResponse', processResponse);
            $('.ipsExportForm').on('submit', showProgressIndicator);

        };

        var showProgressIndicator = function () {
            $('.ipsLoading').removeClass('hidden');
            $('.ipsFileContainer').addClass('hidden');
            $('.ipsImportForm').addClass('hidden');
            $('.ipsExportForm').addClass('hidden');
        }

        var processResponse = function (event, response) {
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

                $('.ipsExportDownloadUrl').attr('href', response.downloadUrl);
                $('.ipsExportDownloadUrl').html('Download archive ' + response.downloadUrl);

                $('.ipsExportBack').removeClass('hidden');

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
        ipExport.init();
    });

})(jQuery);