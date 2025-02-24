(function ($) {
    $(
        function () {
        
            $(".bpfb_toolbar_container").append(
                '&nbsp;' +
                '<a href="" title="' + l10nBpfbDocs.add_documents + '" class="bpfb_toolbarItem" id="bpfb_addDocuments"><span>' + l10nBpfbDocs.add_documents + '</span></a>'
            );
            $(document).on(
                'click', '#bpfb_addDocuments', function () {
                    console.log(l10nBpfbDocs.group_id, $('#whats-new-post-in').val());
                    var group_id = l10nBpfbDocs.group_id;
                    if ('0' === group_id) {
                        group_id = $('#whats-new-post-in').length ? $('#whats-new-post-in').val() : 0;
                    }

                    if (parseInt(group_id)) {
                        $.post(
                            ajaxurl, {
                                "action": "bpfb_documents_add_page",
                                "data": group_id
                            }, function (data) {

                                if (!data.url) {
                                    alert(data.warning);
                                    return false;
                                }
                                else {
                                    window.location = data.url;
                                }
                            }
                        );
                    }
                    else {
                        alert(l10nBpfbDocs.no_group_selected);
                        return false;
                    }
                    return false;
                }
            );
        }
    );
})(jQuery);