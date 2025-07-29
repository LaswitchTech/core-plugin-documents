const DocumentsModal = function(id){

    // AJAX Request
    $.ajax({
        url: '/api/documents/fetch?id=' + id,
        type: 'GET',dataType: 'json',
        success: function(response) {
            console.log(response);

            // Create a modal
            builder.Component(
                "modal",
                {
                    onEnter: true,
                    destroy:true,
                    icon: "file-earmark-pdf",
                    title: builder.Locale.get("Document Details"),
                    cancel: false,
                    submit: false,
                    size: "xl",
                },
                function(modal,component){

                    // Styling
                    component.addClass('modal-primary');
                    component.body.addClass('p-0');
                    component.footer.remove();

                    // Add the Preview
                    component.body.viewer = builder.Component(
                        "pdfviewer",
                        component.body,
                        {
                            class: {
                                component: "bg-glass",
                                toolbar: "bg-glass",
                            },
                            filename: response.record.filename,
                            url:"/documents/get?uuid="+response.record.uuid,
                            scale: 1,
                            password: response.record.doctype.uuid,
                            pageNum: 1,
                            verticalScroll: true,
                            renderText: false,
                            smallToolbar: true,
                        },
                        function(viewer,component){
                            // console.log(viewer,component);

                            // Styling
                            component.removeClass('rounded').removeClass('bg-glass').addClass('rounded-bottom');
                            component.toolbar.removeClass('rounded-top');

                            // Add action button Archive
                            if(response.record.isArchived == 0){
                                component.toolbar.actions.archive = $(document.createElement('button')).attr({
                                    "type": "button",
                                    "class": "btn btn-dark",
                                }).prependTo(component.toolbar.actions);
                                component.toolbar.actions.archive.icon = $(document.createElement('i')).addClass('bi bi-archive').prependTo(component.toolbar.actions.archive);
                                component.toolbar.actions.archive.click(function(){

                                    // Close the modal
                                    modal.hide();

                                    // Open modal
                                    DocumentsModalArchive(response.record);
                                });
                            } else {
                                component.toolbar.actions.archive = $(document.createElement('button')).attr({
                                    "type": "button",
                                    "class": "btn btn-info",
                                }).prependTo(component.toolbar.actions);
                                component.toolbar.actions.archive.icon = $(document.createElement('i')).addClass('bi bi-arrow-counterclockwise').prependTo(component.toolbar.actions.archive);
                                component.toolbar.actions.archive.click(function(){

                                    // Close the modal
                                    modal.hide();

                                    // Open modal
                                    DocumentsModalRecover(response.record);
                                });
                            }

                            // Add action button Approve
                            if(response.record.isApproved == 0){
                                component.toolbar.actions.approve = $(document.createElement('button')).attr({
                                    "type": "button",
                                    "class": "btn btn-success",
                                }).text(builder.Locale.get('Approve')).prependTo(component.toolbar.actions);
                                component.toolbar.actions.approve.icon = $(document.createElement('i')).addClass('me-1 bi bi-check2').prependTo(component.toolbar.actions.approve);
                                component.toolbar.actions.approve.click(function(){

                                    // Close the modal
                                    modal.hide();

                                    // Open modal
                                    DocumentsModalApprove(response.record);
                                });
                            } else {
                                component.toolbar.actions.approve = $(document.createElement('button')).attr({
                                    "type": "button",
                                    "class": "btn btn-danger",
                                }).text(builder.Locale.get('Disapprove')).prependTo(component.toolbar.actions);
                                component.toolbar.actions.approve.icon = $(document.createElement('i')).addClass('me-1 bi bi-ban').prependTo(component.toolbar.actions.approve);
                                component.toolbar.actions.approve.click(function(){

                                    // Close the modal
                                    modal.hide();

                                    // Open modal
                                    DocumentsModalDisapprove(response.record);
                                });
                            }

                            // // Add action button Secure
                            // component.toolbar.actions.secure = $(document.createElement('button')).attr({
                            //     "type": "button",
                            //     "class": "btn btn-light",
                            // }).text(builder.Locale.get('Secure')).prependTo(component.toolbar.actions);
                            // component.toolbar.actions.secure.icon = $(document.createElement('i')).addClass('me-1 bi bi-lock').prependTo(component.toolbar.actions.secure);
                            // component.toolbar.actions.secure.click(function(){
                            //     console.log('Secure');
                            // });

                            // Add action button Variables
                            if(response.record.isApproved == 0){
                                component.toolbar.actions.docvals = $(document.createElement('button')).attr({
                                    "type": "button",
                                    "class": "btn btn-warning",
                                }).text(builder.Locale.get('Variables')).prependTo(component.toolbar.actions);
                                component.toolbar.actions.docvals.icon = $(document.createElement('i')).addClass('me-1 bi bi-list-check').prependTo(component.toolbar.actions.docvals);
                                component.toolbar.actions.docvals.click(function(){

                                    // Close the modal
                                    modal.hide();

                                    // Create a modal
                                    DocumentsModalVars(response.record);
                                });
                            }

                            // Add action button Letterhead
                            if(response.record.doctype.hasLetterhead == 1 && response.record.isApproved == 0){
                                if(response.record.letterhead.id == null){
                                    component.toolbar.actions.letterhead = $(document.createElement('button')).attr({
                                        "type": "button",
                                        "class": "btn btn-primary",
                                    }).text(builder.Locale.get('Letterhead')).prependTo(component.toolbar.actions);
                                    component.toolbar.actions.letterhead.icon = $(document.createElement('i')).addClass('me-1 bi bi-file-earmark-arrow-up').prependTo(component.toolbar.actions.letterhead);
                                    component.toolbar.actions.letterhead.click(function(){

                                        // Close the modal
                                        modal.hide();

                                        // Open modal
                                        DocumentsModalLetterhead(response.record);
                                    });
                                } else {
                                    component.toolbar.actions.letterhead = $(document.createElement('button')).attr({
                                        "type": "button",
                                        "class": "btn btn-danger",
                                    }).text(builder.Locale.get('Letterhead')).prependTo(component.toolbar.actions);
                                    component.toolbar.actions.letterhead.icon = $(document.createElement('i')).addClass('me-1 bi bi-file-earmark-x').prependTo(component.toolbar.actions.letterhead);
                                    component.toolbar.actions.letterhead.click(function(){

                                        // Close the modal
                                        modal.hide();

                                        // Open modal
                                        DocumentsModalLetterheadRemove(response.record);
                                    });
                                }
                            }

                            // Open the modal
                            modal.show();
                        },
                    );
                }
            );
        }
    });
}
const DocumentsModalCreate = function(feed = null, defaults = {}, locale = null, callback = null){

    // Set the locale
    locale = locale || builder.Locale.current();

    // AJAX Request
    $.ajax({
        url: '/api/doctypes/fetchAll',
        headers: {'X-CSRF-Authorization': CSRF_KEY},
        type: 'POST',dataType: 'json',
        data: {
            conditions: [
                {key: 'isArchived', operator: '<>', value: 1},
            ]
        },
        success: function(response) {

            // Generate document types
            var types = [];
            var defaultType = null;
            for(const [key, type] of Object.entries(response.records)){
                if(locale == type.locale){
                    types.push({id: type.id,text: type.title});
                    if(type.name == (defaults.type || null)){
                        defaultType = type.id;
                    }
                }
            }

            // Create a modal
            builder.Component(
                "modal",
                null,
                {
                    onEnter: false,
                    destroy: true,
                    icon: "file-earmark-plus",
                    title: builder.Locale.get("Create a new Document"),
                    cancel: false,
                    submit: true,
                    callback: {
                        submit: function(element,modal){

                            // Create a spinner animate-rotate
                            var spinner = $(document.createElement('div')).attr({
                                "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                                "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                            }).appendTo(element);

                            // Hide the dialog
                            element.dialog.addClass('opacity-0');

                            // Setup a spinner while waiting for the modal to be submitted
                            setTimeout(() => {

                                // Hide the dialog
                                element.dialog.hide();

                                // Add flex to the modal
                                element.addClass('d-flex align-items-center justify-content-center');

                                // Show the spinner
                                spinner.removeClass('d-none');

                                // Submit the form
                                element.form.submit();
                            }, 300);
                        },
                    },
                },
                function(modal,component){

                    // Save the component
                    const componentModal = component;

                    // Style the modal
                    component.addClass('modal-success');
                    component.footer.submit.addClass('btn-success').removeClass('btn-link').attr({
                        "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
                    }).text(builder.Locale.get('Create'));
                    component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-stars me-1').prependTo(component.footer.submit);

                    // Create the form
                    component.form = builder.Component(
                        'form',
                        component.body,
                        {
                            class:{form: 'row',field: 'col'},
                            callback:{
                                val: function(values){

                                    // Add default fields
                                    for(const [key, value] of Object.entries(defaults)){
                                        if(key != 'type'){
                                            values[key] = value;
                                        }
                                    }

                                    return values;
                                },
                                submit: function(form){

                                    // AJAX Request
                                    $.ajax({
                                        url: '/api/documents/create',
                                        headers: {'X-CSRF-Authorization': CSRF_KEY},
                                        type: 'POST',dataType: 'json',
                                        data: form.val(),
                                        success: function(response) {

                                            // Check if the feed is defined
                                            if(feed){

                                                // Add the new document to the list
                                                feed.add(
                                                    {},
                                                    function(item){

                                                        // Format the document
                                                        DocumentFormat(item, response.record);
                                                    },
                                                );
                                            }

                                            // Check if the callback is defined
                                            if(typeof callback === "function"){
                                                callback(response.record);
                                            }

                                            // Hide the modal
                                            modal.hide();
                                        }
                                    });
                                },
                            },
                        },
                        function(form,component){

                            // type
                            form.add(
                                {
                                    name: 'type',
                                    label: builder.Locale.get('Type'),
                                    icon: 'file-earmark-break',
                                    type: 'select2',
                                    options: types,
                                    modal: componentModal,
                                    value: defaultType,
                                },
                            );

                            // Open the modal
                            modal.show();
                        },
                    );
                },
            );
        }
    });
}
const DocumentsModalVars = function(doc){

    // Create a modal
    builder.Component(
        "modal",
        {
            onEnter: true,
            destroy:true,
            icon: "input-cursor-text",
            title: builder.Locale.get("Document Variables"),
            cancel: false,
            submit: true,
            size: "lg",
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // Submit the form
                        element.form.submit();
                    }, 300);
                },
                onHide: function(component,modal){
                    DocumentsModal(doc.id);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Styling
            component.addClass('modal-warning');
            component.footer.submit.addClass('btn-success').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            });
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-save me-1').prependTo(component.footer.submit);

            // Create the form
            component.form = builder.Component(
                'form',
                component.body,
                {
                    class:{
                        form: 'row row-cols-2',
                        field: 'mb-3 col',
                    },
                    callback:{
                        val: function(values){
                            var data = {docvals: values};
                            for(const [key, variable] of Object.entries(doc.docvals)){
                                if(typeof data.docvals[key] === 'undefined'){
                                    data.docvals[key] = variable;
                                }
                            }
                            return data;
                        },
                        submit: function(form){
                            $.ajax({
                                url: '/api/documents/update?id='+doc.id,
                                headers: {'X-CSRF-Authorization': CSRF_KEY},
                                type: 'POST',dataType: 'json',
                                data: form.val(),
                                success: function(response) {

                                    // Hide the modal
                                    modal.hide();
                                }
                            });
                        },
                    },
                },
                function(form,component){

                    // Add a row count
                    var row = 0;
                    var count = 0;

                    // Loop through the variables
                    for(const [key, variable] of Object.entries(doc.doctype.template.variables)){
                        const name = variable.replace(/{{/g, '').replace(/}}/g, '');
                        const label = builder.Helper.ucwords(variable.replace(/{{/g, '').replace(/}}/g, '').replace(/_/g, ' '));
                        let type = 'text';
                        switch(name){
                            case 'phone':
                            case 'user_phone':
                            case 'organization_phone':
                            case 'mobile':
                            case 'user_mobile':
                            case 'organization_mobile':
                                type = 'phone-extension';
                                break;
                            case 'tollfree':
                            case 'user_tollfree':
                            case 'organization_tollfree':
                                type = 'phone-international';
                                break;
                            case 'fax':
                            case 'user_fax':
                            case 'organization_fax':
                                type = 'phone';
                                break;
                            case 'email':
                            case 'user_email':
                            case 'organization_email':
                                type = 'email';
                                break;
                            case 'state':
                            case 'user_state':
                            case 'organization_state':
                                type = 'state';
                                break;
                            case 'country':
                            case 'user_country':
                            case 'organization_country':
                                type = 'country';
                                break;
                            case 'zipcode':
                            case 'user_zipcode':
                            case 'organization_zipcode':
                                type = 'zipcode';
                                break;
                            case 'locale':
                            case 'user_locale':
                            case 'organization_locale':
                                type = 'locale';
                                break;
                            case 'businessNumber':
                            case 'user_businessNumber':
                            case 'organization_businessNumber':
                                type = 'business-number';
                                break;
                            case 'taxExtension':
                            case 'user_taxExtension':
                            case 'organization_taxExtension':
                            case 'importerExtension':
                            case 'user_importerExtension':
                            case 'organization_importerExtension':
                                type = 'tax-extension';
                                break;
                            case 'date':
                            case 'until':
                            case 'from':
                                type = 'date';
                                break;
                            case 'rate':
                                type = 'number';
                                break;
                        }

                        // Check if the variable is in the doc.doctype.locked array
                        if(!doc.doctype.locked.includes(name)){

                            // Add field to the form
                            form.add(
                                {
                                    name: name,
                                    label: label,
                                    type: type,
                                    value: doc.docvals[name] || '',
                                },
                                function(input,form){

                                    // Increment the row if the input is a multiple of 3
                                    if(count % 2 == 0){
                                        row++;
                                    }

                                    // Add the row count to the input
                                    input.attr('data-row', row);

                                    // Increment the count
                                    count++;
                                },
                            );
                        }
                    }

                    // Remove the margin on the last row
                    component.find('[data-row="'+row+'"]').removeClass('mb-3');

                    // Show the modal
                    modal.show();
                },
            );
        }
    );
}
const DocumentsModalLetterhead = function(doc){

    // Create a modal
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "file-earmark-arrow-up",
            title: builder.Locale.get("Upload a Letterhead"),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // Submit the form
                        element.form.submit();
                    }, 300);
                },
                onHide: function(component,modal){
                    DocumentsModal(doc.id);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Style the modal
            component.addClass('modal-primary');
            component.footer.submit.addClass('btn-primary').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Upload'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-upload me-1').prependTo(component.footer.submit);

            // Create the form
            component.form = builder.Component(
                'form',
                component.body,
                {
                    class:{form: 'row',field: 'col'},
                    callback:{
                        submit: function(form){

                            // Get the values
                            var values = form.val();

                            // Run the file promise
                            values.file.then(fileData => {

                                // Loop through the files
                                for(const [id, file] of Object.entries(fileData)){

                                    // Add some properties
                                    file.isPublic = 1;
                                    file.targetTable = 'documents';
                                    file.targetId = doc.id;

                                    // Generate a md5 checksum
                                    builder.Helper.md5(file.content.split(',')[1],function(checksum){

                                        // Save the checksum
                                        file.checksum = checksum;

                                        // AJAX Request
                                        $.ajax({
                                            url: '/api/files/upload',
                                            headers: {'X-CSRF-Authorization': CSRF_KEY},
                                            type: 'POST',dataType: 'json',
                                            data: file,
                                            success: function(response) {

                                                // Initialize the updateData
                                                var updateData = {letterhead: response.record.id};

                                                // AJAX Request
                                                $.ajax({
                                                    url: '/api/documents/update?id='+doc.id,
                                                    headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                    type: 'POST',dataType: 'json',
                                                    data: updateData,
                                                    success: function(response) {

                                                        // Hide the modal
                                                        modal.hide();
                                                    }
                                                });
                                            }
                                        });
                                    });
                                }
                            }).catch(error => {
                                console.error('Error reading files:', error);
                            });
                        },
                    },
                },
                function(form,component){

                    // file
                    form.add(
                        {
                            name: 'file',
                            label: builder.Locale.get('Letterhead'),
                            icon: 'file-earmark-arrow-up',
                            type: 'file',
                            modal: componentModal,
                        },
                    );

                    // Open the modal
                    modal.show();
                },
            );
        },
    );
}
const DocumentsModalLetterheadRemove = function(doc){

    // Create a modal
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "file-earmark-x",
            title: builder.Locale.get("Are you sure you?"),
            body: builder.Locale.get("Your are about to remove the letterhead. Are you sure you want to continue?"),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // Initialize the updateData
                        var updateData = {letterhead: null};

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/update?id='+doc.id,
                            headers: {'X-CSRF-Authorization': CSRF_KEY},
                            type: 'POST',dataType: 'json',
                            data: updateData,
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
                onHide: function(component,modal){
                    DocumentsModal(doc.id);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Style the modal
            component.addClass('modal-danger');
            component.footer.submit.addClass('btn-danger').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Remove'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-x-lg me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        },
    );
}
const DocumentsModalApprove = function(doc){

    // Create a modal
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "file-earmark-check",
            title: builder.Locale.get("Are you sure you?"),
            body: builder.Locale.get("Your are about to approve this document. This will locked down any futur editing. Are you sure you want to continue?"),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/approve?id='+doc.id,
                            type: 'GET',dataType: 'json',
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
                onHide: function(component,modal){
                    DocumentsModal(doc.id);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Style the modal
            component.addClass('modal-success');
            component.footer.submit.addClass('btn-success').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Approve'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-check-lg me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        },
    );
}
const DocumentsModalDisapprove = function(doc){

    // Create a modal
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "file-earmark-x",
            title: builder.Locale.get("Are you sure you?"),
            body: builder.Locale.get("Your are about to disapprove this document. This will unlocked any futur editing. Are you sure you want to continue?"),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/disapprove?id='+doc.id,
                            type: 'GET',dataType: 'json',
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
                onHide: function(component,modal){
                    DocumentsModal(doc.id);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Style the modal
            component.addClass('modal-danger');
            component.footer.submit.addClass('btn-danger').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Disapprove'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-ban me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        },
    );
}
const DocumentsModalArchive = function(doc, item = null){

    // Create a modal
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "archive",
            title: builder.Locale.get("Are you sure you?"),
            body: builder.Locale.get("Your are about to archive this document. Are you sure you want to continue?"),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/archive?id='+doc.id,
                            type: 'GET',dataType: 'json',
                            success: function(response) {

                                // Remove the item from the list
                                if(item){
                                    item.remove();
                                }

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Style the modal
            component.addClass('modal-dark');
            component.footer.submit.addClass('btn-dark').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Archive'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-archive me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        },
    );
}
const DocumentsModalRecover = function(doc){

    // Create a modal
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "file-earmark-check",
            title: builder.Locale.get("Are you sure you?"),
            body: builder.Locale.get("Your are about to restore this document. Are you sure you want to continue?"),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/recover?id='+doc.id,
                            type: 'GET',dataType: 'json',
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
                onHide: function(component,modal){
                    DocumentsModal(doc.id);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Style the modal
            component.addClass('modal-info');
            component.footer.submit.addClass('btn-info').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Restore'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-arrow-counterclockwise me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        },
    );
}
const DocumentFormat = function(element, doc){

    // Save the document inside the element
    element.doc = doc;

    // Set attributes
    element.attr({
        "data-id": doc.id,
        "data-type": "documents",
    })

    // Update padding
    element.container.addClass('px-3');
    element.field.removeClass('px-1 py-2 ps-2 pe-0').addClass('p-2');

    // Remove the pointer cursor
    element.removeClass('cursor-pointer').css('transition', '0.5s ease-in-out');

    // Setup a grid
    element.field.container = $(document.createElement('div')).addClass('d-flex justify-content-start align-items-center').appendTo(element.field);
    element.field.container.icon = $(document.createElement('div')).addClass('flex-shrink-1 d-flex justify-content-center align-items-center text-bg-primary rounded-circle border border-3 border-light').css({height:"64px",width:"64px"}).appendTo(element.field.container);
    element.field.container.content = $(document.createElement('div')).addClass('flex-grow-1 d-flex flex-column justify-content-center align-items-start ms-3').appendTo(element.field.container);
    element.field.container.content.line1 = $(document.createElement('div')).addClass('text-nowrap my-1').appendTo(element.field.container.content);
    element.field.container.controls = $(document.createElement('div')).addClass('flex-shrink-1 d-flex justify-content-center align-items-center').appendTo(element.field.container);

    // Add the icon
    element.field.icon = $(document.createElement('i')).addClass('fs-3 bi bi-file-earmark-pdf').appendTo(element.field.container.icon);

    // Add the name
    element.field.name = $(document.createElement('span')).addClass('fs-5 fw-lighter').text(doc.filename).appendTo(element.field.container.content.line1);

    // Add additonnal styling to the content area
    element.field.container.addClass('cursor-pointer');

    // on hover Add text-bg-secondary to the item
    element.field.container.hover(
        function(){
            element.addClass('text-bg-secondary');
        },
        function(){
            element.removeClass('text-bg-secondary');
        },
    );

    // Open modal when clicking on the file
    element.field.container.icon.click(function(){
        DocumentsModal(doc.id);
    });
    element.field.container.content.click(function(){
        DocumentsModal(doc.id);
    });

    // Add the actions
    element.field.container.controls.actions = $(document.createElement('div')).addClass('btn-group').appendTo(element.field.container.controls);
    element.field.container.controls.actions.download = $(document.createElement('button')).addClass('btn btn-sm btn-light').html('<i class="bi me-1 bi-download"></i>'+builder.Locale.get("Download")).appendTo(element.field.container.controls.actions);
    element.field.container.controls.actions.download.click(function(){
        window.location.href = '/documents/get?uuid='+doc.uuid+'&download';
    });
    element.field.container.controls.actions.archive = $(document.createElement('button')).addClass('btn btn-sm btn-dark').html('<i class="bi bi-archive"></i>').appendTo(element.field.container.controls.actions);
    element.field.container.controls.actions.archive.click(function(){
        DocumentsModalArchive(doc, element);
    });

    // Remove the icon and the actions container
    setTimeout(() => {
        if(typeof element.container.icon !== 'undefined'){
            element.container.icon.remove();
        }
        if(typeof element.field !== 'undefined'){
            element.field.removeClass('px-1 py-2 ps-2 pe-0').addClass('p-2');
        }
        if(typeof element.actions !== 'undefined'){
            element.actions.remove();
        }
    }, 100);
}
const DocumentsFeed = function(documents, container, defaults = {}, locale = null, callback = null){

    // Initialize the list's tools and actions
    var Tools = {
        add: {
            icon: "plus-lg",
            label: builder.Locale.get("Create a new Document..."),
            color: "success",
            callback: function(tool,list){
                DocumentsModalCreate(list, defaults, locale);
            },
        },
    };
    var Actions = {}

    // Get the keys as numbers, sort them in reverse order
    const sortedKeys = Object.keys(documents).map(Number).sort((a, b) => b - a);

    // Create the list
    builder.Component(
        "list",
        container,
        {
            class: {
                component: "w-100 rounded bg-transparent border-0 shadow-none",
                item: "rounded-top-0",
            },
            tools: Tools,
            actions: Actions,
        },
        function(list,component){

            // Loop through the documents
            for(const [key, id] of Object.entries(sortedKeys)){
                const doc = documents[id];

                // Add the document to the list
                list.add(
                    {},
                    function(item,list){

                        // Format the document
                        DocumentFormat(item, doc);
                    },
                );
            }
        },
    );
}

// Create a document
function process_function_DocumentCreate(task, value, callback = null){

    // Check if the task is attached to an object
    if(typeof task.target === 'undefined'){
        return;
    }

    // Set the document values
    var docvals = {}
    if(typeof task.target.vcard === 'object'){
        docvals.locale = task.target.vcard.locale;
        docvals.name = task.target.vcard.name;
        docvals.title = task.target.vcard.title;
        docvals.role = task.target.vcard.role;
        docvals.address = task.target.vcard.address;
        docvals.city = task.target.vcard.city;
        docvals.state = task.target.vcard.state;
        docvals.zipcode = task.target.vcard.zipcode;
        docvals.country = task.target.vcard.country;
        docvals.phone = task.target.vcard.phone;
        docvals.mobile = task.target.vcard.mobile;
        docvals.tollfree = task.target.vcard.tollfree;
        docvals.fax = task.target.vcard.fax;
        docvals.website = task.target.vcard.website;
        docvals.businessNumber = task.target.vcard.businessNumber;
        docvals.taxExtension = task.target.vcard.taxExtension;
        docvals.importerExtension = task.target.vcard.importerExtension;
    }
    if(typeof task.target.vcard.avatar !== 'undefined' && task.target.vcard.avatar){
        docvals.avatar = task.target.vcard.avatar;
        if(typeof docvals.avatar === 'object'){
            docvals.avatar = docvals.avatar.id;
        }
    }

    // Open the Create Document Modal
    DocumentsModalCreate(null, {
        targetTable: task.targetTable,
        targetId: task.targetId,
        isPublic: 1,
        type: value,
        locale: docvals.locale,
        docvals: docvals,
    }, docvals.locale, function(response){

        // Execute Callback
        if(typeof callback === "function"){
            callback(task, response);
        }
    });
}
function process_meta_DocumentCreate(key = null){
    const metadata = {
        label: "Create a Document",
        description: "Create a Document",
        placeholder: "Select an option",
        type: "select",
        options: [
            {text: "Authorization Letter", id: "AL"},
            {text: "Power of Attorney", id: "POA"},
            {text: "Non-Disclosure Agreement", id: "NDA"},
            {text: "Duty Review Agreement", id: "DRA"},
        ],
    };
    return metadata[key] ? metadata[key] : metadata;
}

// Sign a document
function process_function_DocumentSign(task, value, callback = null){
    // Execute Callback
    if(typeof callback === "function"){
        callback(task);
    }
}
function process_meta_DocumentSign(key = null){
    const metadata = {
        label: "Sign a Document",
        description: "Sign a Document",
        type: "none",
    };
    return metadata[key] ? metadata[key] : metadata;
}

// Send a document
function process_function_DocumentSend(task, value, callback = null){
    // Execute Callback
    if(typeof callback === "function"){
        callback(task);
    }
}
function process_meta_DocumentSend(key = null){
    const metadata = {
        label: "Send a Document",
        description: "Send a Document",
        type: "none",
    };
    return metadata[key] ? metadata[key] : metadata;
}
