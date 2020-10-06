(function ($, undefined) {

    function saveData(el, data) {
        const HTTP_SERVER_LAG = 20;

        return new Promise(resolve => {
            setTimeout(() => {
                // console.log(data, el);

                var copy = $(el).data("copy");
                var type = $(el).data("type");

                if (typeof copy !== typeof undefined && copy !== false) {
                    // Element has this attribute
                    if ($(copy.length > 0)) {
                        var rawHTML = data;
                        if (type === "inline") {
                            rawHTML = $(rawHTML).html();
                            // var rawHTML = $(rawHTML).html().replace('/< /?[a|br|li|ol|ul]+/?>/igm', '');
                        }

                        $(copy).val(rawHTML);
                    }
                }

                resolve();

            }, HTTP_SERVER_LAG);
        });
    }

    $.nette.ext('modalArticle', {
        success: function (payload, status, xhr, settings) {

            var modalAction = settings.modalAction ? settings.modalAction : false;

            if (modalAction) {
                var classicEditor = CKEditor.ClassicEditor;

                $('form .editor').each(function (index, el) {

                    var toolbar = $(el).data("toolbar");
                    var type = $(el).data("type");
                    var language = $("html").attr('lang');

                    var config = {
                        // extraPlugins: [ ConvertDivAttributes, AddClassToAllLinks ],
                        // extraPlugins: [ AddClassToAllLinks ],
                        autosave: {
                            save( editor ) {
                                // The saveData() function must return a promise
                                // which should be resolved when the data is successfully saved.
                                return saveData( editor.sourceElement, editor.getData() );
                            }
                        },
                    };

                    if (toolbar && $.isArray(toolbar)) {
                        config['toolbar'] = toolbar;
                    }

                    if (language) config['language'] = language;

                    classicEditor
                        .create($(this).get(0), config)
                        .then(newEditor => {
                            const sel = newEditor.model.document.selection;
                        })
                        .catch(error => {
                            console.error(error);
                        });

                });
            }
        }
    });




    $(function () {

        $.fn.modal.Constructor.prototype.enforceFocus = function () {
            console.log("AAAAAAAAAAAAAAAAAAAAAA")
        };


        /*
                $.fn.modal.Constructor.prototype.enforceFocus = function () {
                    var $modalElement = this.$element;

                    console.log($modalElement);
                    $(document).on('focusin.modal', function (e) {
                        var $parent = $(e.target.parentNode);
                        if ($modalElement[0] !== e.target && !$modalElement.has(e.target).length &&
                            !$parent.hasClass('cke_dialog_ui_input_select') && !$parent.hasClass('cke_dialog_ui_input_text')) {

                        }
                    })
                };
        */
    });

})(jQuery);
