<?php

/**
 * Core Framework - DocumentsEndpoint
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Endpoint;

class DocumentsEndpoint extends Endpoint {

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call Parent Constructor
        parent::__construct();

        // Retrieve the namespace
        $namespace = $this->Request->getNamespace();

        // Set Global access
        $this->Public = false;

        // Set Properties
        switch($namespace){
            case "/documents/types":
            case "/documents/get":
                $this->Level = 1;
                break;
            case "/documents/create":
                $this->Level = 2;
                break;
            case "/documents/approve":
            case "/documents/disapprove":
            case "/documents/update":
                $this->Level = 3;
                break;
            case "/documents/archive":
            case "/documents/recovery":
                $this->Level = 4;
                break;
        }
    }

    /**
     * Retrieve Document Types
     */
    public function typesAction(): array
    {
        return ["status" => 200, "message" => "OK", "data" => $this->Model->Documents->types()];
    }

    /**
     * Create a new Document
     */
    public function createAction(): array
    {
        // Import Global Variables
        global $CSRF,$UUID,$LOCALE;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Set Required Fields
                $required = ['type','targetTable','targetId'];

                // Set Optional Fields
                $optional = ['docvals','locale'];

                // Set Unique Fields
                $unique = ['id','created','modified','owner','organization'];

                // Check if all required fields are set
                if(count(array_intersect_key(array_flip($required), $parameters)) == count($required)){

                    // Retrieve the Document Type
                    $documentType = $this->Model->Documents->type(intval($parameters['type']));

                    if(!empty($documentType)){

                        // Setup our document
                        $document = [
                            "owner" => $this->Auth->user()->username,
                            "filename" => $documentType['filename'],
                            "doctype" => $documentType['id'],
                            "title" => $documentType['title'],
                            "subject" => $documentType['subject'],
                            "author" => $this->Auth->user()->vcard('name'),
                            "creator" => $this->Auth->user()->vcard('name'),
                            "keywords" => $documentType['title'],
                            "docvals" => $parameters['docvals'] ?? [],
                            // "letterhead" => null,
                            "watermark" => "DRAFT",
                            "locale" => $parameters['locale'] ?? $LOCALE->current(),
                            // "password" => null,
                            // "isApproved" => null,
                            // "approvedOn" => null,
                            "targetTable" => $parameters['targetTable'],
                            "targetId" => intval($parameters['targetId']),
                            "organization" => $this->Auth->user()->organization()->id,
                        ];

                        // Parse the Document Filename
                        foreach($this->Model->Documents->variables($parameters['docvals']) as $key => $value){
                            if($value){
                                $document['filename'] = str_replace("{{".$key."}}",$value,$document['filename']);
                            } else {
                                $document['filename'] = str_replace("{{".$key."}}",'',$document['filename']);
                            }
                        }

                        // Create a Document
                        $documentId = $this->Model->Documents->create($document);

                        // Retrieve the Document
                        $document = $this->Model->Documents->get($documentId);

                        // Update UUID
                        $affectedRows = $this->Model->Documents->update($documentId, ['uuid' => $UUID->toString($document['id'].$document['created'])]);

                        // Check if the Document was created
                        if($documentId){

                            // Retrieve the Document
                            $message["data"]["record"] = $this->Model->Documents->get($documentId);
                        } else {
                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Failed to Create Document"];
                        }
                    } else {
                        $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Document Type"];
                    }
                } else {
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Missing Required Fields"];
                }
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        return $message;
    }

    /**
     * Update a Document
     */
    public function updateAction(): array
    {
        // Import Global Variables
        global $CSRF;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }

        // Retrieve the Document
        $document = $this->Model->Documents->get(intval($this->Request->getParams('GET','id')));

        // Check if the Document is accessible
        if(empty($document)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested document."];
        } else {
            if($document['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this document."];
            }
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Set Required Fields
                $required = ['title','subject','author','creator','keywords','locale','docvals','letterhead'];

                // Set Unique Fields
                $unique = ['id','created','modified','owner','organization','type','targetTable','targetId'];

                // Check if all required fields are set
                if(count(array_intersect_key(array_flip($required), $parameters)) > 0){

                    // Setup our document
                    $values = [];

                    // Loop through the parameters
                    foreach($parameters as $key => $value){
                        if(!in_array($key,$unique) && in_array($key,$required)){
                            if($key == 'docvals'){
                                $values[$key] = $document['docvals'];
                                foreach($value as $k => $v){
                                    $values[$key][$k] = $v;
                                }
                            } else {
                                $values[$key] = $value;
                            }
                            if(empty($value)){
                                $values[$key] = null;
                            }
                        }
                    }

                    // Update the Document
                    $affectedRows = $this->Model->Documents->update($document['id'], $values);

                    // Retrieve the Updated Document
                    $message["data"]["record"] = $this->Model->Documents->get($document['id']);
                } else {
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Missing Required Fields"];
                }
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        return $message;
    }

    /**
     * Archive a Document
     */
    public function archiveAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the Document
        $document = $this->Model->Documents->get(intval($this->Request->getParams('GET','id')));

        // Check if the Document is accessible
        if(empty($document)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested document."];
        } else {
            if($document['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this document."];
            }
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Update the Document
                $affectedRows = $this->Model->Documents->update($document['id'], ["isArchived" => 1]);

                // Retrieve the Updated Document
                $message["data"]["record"] = $this->Model->Documents->get($document['id']);
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        return $message;
    }

    /**
     * Recover a Document
     */
    public function recoverAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the Document
        $document = $this->Model->Documents->get(intval($this->Request->getParams('GET','id')));

        // Check if the Document is accessible
        if(empty($document)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested document."];
        } else {
            if($document['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this document."];
            }
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Update the Document
                $affectedRows = $this->Model->Documents->update($document['id'], ["isArchived" => 0]);

                // Retrieve the Updated Document
                $message["data"]["record"] = $this->Model->Documents->get($document['id']);
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        return $message;
    }

    /**
     * Approve a Document
     */
    public function approveAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the Document
        $document = $this->Model->Documents->get(intval($this->Request->getParams('GET','id')));

        // Check if the Document is accessible
        if(empty($document)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested document."];
        } else {
            if($document['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this document."];
            }
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Update the Document
                $affectedRows = $this->Model->Documents->update($document['id'], ["isApproved" => 1, "approvedOn" => date("Y-m-d H:i:s"), "watermark" => null]);

                // Retrieve the Updated Document
                $message["data"]["record"] = $this->Model->Documents->get($document['id']);
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        return $message;
    }

    /**
     * Diapprove a Document
     */
    public function disapproveAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the Document
        $document = $this->Model->Documents->get(intval($this->Request->getParams('GET','id')));

        // Check if the Document is accessible
        if(empty($document)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested document."];
        } else {
            if($document['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this document."];
            }
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Update the Document
                $affectedRows = $this->Model->Documents->update($document['id'], ["isApproved" => 0, "approvedOn" => null, "watermark" => "DRAFT"]);

                // Retrieve the Updated Document
                $message["data"]["record"] = $this->Model->Documents->get($document['id']);
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        return $message;
    }

    /**
     * Retrieve Document's Details
     */
    public function getAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the Document
        $document = $this->Model->Documents->get(intval($this->Request->getParams('GET','id')));

        // Check if the Document is accessible
        if(empty($document)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested document."];
        } else {
            if($document['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this document."];
            }
        }

        // Check if the status is 200
        if($message['status'] == 200){
            $message['data']['record'] = $document;
        }

        return $message;
    }
}
