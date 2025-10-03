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

    // Open the Create Modal
    builder.Widget("documents",{render: false,locale: task.target.vcard.locale,targetTable: task.targetTable,targetId: task.targetId,docvals:docvals}).create(value,function(response){

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

