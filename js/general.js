//last edit on 27/8/2013 stergatu

jQuery(document).ready(
    function ($) {

        //Hide the sort form submit, we're gonna submit on change
        $('#bp-group-documents-sort-form input[type=submit]').hide();
        $('#bp-group-documents-sort-form select[name=bpgrd-order]').change(
            function () {
                $('form#bp-group-documents-sort-form').submit();
            }
        );

        //Hide the category form submit, we're gonna submit on change
        $('#bp-group-documents-category-form input[type=submit]').hide();
        $('#bp-group-documents-category-form select[name=bpgrd-category]').change(
            function () {
                $('form#bp-group-documents-category-form').submit();
            }
        );

        //Hide the upload form by default, expand as needed
        $('#bp-group-documents-upload-new').hide();
        $('#bp-group-documents-upload-button').show();
        $('#bp-group-documents-upload-button').click(
            function () {
                $('#bp-group-documents-upload-button').slideUp();
                $('#bp-group-documents-upload-new').slideDown();
                return false;
            }
        );

        //prefill the new category field
        $('input.bp-group-documents-new-category').val(l10nBpGrDocuments.new_category).css('color','#999').focus(
            function () {
                $(this).val('').css('color','inherit');
            }
        );

        //check for presence of a file before submitting form
        $('form#bp-group-documents-form').submit(
            function () {

                //check for pre-filled values, and remove before sumitting
                if ($('input.bp-group-documents-new-category').val() === l10nBpGrDocuments.new_category ) {
                    $('input.bp-group-documents-new-category').val('');
                }
                if ($('input[name=bp_group_documents_operation]').val() == 'add' ) {
                    if ($('input.bp-group-documents-file').val()) {
                        return true;
                    }
                    alert(l10nBpGrDocuments.no_file_selected);
                    return false;
                }
            }
        );

        //validate group admin form before submitting
        $('form#group-settings-form').submit(
            function () {

                //check for pre-filled values, and remove before sumitting
                if ($('input.bp-group-documents-new-category').val() === l10nBpGrDocuments.new_category  ) {
                    $('input.bp-group-documents-new-category').val('');
                }
            }
        );

        //Make the user confirm when deleting a document
        $('a.bp-group-documents-delete').click(
            function () {
                return confirm(l10nBpGrDocuments.sure_to_delete_document);
            }
        );

        //add new single categories in the group admin screen via ajax
        $('#group-documents-group-admin-categories input[value=Add]').click(
            function () {
                $.post(
                    ajaxurl,
                    {
                        action:'group_documents_add_category',
                        category:$('input[name=bp_group_documents_new_category]').val()
                    },
                    function (response) {
                        $('#group-documents-group-admin-categories input[value=Add]').parent().before(response);
                    }
                );
                return false;
            }
        );
    }
);
