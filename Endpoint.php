<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Base\BaseEndpoint;

class DocumentsEndpoint extends BaseEndpoint {

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Initialize the Endpoint
        $this->init('documents');

        // Set Properties
        $this->required = ['type','targetTable','targetId'];
        $this->optional = ['docvals','locale'];

        // Set the Level
        switch($this->Request->getNamespace()){
            case "/documents/approve":
            case "/documents/disapprove":
                $this->Level = 3;
                break;
        }
    }

    /**
     * Create a record
     */
    public function createAction(): array
    {
        // Import Global Classes
        global $UUID;

        // Call the parent constructor
        $message = parent::createAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Retrieve the parameters
            $parameters = $message['data']['parameters'];

            // Initialize the fields array
            $fields = [];

            // Retrieve the Document Type
            $doctype = $this->Model->Doctypes->fetch(intval($parameters['type']));

            if(!empty($doctype)){

                // Setup our document
                $fields['filename'] = $doctype['filename'];
                $fields['doctype'] = $doctype['id'];
                $fields['title'] = $doctype['title'];
                $fields['subject'] = $doctype['subject'];
                $fields['author'] = $this->Auth->user()->vcard('name');
                $fields['creator'] = $this->Auth->user()->vcard('name');
                $fields['keywords'] = $doctype['title'];
                $fields['docvals'] = $parameters['docvals'];
                $fields['watermark'] = "DRAFT";
                $fields['uuid'] = $UUID->toString($message['data']['record']['id'].$message['data']['record']['created']);

                // Parse the Document Filename
                $fields['filename'] = $this->Helper->Documents->replace($fields['filename'], $this->Model->Documents->variables($fields['docvals']));

                // Check if the Event Plugin is accessible
                if($this->Helper->Core->isInstalled('event')){

                    // Initialize the Events
                    $message['data']['event'] = [];

                    // Setup a new event
                    $event = [
                        'category' => 'Document',
                        'message' => 'New Document Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/'.$message['data']['record']['targetTable'].'/details?id='.$message['data']['record']['targetId'],
                        'targetTable' => $message['data']['record']['targetTable'],
                        'targetId' => $message['data']['record']['targetId'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }

                // Check if $fields is empty
                if(!empty($fields)){
                    $affectedRows = $this->Model->{$this->name}->update($message['data']['record']['id'], $fields);

                    // Check if we send out the notification
                    if($affectedRows){

                        // Retrieve the updated record
                        $message['data']['record'] = $this->Model->{$this->name}->fetch($message['data']['record']['id']);
                    }
                }
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Document Type"];
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Update a record
     */
    public function updateAction(): array
    {
        // Call the parent constructor
        $message = parent::updateAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Document',
                    'message' => 'Document Updated by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/'.$message['data']['record']['targetTable'].'/details?id='.$message['data']['record']['targetId'],
                    'targetTable' => $message['data']['record']['targetTable'],
                    'targetId' => $message['data']['record']['targetId'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Delete a record
     */
    public function deleteAction(): array
    {
        // Call the parent constructor
        $message = parent::deleteAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Document',
                    'message' => 'Document Deleted by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/'.$message['data']['record']['targetTable'].'/details?id='.$message['data']['record']['targetId'],
                    'targetTable' => $message['data']['record']['targetTable'],
                    'targetId' => $message['data']['record']['targetId'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Archive a record
     */
    public function archiveAction(): array
    {
        // Call the parent constructor
        $message = parent::archiveAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Document',
                    'message' => 'Document Archived by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/'.$message['data']['record']['targetTable'].'/details?id='.$message['data']['record']['targetId'],
                    'targetTable' => $message['data']['record']['targetTable'],
                    'targetId' => $message['data']['record']['targetId'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Recover a record
     */
    public function recoverAction(): array
    {
        // Call the parent constructor
        $message = parent::recoverAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Document',
                    'message' => 'Document Recovered by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/'.$message['data']['record']['targetTable'].'/details?id='.$message['data']['record']['targetId'],
                    'targetTable' => $message['data']['record']['targetTable'],
                    'targetId' => $message['data']['record']['targetId'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Approve a document
     */
    public function approveAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the record
        $record = $this->Model->{$this->name}->fetch(intval($this->Request->getParams('GET','id')));

        // Check if the record is accessible
        if(empty($record)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested ".$this->name."."];
        } else {
            // Check if the organization owns the record
            if(array_key_exists('organization',$record) && $record['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this ".$this->name."."];
            }
            // Check if the user is authorized to access the record
            if(array_key_exists('assignedTo',$record) && $record['assignedTo']['id'] != $this->Auth->user()->id && !$this->Auth->isAuthorized("AccountManager", 1)){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this ".$this->name."."];
            }
        }

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Update the record
                $affectedRows = $this->Model->{$this->name}->update($record['id'], [
                    "isApproved" => 1,
                    "approvedOn" => date("Y-m-d H:i:s"),
                    "watermark" => null
                ]);

                // Check if the record was updated
                if($affectedRows){

                    // Retrieve the record
                    $message['data']['record'] = $this->Model->{$this->name}->fetch($record['id']);
                } else {
                    $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while updating the ".$this->name."."];
                }
            } else {
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "The method is not allowed for the requested URL."];
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Disapprove a document
     */
    public function disapproveAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the record
        $record = $this->Model->{$this->name}->fetch(intval($this->Request->getParams('GET','id')));

        // Check if the record is accessible
        if(empty($record)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested ".$this->name."."];
        } else {
            // Check if the organization owns the record
            if(array_key_exists('organization',$record) && $record['organization']['id'] != $this->Auth->user()->organization()->id){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this ".$this->name."."];
            }
            // Check if the user is authorized to access the record
            if(array_key_exists('assignedTo',$record) && $record['assignedTo']['id'] != $this->Auth->user()->id && !$this->Auth->isAuthorized("AccountManager", 1)){
                $message = ["status" => 403, "message" => "Forbidden", "data" => "You are not allowed to access this ".$this->name."."];
            }
        }

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Update the record
                $affectedRows = $this->Model->{$this->name}->update($record['id'], [
                    "isApproved" => 0,
                    "approvedOn" => null,
                    "watermark" => "DRAFT"
                ]);

                // Check if the record was updated
                if($affectedRows){

                    // Retrieve the record
                    $message['data']['record'] = $this->Model->{$this->name}->fetch($record['id']);
                } else {
                    $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while updating the ".$this->name."."];
                }
            } else {
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "The method is not allowed for the requested URL."];
            }
        }

        // Return the message
        return $message;
    }
}
