<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Controller;

class DocumentsController extends Controller {

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call Parent Constructor
        parent::__construct();

        // Retrieve the namespace
        $namespace = $this->Request->getNamespace();

        // Set Properties
        switch($namespace){
            case "/documents/get":
                $this->Public = false;
                $this->Level = 0;
                break;
        }
    }

    /**
     * Get a Document
     *
     * @return mixed
     */
    public function getAction(): mixed
    {

        // Retrieve the parameters
        $uuid = $this->Request->getParams('GET', 'uuid') ?? null;

        // Retrieve the document metadata
        $document = $this->Model->Documents->fetchByUUID($uuid);

        // Check if the document exists
        if($document){

            // Generate the document
            $document['path'] = $this->Model->Documents->generate($document);

            // Check if the document was generated
            if($document['path']){

                // Retrieve the document content
                $document['content'] = file_get_contents($document['path']);

                // Retrieve the document type
                $document['type'] = mime_content_type($document['path']);

                // Retrieve the document size
                $document['size'] = filesize($document['path']);
            }
        }

        // Return the document
        return $document;
    }
}
