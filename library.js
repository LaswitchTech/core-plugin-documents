builder.add('widgets','documents', class extends builder.ComponentClass {

    _init(){
        this._properties = {
            class: {
                component: null,
            },
            data: {},
            locale: null,
            targetTable: null,
            targetId: null,
            interval: 10000,
            autoStart: false,
            docvals: {},
            callback: {},
        };
        this._documents = {};
        this._selection = [];
        this._counter = 0;
        this._interval = null;
    }

    config(options){

        // Set Self
        const self = this;

        // Execute parent config
        super.config(options);

        // Check if locale is provided
        this._properties.locale = this._properties.locale ?? this._builder.Locale.current();
    }

    _create(){

        // Set Self
        const self = this;

        // Create Component
        this._component = $(document.createElement('div')).attr({
            'id': 'documents' + this._id,
            'class': 'documents-explorer',
        });
        this._component.id = this._component.attr('id');

        // Set Component Class
        if(this._properties.class.component){
            this._component.addClass(this._properties.class.component);
        }

        // Create a controls container
        this._component.controls = $(document.createElement('div')).addClass('documents-controls').prependTo(this._component);

        // Create selection controls
        this._component.controls.selection = $(document.createElement('div')).addClass('selection-controls btn-group me-3').appendTo(this._component.controls);
        this._component.controls.selection.selectAll = $(document.createElement('button')).attr({
            'class': 'btn btn-light',
            'data-action': 'select-all',
            'type': 'button',
        }).html('<i class="bi bi-check-square"></i>').appendTo(this._component.controls.selection);
        this._component.controls.selection.selectAll.click(function(){

            // Check if all documents are selected
            const allSelected = self._component.container.find('.card').length === self._selection.length

            // If all are selected, deselect all
            if(allSelected){
                self._component.container.find('.card.selected').removeClass('selected');
                self._selection = [];
            } else {
                // Otherwise, select all documents
                self._component.container.find('.card:not(.selected)').each(function(){
                    const file = self._documents[$(this).data('id')];
                    file.card.addClass('selected');
                    self._selection.push(file.data.id);
                });
            }
            self._component.controls.selection.count.text(self._selection.length + ' ' + self._builder.Locale.get('selected'));
        });
        this._component.controls.selection.count = $(document.createElement('span')).addClass('btn btn-outline-primary counter disabled text-nowrap').html('0' + ' ' + self._builder.Locale.get('selected')).appendTo(this._component.controls.selection);
        this._component.controls.selection.download = $(document.createElement('button')).attr({
            'class': 'btn btn-primary',
            'data-action': 'download',
            'type': 'button',
        }).html('<i class="bi bi-download"></i>').appendTo(this._component.controls.selection);
        this._component.controls.selection.download.click(function(){
            self.download();
        });
        // this._component.controls.selection.share = $(document.createElement('button')).attr({
        //     'class': 'btn btn-light',
        //     'data-action': 'share',
        //     'type': 'button',
        // }).html('<i class="bi bi-share"></i>').appendTo(this._component.controls.selection);
        this._component.controls.selection.archive = $(document.createElement('button')).attr({
            'class': 'btn btn-dark',
            'data-action': 'archive',
            'type': 'button',
        }).html('<i class="bi bi-archive"></i>').appendTo(this._component.controls.selection);
        this._component.controls.selection.archive.click(function(){
            self.archive();
        });

        // Create view controls
        this._component.controls.group = $(document.createElement('div')).addClass('btn-group').appendTo(this._component.controls);
        this._component.controls.group.create = $(document.createElement('button')).attr({
            'class': 'btn btn-success',
            'data-action': 'create',
            'type': 'button',
        }).html('<i class="bi bi-plus-lg"></i>').appendTo(this._component.controls.group);
        this._component.controls.group.grid = $(document.createElement('button')).attr({
            'class': 'btn btn-outline-secondary',
            'data-action': 'grid',
            'type': 'button',
        }).html('<i class="bi bi-grid-3x3-gap"></i>').appendTo(this._component.controls.group);
        this._component.controls.group.list = $(document.createElement('button')).attr({
            'class': 'btn btn-outline-secondary',
            'data-action': 'list',
            'type': 'button',
        }).html('<i class="bi bi-list"></i>').appendTo(this._component.controls.group);

        // Create a search container
        this._component.search = $(document.createElement('input')).attr({
            'class': 'form-control',
            'type': 'search',
            'placeholder': this._builder.Locale.get('Search...'),
        }).prependTo(this._component.controls);
        this._component.search.on('input', function(){
            const search = this.value.toLowerCase();
            self._component.container.children('.col').each(function(){
                const content = $(this).text().toLowerCase();
                if(content.includes(search)){
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Create a container for the documents
        this._component.container = $(document.createElement('div')).addClass('documents-container').appendTo(this._component);
        this._component.container.on('click', '.controls, .controls *', function (e) {
            e.stopPropagation();
        });

        // Load the state
        this.loadState();

        // Add Event Listeners
        this._component.controls.group.create.click(function(){
            self.create();
        });
        this._component.controls.group.grid.click(function(){

            // Set the grid view
            self._component.container.removeClass('list-view').addClass('grid-view');
            self._component.controls.group.grid.addClass('active');
            self._component.controls.group.list.removeClass('active');

            // Save the state
            self.saveState();
        });
        this._component.controls.group.list.click(function(){

            // Set the list view
            self._component.container.removeClass('grid-view').addClass('list-view');
            self._component.controls.group.list.addClass('active');
            self._component.controls.group.grid.removeClass('active');

            // Save the state
            self.saveState();
        });

        // Add existing Contacts
        for(const [key, record] of Object.entries(this._properties.data ?? {})){
            this.add(record);
        }

        // Check if autoStart is enabled
        if(this._properties.autoStart){

            // Start the interval to check for changes
            setTimeout(function(){
                self.start();
            }, this._properties.interval);
        }
    }

    stateKey() {

        // include origin, path and query so /page?a=1 and /page?a=2 don't clash
        const url = location.origin + location.pathname + location.search;
        return `documents.state::${url}::${this._component.id}`;
    }

    clearState() {

        // Remove persisted state
        localStorage.removeItem(this.stateKey());

        // Reset the view mode
        this._component.container.removeClass('list-view').addClass('grid-view');
    }

    saveState() {

        // Save the current view mode
        const state = {
            view: this._component.container.hasClass('list-view') ? 'list' : 'grid',
        };

        // Persist the state
        localStorage.setItem(this.stateKey(), JSON.stringify(state));
    }

    loadState() {

        // Check for persisted state
        const state = localStorage.getItem(this.stateKey());
        if(state){
            try {
                const parsedState = JSON.parse(state);
                if(parsedState.view === 'list'){
                    // Set the list view
                    this._component.container.removeClass('grid-view').addClass('list-view');
                    this._component.controls.group.list.addClass('active');
                    this._component.controls.group.grid.removeClass('active');
                } else {
                    // Set the grid view
                    this._component.container.removeClass('list-view').addClass('grid-view');
                    this._component.controls.group.grid.addClass('active');
                    this._component.controls.group.list.removeClass('active');
                }
            } catch (e) {
                // If parsing fails, default to grid view
                this._component.container.removeClass('list-view').addClass('grid-view');
                this._component.controls.group.grid.addClass('active');
                this._component.controls.group.list.removeClass('active');
            }
        } else {
            // Default to grid view if no state is found
            this._component.container.removeClass('list-view').addClass('grid-view');
            this._component.controls.group.grid.addClass('active');
            this._component.controls.group.list.removeClass('active');
        }
    }

    load(records = null){

        // Set Self
        const self = this;

        // Check if records are provided
        if(records !== null && Object.entries(records).length > 0){

            // Loop through the records
            for(const [key, record] of Object.entries(records)){
                self.add(record);
            }
            return this;
        }

        // Retrieve Notes
        $.ajax({
            url: '/api/documents/fetchAll',
            headers: {'X-CSRF-Authorization': CSRF_KEY},
            type: 'POST',dataType: 'json',
            data: {
                conditions: [
                    {key: 'targetTable', operator: '=', value: this._properties.targetTable},
                    {key: 'targetId', operator: '=', value: this._properties.targetId},
                    {key: 'isArchived', operator: '<>', value: 1},
                ]
            },
            error: function(xhr, status, error) {
                console.error('Error fetching data:', error);
                reject(error);
            },
            success: function(response) {

                // Add Records
                for(const [key, record] of Object.entries(response.records)){
                    self.add(record);
                }
            }
        });

        return this;
    }

    start(){
        // Set Self
        const self = this;

        // Check if the interval is already set
        if(this._interval){
            console.warn('Interval is already set, stopping the previous one.');
            clearInterval(this._interval);
        }

        // Set the interval to check for changes
        this._interval = setInterval(function(){
            self.load();
        }, this._properties.interval);
    }

    stop(){
        // Check if the interval is set
        if(this._interval){
            clearInterval(this._interval);
            this._interval = null;
        } else {
            console.warn('No interval is currently set.');
        }
    }

    add(record, param1 = null, param2 = null){

        // Set Self
        const self = this;

        let options = {};
        let callback = null;

        // Set selector, options, and callback
        [param1, param2].forEach(param => {
            if(param !== null){
                if (typeof param === 'object') {
                    options = param;
                } else if (typeof param === 'function') {
                    callback = param;
                }
            }
        });

        let properties = {
            class: {},
            callback: {},
        };

        // Configure Options
        for(const [key, value] of Object.entries(options)){
            if(typeof properties[key] !== 'undefined'){
                switch(key){
                    case"callback":
                        if(typeof properties[key] !== 'undefined'){
                            for(const [k, v] of Object.entries(value)){
                                if(typeof properties[key][k] !== 'undefined'){
                                    properties[key][k] = v;
                                }
                            }
                        }
                        break;
                    case"class":
                        for(const [section, classes] of Object.entries(value)){
                            if(properties[key][section] != null){
                                properties[key][section] += ' ' + classes;
                            } else {
                                properties[key][section] = classes;
                            }
                        }
                        break;
                    default:
                        properties[key] = value;
                        break;
                }
            }
        }

        // Check if the file already exists
        if(this._documents[record.id ?? (this._counter + 1)]){
            this.edit(record.id ?? (this._counter + 1), record);
            return this;
        }

        // Increment Post Count
        this._counter++;

        // Set ID
        const count = record.id ?? this._counter;
        const id = this._component.id + 'document' + count;

        // Create Column
        let file = $(document.createElement('div')).attr({
            'id':id,
            'class':'col',
            'data-type':'document',
        }).appendTo(this._component.container);
        file.id = file.attr('id');
        file.data = record;

        // Create Card
        file.card = $(document.createElement('div')).attr({
            'class': 'card h-100 card-hover',
            'data-id': count,
        }).appendTo(file);
        file.card.body = $(document.createElement('div')).addClass('card-body').appendTo(file.card);

        // Add vCard information
        file.card.body.info = $(document.createElement('div')).addClass('d-flex align-items-center gap-3').appendTo(file.card.body);
        file.card.body.info.icon = $(document.createElement('div')).addClass('document-icon').appendTo(file.card.body.info);
        file.card.body.info.icon.i = $(document.createElement('i')).addClass('bi bi-file-earmark-pdf text-red').appendTo(file.card.body.info.icon);
        file.card.body.info.container = $(document.createElement('div')).addClass('document-info flex-grow-1').appendTo(file.card.body.info);
        file.card.body.info.container.name = $(document.createElement('div')).addClass('document-name d-flex align-items-center gap-2 flex-wrap').text(record.filename).appendTo(file.card.body.info.container);
        file.card.body.info.container.metadata = $(document.createElement('div')).addClass('small text-secondary').appendTo(file.card.body.info.container);
        file.card.body.info.container.metadata.draft = $(document.createElement('span')).addClass('document-draft').text(self._builder.Locale.get((record.isApproved ? 'Approved' : 'DRAFT'))).appendTo(file.card.body.info.container.metadata);
        file.card.body.info.container.metadata.date = $(document.createElement('span')).addClass('document-date').text(new Date(record.modified ?? Date.now()).toLocaleDateString()).appendTo(file.card.body.info.container.metadata);

        // Add click event to the card
        file.card.body.click(function(e){
            if(file.card.hasClass('selected')){
                file.card.removeClass('selected');
                self._selection = self._selection.filter(f => f !== file.data.id);
            } else {
                file.card.addClass('selected');
                self._selection.push(file.data.id);
            }
            self._component.controls.selection.count.text(self._selection.length + ' ' + self._builder.Locale.get('selected'));
        });

        // Add Control - Edit
        file.card.edit = $(document.createElement('button')).attr({
            'class': 'btn btn-light',
            'data-action': 'edit',
            'type': 'button',
        }).html('<i class="bi bi-pencil"></i>').appendTo(file.card);
        file.card.edit.hover(function(){
            $(this).removeClass('btn-light').addClass('btn-warning');
        }, function(){
            $(this).removeClass('btn-warning').addClass('btn-light');
        }).click(function(){
            self.edit(count);
        });

        // Save the object
        this._documents[count] = file;

        // return the instance
        return this;
    }

    edit(id, record = null){

        // Set Self
        const self = this;

        // Check if the document exists
        if(!this._documents[id]){
            console.error('Document with ID ' + id + ' does not exist.');
            return;
        }

        // Update the document
        function update(doc, record){

            // Update the document data
            doc.data = record;

            // Update the card body info
            doc.card.body.info.container.name.text(record.filename);
            doc.card.body.info.container.metadata.draft.text(self._builder.Locale.get((record.isApproved ? 'Approved' : 'DRAFT')));
            doc.card.body.info.container.metadata.date.text(new Date(record.modified ?? Date.now()).toLocaleDateString());

            // Return the updated document instance
            return doc;
        }

        // Check if record is provided
        if(record){
            this._documents[id] = update(this._documents[id], record);
        } else {

            // Create the Modal
            this._builder.Component(
                "modal",
                {
                    icon: "file-earmark-richtext",
                    title: this._builder.Locale.get("Document"),
                    color: 'warning',
                    size: 'xl',
                    submit: false,
                    cancel: false,
                    callback: {
                        load: function(component, modal){
                            return new Promise((resolve, reject) => {
                                try {

                                    // Set the parent
                                    const parent = component.dialog;

                                    // AJAX Request
                                    $.ajax({
                                        url: '/api/documents/fetch?id=' + id,
                                        headers: {'X-CSRF-Authorization': CSRF_KEY},
                                        type: 'GET',dataType: 'json',
                                        error: function(xhr, status, error) {
                                            console.error('Error fetching document:', error);
                                            reject(new Error(self._builder.Locale.get('Failed to fetch document')));
                                        },
                                        success: function(response) {

                                            // Set the record
                                            const documentRecord = response.record;

                                            // Add the Preview
                                            component.body.viewer = self._builder.Component(
                                                "pdfviewer",
                                                component.body,
                                                {
                                                    filename: documentRecord.filename,
                                                    url:"/documents/get?uuid="+documentRecord.uuid,
                                                    scale: 1,
                                                    password: documentRecord.doctype.uuid,
                                                    pageNum: 1,
                                                    verticalScroll: true,
                                                    renderText: false,
                                                    smallToolbar: true,
                                                },
                                                function(viewer,component){

                                                    // Styling
                                                    component.removeClass('rounded').addClass('rounded-bottom');
                                                    component.toolbar.removeClass('rounded-top');

                                                    // Add action button Archive
                                                    if(documentRecord.isArchived == 0){
                                                        component.toolbar.actions.archive = $(document.createElement('button')).attr({
                                                            "type": "button",
                                                            "class": "btn btn-dark",
                                                        }).prependTo(component.toolbar.actions);
                                                        component.toolbar.actions.archive.icon = $(document.createElement('i')).addClass('bi bi-archive').prependTo(component.toolbar.actions.archive);
                                                        component.toolbar.actions.archive.click(function(){

                                                            // Close the modal
                                                            modal.hide();

                                                            // Open the archive
                                                            self.archive(documentRecord.id);
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

                                                            // Open the archive
                                                            self.archive(documentRecord.id);
                                                        });
                                                    }

                                                    // Add action button Approve
                                                    if(documentRecord.isApproved == 0){
                                                        component.toolbar.actions.approve = $(document.createElement('button')).attr({
                                                            "type": "button",
                                                            "class": "btn btn-success",
                                                        }).text(builder.Locale.get('Approve')).prependTo(component.toolbar.actions);
                                                        component.toolbar.actions.approve.icon = $(document.createElement('i')).addClass('me-1 bi bi-check2').prependTo(component.toolbar.actions.approve);
                                                        component.toolbar.actions.approve.click(function(){

                                                            // Close the modal
                                                            modal.hide();

                                                            // Open the approve
                                                            self.approve(documentRecord.id, true);
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

                                                            // Open the approve
                                                            self.disapprove(documentRecord.id, true);
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
                                                    if(documentRecord.isApproved == 0){
                                                        component.toolbar.actions.docvals = $(document.createElement('button')).attr({
                                                            "type": "button",
                                                            "class": "btn btn-warning",
                                                        }).text(builder.Locale.get('Variables')).prependTo(component.toolbar.actions);
                                                        component.toolbar.actions.docvals.icon = $(document.createElement('i')).addClass('me-1 bi bi-list-check').prependTo(component.toolbar.actions.docvals);
                                                        component.toolbar.actions.docvals.click(function(){

                                                            // Close the modal
                                                            modal.hide();

                                                            // Open the setVariables
                                                            self.setVariables(documentRecord.id, true);
                                                        });
                                                    }

                                                    // Add action button Letterhead
                                                    if(documentRecord.doctype.hasLetterhead == 1 && documentRecord.isApproved == 0){
                                                        if(documentRecord.letterhead.id == null){
                                                            component.toolbar.actions.letterhead = $(document.createElement('button')).attr({
                                                                "type": "button",
                                                                "class": "btn btn-primary",
                                                            }).text(builder.Locale.get('Letterhead')).prependTo(component.toolbar.actions);
                                                            component.toolbar.actions.letterhead.icon = $(document.createElement('i')).addClass('me-1 bi bi-file-earmark-arrow-up').prependTo(component.toolbar.actions.letterhead);
                                                            component.toolbar.actions.letterhead.click(function(){

                                                                // Close the modal
                                                                modal.hide();

                                                                // Open the addLetterhead
                                                                self.addLetterhead(documentRecord.id, true);
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

                                                                // Open the addLetterhead
                                                                self.removeLetterhead(documentRecord.id, true);
                                                            });
                                                        }
                                                    }

                                                    // Resolve the promise
                                                    resolve();
                                                },
                                            );
                                        },
                                    });
                                } catch (error) {
                                    console.error('Error in document edit modal:', error);
                                    reject(error);
                                }
                            });
                        },
                    },
                },
                function(modal,component){

                    // Styling
                    component.body.addClass('p-0');

                    // Show the modal
                    modal.show();
                },
            );
        }

        // Return this instance
        return this;
    }

    create(doctype = null){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "plus-lg",
                title: this._builder.Locale.get("Create Document"),
                color: 'success',
                callback: {
                    load: function(component, modal){
                        return new Promise((resolve, reject) => {
                            try {

                                // Set the parent
                                const parent = component.dialog;

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
                                    error: function(xhr, status, error) {
                                        console.error('Error fetching document types:', error);
                                        reject(new Error(self._builder.Locale.get('Failed to fetch document types')));
                                    },
                                    success: function(response) {

                                        // Generate document types
                                        var types = [];
                                        var value = null;
                                        for(const [key, type] of Object.entries(response.records)){
                                            if(self._properties.locale === type.locale){
                                                types.push({id: type.id,text: type.title});
                                                if(type.name == doctype){
                                                    value = type.id;
                                                }
                                            }
                                        }

                                        // Create the Form
                                        self._builder.Utility(
                                            'form',
                                            component.body,
                                            {
                                                callback: {
                                                    val: function(values){
                                                        // Set the default values
                                                        values.targetTable = self._properties.targetTable;
                                                        values.targetId = self._properties.targetId;
                                                        values.locale = self._properties.locale;
                                                        values.docvals = self._properties.docvals;
                                                        return values;
                                                    },
                                                    submit: function(form){

                                                        // Show the modal spinner
                                                        modal.spinner(true);

                                                        // AJAX Request
                                                        $.ajax({
                                                            url: '/api/documents/create',
                                                            headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                            type: 'POST',dataType: 'json',
                                                            data: form.val(),
                                                            error: function(xhr, status, error) {
                                                                console.error('Error creating document:', error);
                                                            },
                                                            success: function(response) {

                                                                // Add the new document to the explorer
                                                                self.add(response.record);

                                                                // Hide the modal
                                                                modal.hide();
                                                            }
                                                        });
                                                    },
                                                }
                                            },
                                            function(form,component){

                                                // Add event listener on the modal submit button
                                                parent.content.footer.submit.click(function(e){
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    form.submit();
                                                });

                                                // type
                                                form.add(
                                                    'select2',
                                                    {
                                                        name: 'type',
                                                        label: self._builder.Locale.get('Type'),
                                                        placeholder: self._builder.Locale.get('Select a type'),
                                                        options: types,
                                                        value: value,
                                                        required: true,
                                                        class: {
                                                            component: 'bg-gray-200 p-3 py-2 rounded-0',
                                                        },
                                                    }
                                                );

                                                // Resolve the promise
                                                resolve();
                                            },
                                        );
                                    }
                                });
                            } catch (error) {
                                console.error('Error in document create modal:', error);
                                reject(error);
                            }
                        });
                    },
                },
            },
            function(modal,component){

                // Styling
                component.body.addClass('p-0');

                // Show the modal
                modal.show();
            },
        );
    }

    archive(documentId = null){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "archive",
                title: this._builder.Locale.get("Are you sure?"),
                body: this._builder.Locale.get("You are about to archive the selected document(s). Are you sure you want to continue?"),
                color: 'dark',
                callback: {
                    submit: function(element,modal){

                        // Show the modal spinner
                        modal.spinner(true);

                        // Loop through the selected documents
                        for(const [key, id] of Object.entries((documentId !== null) ? [documentId] : self._selection)){
                            const file = self._documents[id];

                            // AJAX Request - Archive the document
                            $.ajax({
                                url: '/api/documents/archive?id='+id,
                                type: 'GET',dataType: 'json',
                                error: function(xhr, status, error) {
                                    console.error('Error updating document:', error);
                                },
                                success: function(response) {

                                    // Remove the document
                                    file.remove();
                                    delete self._documents[id];

                                    // Remove from the selection
                                    self._selection = self._selection.filter(f => f !== id);

                                    // Update the counter
                                    self._component.controls.selection.count.text(self._selection.length + ' ' + self._builder.Locale.get('selected'));

                                    // Check if the selection is empty
                                    if(documentId === null || self._selection.length === 0){

                                        // Close the modal
                                        modal.hide();
                                    }
                                }
                            });
                        }
                    },
                },
            },
            function(modal,component){

                // Show the modal
                modal.show();
            },
        );
    }

    download(){

        // Set Self
        const self = this;

        // Loop through the selected documents
        for(const [key, id] of Object.entries(this._selection)){
            const file = this._documents[id];

            // Create a hidden link element
            const link = document.createElement('a');
            link.href = '/documents/get?uuid='+file.data.uuid+'&download';
            link.download = file.data.name;
            document.body.appendChild(link);

            // Programmatically click the link to trigger the download
            link.click();

            // Remove the link from the document
            document.body.removeChild(link);
        }
    }

    approve(documentId, open = false){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "file-earmark-check",
                title: this._builder.Locale.get("Are you sure?"),
                body: this._builder.Locale.get("You are about to approve this document. This will locked down any futur editing. Are you sure you want to continue?"),
                color: 'success',
                callback: {
                    onHide: function(component,modal){

                        // Check if the document is open
                        if(open){

                            // Open the document
                            self.edit(documentId);
                        }
                    },
                    submit: function(element,modal){

                        // Show the modal spinner
                        modal.spinner(true);

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/approve?id='+documentId,
                            type: 'GET',dataType: 'json',
                            error: function(xhr, status, error) {
                                console.error('Error updating document:', error);
                            },
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    },
                },
            },
            function(modal,component){

                // Show the modal
                modal.show();
            },
        );
    }

    disapprove(documentId, open = false){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "file-earmark-x",
                title: this._builder.Locale.get("Are you sure?"),
                body: this._builder.Locale.get("You are about to disapprove this document. This will unlocked any futur editing. Are you sure you want to continue?"),
                color: 'danger',
                callback: {
                    onHide: function(component,modal){

                        // Check if the document is open
                        if(open){

                            // Open the document
                            self.edit(documentId);
                        }
                    },
                    submit: function(element,modal){

                        // Show the modal spinner
                        modal.spinner(true);

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/disapprove?id='+documentId,
                            type: 'GET',dataType: 'json',
                            error: function(xhr, status, error) {
                                console.error('Error updating document:', error);
                            },
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    },
                },
            },
            function(modal,component){

                // Show the modal
                modal.show();
            },
        );
    }

    addLetterhead(documentId, open = false){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "file-earmark-arrow-up",
                title: this._builder.Locale.get("Upload a Letterhead"),
                color: 'primary',
                callback: {
                    onHide: function(component,modal){

                        // Check if the document is open
                        if(open){

                            // Open the document
                            self.edit(documentId);
                        }
                    },
                },
            },
            function(modal,component){

                // Set the parent
                const parent = component.dialog;

                // Styling
                component.body.addClass('p-0');

                // Create the Form
                self._builder.Utility(
                    'form',
                    component.body,
                    {
                        callback: {
                            submit: function(form){

                                // Show the modal spinner
                                modal.spinner(true);

                                // Run the file promise
                                form.val().file.then(fileData => {

                                    // Loop through the files
                                    for(const [id, file] of Object.entries(fileData)){

                                        // Add some properties
                                        file.isPublic = 1;
                                        file.targetTable = self._properties.targetTable;
                                        file.targetId = self._properties.targetId;

                                        // Generate a md5 checksum
                                        self._builder.Helper.md5(file.content.split(',')[1],function(checksum){

                                            // Save the checksum
                                            file.checksum = checksum;

                                            // AJAX Request
                                            $.ajax({
                                                url: '/api/files/upload',
                                                headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                type: 'POST',dataType: 'json',
                                                data: file,
                                                error: function(xhr, status, error) {
                                                    console.error('Error uploading letterhead file:', error);
                                                },
                                                success: function(response) {

                                                    // AJAX Request
                                                    $.ajax({
                                                        url: '/api/documents/update?id='+documentId,
                                                        headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                        type: 'POST',dataType: 'json',
                                                        data: {letterhead: response.record.id},
                                                        error: function(xhr, status, error) {
                                                            console.error('Error updating document with letterhead:', error);
                                                        },
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
                        }
                    },
                    function(form,component){

                        // Add event listener on the modal submit button
                        parent.content.footer.submit.click(function(e){
                            e.preventDefault();
                            e.stopPropagation();
                            form.submit();
                        });

                        // Upload
                        form.add(
                            'file',
                            {
                                name: 'file',
                                placeholder: self._builder.Locale.get('Select file'),
                                multiple: true,
                                class: {
                                    component: 'bg-gray-200 p-3 py-2 rounded-0',
                                },
                            }
                        );

                        // Show the modal
                        modal.show();
                    },
                );
            },
        );
    }

    removeLetterhead(documentId, open = false){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "file-earmark-x",
                title: this._builder.Locale.get("Are you sure?"),
                body: this._builder.Locale.get("You are about to remove the letterhead. Are you sure you want to continue?"),
                color: 'danger',
                callback: {
                    onHide: function(component,modal){

                        // Check if the document is open
                        if(open){

                            // Open the document
                            self.edit(documentId);
                        }
                    },
                    submit: function(element,modal){

                        // Show the modal spinner
                        modal.spinner(true);

                        // AJAX Request
                        $.ajax({
                            url: '/api/documents/update?id='+documentId,
                            headers: {'X-CSRF-Authorization': CSRF_KEY},
                            type: 'POST',dataType: 'json',
                            data: {letterhead: null},
                            error: function(xhr, status, error) {
                                console.error('Error updating document:', error);
                            },
                            success: function(response) {

                                // Hide the modal
                                modal.hide();
                            }
                        });
                    },
                },
            },
            function(modal,component){

                // Show the modal
                modal.show();
            },
        );
    }

    setVariables(documentId, open = false){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "input-cursor-text",
                title: this._builder.Locale.get("Document Variables"),
                color: 'warning',
                size: 'lg',
                callback: {
                    onHide: function(component,modal){

                        // Check if the document is open
                        if(open){

                            // Open the document
                            self.edit(documentId);
                        }
                    },
                    load: function(component, modal){
                        return new Promise((resolve, reject) => {
                            try {

                                // Set the parent
                                const parent = component.dialog;

                                // Retrieve the libraries
                                $.ajax({
                                    url: '/api/library/fetch',
                                    type: 'GET',dataType: 'json',
                                    error: function(xhr, status, error) {
                                        console.error('Error fetching libraries:', error);
                                        reject(new Error(self._builder.Locale.get('Failed to fetch libraries')));
                                    },
                                    success: function(library) {

                                        // AJAX Request
                                        $.ajax({
                                            url: '/api/documents/fetch?id=' + documentId,
                                            headers: {'X-CSRF-Authorization': CSRF_KEY},
                                            type: 'GET',dataType: 'json',
                                            error: function(xhr, status, error) {
                                                console.error('Error fetching document:', error);
                                                reject(new Error(self._builder.Locale.get('Failed to fetch document')));
                                            },
                                            success: function(response) {

                                                // Set the record
                                                const documentRecord = response.record;
                                                var contacts = null;

                                                // Create the Form
                                                self._builder.Utility(
                                                    'form',
                                                    component.body,
                                                    {
                                                        class: {
                                                            component: 'row row-cols-1 row-cols-md-2 g-2',
                                                        },
                                                        callback: {
                                                            val: function(values){
                                                                for(const [key, value] of Object.entries(documentRecord.docvals)){
                                                                    if(typeof values[key] === 'undefined'){
                                                                        values[key] = value;
                                                                    }
                                                                }
                                                                if(typeof values.contact !== 'undefined' && contacts[values.contact]){
                                                                    values['contact'] = contacts[values.contact].vcard;
                                                                }
                                                                return {docvals: values};
                                                            },
                                                            submit: function(form){

                                                                // Show the modal spinner
                                                                modal.spinner(true);

                                                                // AJAX Request
                                                                $.ajax({
                                                                    url: '/api/documents/update?id='+documentRecord.id,
                                                                    headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                                    type: 'POST',dataType: 'json',
                                                                    data: form.val(),
                                                                    error: function(xhr, status, error) {
                                                                        console.error('Error updating document:', error);
                                                                    },
                                                                    success: function(response) {

                                                                        // Hide the modal
                                                                        modal.hide();
                                                                    }
                                                                });
                                                            },
                                                        }
                                                    },
                                                    function(form,component){

                                                        // Add event listener on the modal submit button
                                                        parent.content.footer.submit.click(function(e){
                                                            e.preventDefault();
                                                            e.stopPropagation();
                                                            form.submit();
                                                        });

                                                        // Loop through the variables
                                                        for(const [key, variable] of Object.entries(documentRecord.doctype.template.variables)){

                                                            const name = variable.replace(/{{/g, '').replace(/}}/g, '');
                                                            const label = builder.Helper.ucwords(variable.replace(/{{/g, '').replace(/}}/g, '').replace(/\./g, ' '));
                                                            const type = self._fieldType(name.split('.')[1] ?? name);

                                                            // Check if the variable is in the documentRecord.doctype.locked array
                                                            if(!documentRecord.doctype.locked.includes(name)){

                                                                // Generate field types
                                                                switch(name.split('.')[0] ?? name){
                                                                    case 'contact':
                                                                        if(contacts === null){
                                                                            contacts = {};
                                                                            form.add(
                                                                                'select2',
                                                                                {
                                                                                    name: name.split('.')[0] ?? name,
                                                                                    label: self._builder.Locale.get(name.split('.')[0] ?? name),
                                                                                    placeholder: self._builder.Locale.get('Select '+(name.split('.')[0] ?? name).toLowerCase()),
                                                                                    options: [],
                                                                                    value: documentRecord.docvals[name] ?? '',
                                                                                    class: {
                                                                                        component: 'col',
                                                                                    },
                                                                                },
                                                                                function(input){

                                                                                    // Retrieve records
                                                                                    $.ajax({
                                                                                        url: '/api/contacts/fetchAll',
                                                                                        headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                                                        type: 'POST',dataType: 'json',
                                                                                        data: {
                                                                                            conditions: [
                                                                                                {key: 'targetTable', operator: '=', value: self._properties.targetTable},
                                                                                                {key: 'targetId', operator: '=', value: self._properties.targetId},
                                                                                                {key: 'isArchived', operator: '<>', value: 1},
                                                                                            ]
                                                                                        },
                                                                                        error: function(xhr, status, error) {
                                                                                            console.error('Error fetching data:', error);
                                                                                        },
                                                                                        success: function(response) {

                                                                                            // Add Records
                                                                                            for(const [key, record] of Object.entries(response.records)){
                                                                                                contacts[key] = record;
                                                                                                if(record.vcard.role.includes('Signatory')){
                                                                                                    input.add(key, record.vcard.name + (record.vcard.organization ? ' ('+record.vcard.organization+')' : '') + ' <' + (record.vcard.email ? record.vcard.email : self._builder.Locale.get('no email')) + '>' + (record.vcard.title ? ' - '+record.vcard.title : ''));
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    });
                                                                                }
                                                                            );
                                                                        }
                                                                        break;
                                                                    default:
                                                                        switch(name.split('.')[1] ?? name){
                                                                            default:
                                                                                form.add(
                                                                                    type,
                                                                                    {
                                                                                        name: name,
                                                                                        label: self._builder.Locale.get(label),
                                                                                        placeholder: self._builder.Locale.get('Enter '+(name.split('.')[1] ?? name).toLowerCase()),
                                                                                        options: library.options[(name.split('.')[1] ?? name)] ?? [],
                                                                                        value: documentRecord.docvals[name] ?? '',
                                                                                        class: {
                                                                                            component: 'col',
                                                                                        },
                                                                                    }
                                                                                );
                                                                                break;
                                                                        }
                                                                        break;
                                                                }
                                                            }
                                                        }

                                                        // Resolve the promise
                                                        resolve();
                                                    },
                                                );
                                            },
                                        });
                                    },
                                });
                            } catch (error) {
                                console.error('Error in document edit modal:', error);
                                reject(error);
                            }
                        });
                    },
                },
            },
            function(modal,component){

                // Styling
                component.body.addClass('bg-gray-200 p-3 py-2');

                // Show the modal
                modal.show();
            },
        );
    }

    _fieldType(name){
        switch(name){
            case 'phone':
            case 'date':
            case 'email':
            case 'zipcode':
            case 'locale':
            case 'businessNumber':
            case 'taxExtension':
            case 'importerExtension': return name;
            case 'mobile':
            case 'tollfree':
            case 'fax': return 'phone';
            case 'state':
            case 'country': return 'select2';
            case 'until':
            case 'from': return 'date';
            case 'rate': return 'number';
            default: return 'text';
        }
    }
});
