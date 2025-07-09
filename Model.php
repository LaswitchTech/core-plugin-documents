<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Base\BaseModel;
use \LaswitchTech\Core\Objects\PDF;

class DocumentsModel extends BaseModel {

    // Global Properties
    protected $Helper;
    protected $Config;
    protected $UUID;
    protected $Log;

    // Properties
    protected $Path;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Import Global Variables
        global $HELPER, $CONFIG, $UUID, $LOG;

        // Set Properties
        $this->Helper = $HELPER;
        $this->Config = $CONFIG;
        $this->UUID = $UUID;
        $this->Log = $LOG;

        // Initialize properties
        $this->Path = $this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'documents';

        // Add the documents log
        $this->Log->add('documents');

        // Initialize the Model
        $this->init('documents');
    }

    /**
     * Retrieve multiple records
     *
     * @param array $conditions
     * @return array
     */
    public function fetchAll(array $conditions = [], string $conjunction = 'AND'): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table($this->table)
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('doctype', 'doctypes', 'id')
            ->join('letterhead', 'files', 'id')
            ->join('organization', 'organizations', 'id')
            ->filter()
            ->where('id', 9999, '<>')
            ->where('organization', $this->Auth->user()->organization()->id);

        // Check if the conditions are empty
        if(!empty($conditions)){

            // Add a Filter
            $Query->filter();

            // Add the Conditions
            foreach($conditions as $key => $condition){

                // Check if the key exists in the definition
                if(!array_key_exists($condition['key'], $this->definition)){

                    // Remove the key from the data
                    unset($conditions[$key]);
                    continue;
                }

                // Add the condition to the Query
                $Query->where($condition["key"], $condition["value"], $condition["operator"], $conjunction);
            }
        }

        // Retrieve the Results
        $records = $Query->fetch();

        // Loop through the records to process them
        foreach($records as $key => $record){

            // Overwrite the record with the processed one
            $records[$key] = $this->process($record);
        }

        // Return the Results
        return $records;
    }

    /**
     * Retrieve a single record
     *
     * @param int $id
     * @return array
     */
    public function fetch(int $id): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table($this->table)
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('doctype', 'doctypes', 'id')
            ->join('letterhead', 'files', 'id')
            ->join('organization', 'organizations', 'id')
            ->filter()
            ->where('id', 9999, '<>')
            ->filter()
            ->where($this->primary, $id)
            ->limit(1);

        // Retrieve the record
        $records = $Query->fetch();

        // Loop through the records to process them
        foreach($records as $key => $record){

            // Overwrite the record with the processed one
            $records[$key] = $this->process($record);
        }

        // Return the record or an empty array if not found
        return $records[array_key_first($records)] ?? [];
    }

    /**
     * Retrieve a single record
     *
     * @param string $uuid
     * @return array
     */
    public function fetchByUUID(string $uuid): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table($this->table)
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('doctype', 'doctypes', 'id')
            ->join('letterhead', 'files', 'id')
            ->join('organization', 'organizations', 'id')
            ->filter()
            ->where('id', 9999, '<>')
            ->filter()
            ->where('uuid', $uuid)
            ->limit(1);

        // Retrieve the record
        $records = $Query->fetch();

        // Loop through the records to process them
        foreach($records as $key => $record){

            // Overwrite the record with the processed one
            $records[$key] = $this->process($record);
        }

        // Return the record or an empty array if not found
        return $records[array_key_first($records)] ?? [];
    }

    /**
     * Process a record
     *
     * @param array $record
     * @return array
     */
    protected function process(array $record): array
    {
        // Execute the parent process method
        $record = parent::process($record);

        // Decode JSON Fields
        foreach($record as $key => $value){

            // Process the doctype
            if($key === 'doctype' && is_array($value)){

                // Check if the value['locked'] is a valid JSON string
                if(is_string($value['locked']) && $this->isJson($value['locked'])){

                    // Decode the JSON value
                    $record[$key]['locked'] = json_decode($value['locked'], true);
                }

                // Check if the value['permissions'] is a valid JSON string
                if(is_string($value['permissions']) && $this->isJson($value['permissions'])){

                    // Decode the JSON value
                    $record[$key]['permissions'] = json_decode($value['permissions'], true);
                }

                // Generate the UUID for the doctype
                $record[$key]['uuid'] = $this->UUID->toString($value['id'].$value['template'].$value['locale']);

                // Set the document's template
                $record[$key]['template'] = [
                    "name" => $record[$key]['template'],
                    "path" => $this->Config->root() . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'doctype' . DIRECTORY_SEPARATOR . $record[$key]['template'],
                ];

                // Check if the template path is valid
                if(!is_file($record[$key]['template']['path'])){
                    $record[$key]['template']['path'] = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'doctype' . DIRECTORY_SEPARATOR . $record[$key]['template']['name'];
                }

                // Set the template's content
                $record[$key]['template']['content'] = is_file($record[$key]['template']['path']) ? file_get_contents($record[$key]['template']['path']) : '';

                // Add the document's template's variables
                $record[$key]['template']['variables'] = $this->Helper->Documents->vars($record[$key]['template']['content']);
            }
        }

        // Return the processed record
        return $record;
    }

    /**
     * Set values from an array
     *
     * @param array $values
     * @return array
     */
    public function variables(array $values = []): array
    {
        // Import Global Variables
        global $AUTH,$LOCALE;

        // Set the Log
        $this->Log->set('documents');

        // Retrieve Locales
        $locales = $LOCALE->list();

        // Unset reserved variables
        foreach(['doctype','letterhead'] as $key){ if(isset($values[$key])){ unset($values[$key]); } }

        // Initialize the variables
        $variables = [
            'root' => $this->Config->root() ?? '',
            'today' => date('Y-m-d') ?? '',
            'date' => date('Y-m-d') ?? '',
            'now' => date('Y-m-d H:i:s') ?? '',
            'this_year' => date('Y') ?? '',
            'this_month' => date('m') ?? '',
            'this_day' => date('d') ?? '',
            'year' => date('Y') ?? '',
            'month' => date('m') ?? '',
            'day' => date('d') ?? '',
            'next_2year' => date('Y-m-d', strtotime('+2 year')) ?? '',
            'next_18months' => date('Y-m-d', strtotime('+18 months')) ?? '',
            'next_year' => date('Y-m-d', strtotime('+1 year')) ?? '',
            'next_month' => date('Y-m-d', strtotime('+1 month')) ?? '',
            'next_week' => date('Y-m-d', strtotime('+1 week')) ?? '',
            'next_day' => date('Y-m-d', strtotime('+1 day')) ?? '',
            'tomorrow' => date('Y-m-d', strtotime('+1 day')) ?? '',
            'yesterday' => date('Y-m-d', strtotime('-1 day')) ?? '',
            'last_week' => date('Y-m-d', strtotime('-1 week')) ?? '',
            'last_month' => date('Y-m-d', strtotime('-1 month')) ?? '',
            'last_year' => date('Y-m-d', strtotime('-1 year')) ?? '',
            'last_18months' => date('Y-m-d', strtotime('-18 months')) ?? '',
            'last_2year' => date('Y-m-d', strtotime('-2 year')) ?? '',
            'locale' => $LOCALE->current(),
            'language' => $locales[$LOCALE->current()] ?? '',
            'until' => '',
            'from' => '',
            'title' => '',
            'subject' => '',
            'author' => '',
            'creator' => '',
            'approvedOn' => '',
            'keywords' => '',
            'username' => '',
            'user_name' => '',
            'user_title' => '',
            'user_role' => '',
            'user_address' => '',
            'user_city' => '',
            'user_state' => '',
            'user_country' => '',
            'user_zipcode' => '',
            'user_email' => '',
            'user_phone' => '',
            'user_mobile' => '',
            'user_tollfree' => '',
            'user_fax' => '',
            'user_locale' => $LOCALE->current(),
            'user_language' => $locales[$LOCALE->current()] ?? '',
            'user_avatar' => '',
            'organization_name' => '',
            'organization_title' => '',
            'organization_role' => '',
            'organization_address' => '',
            'organization_city' => '',
            'organization_state' => '',
            'organization_country' => '',
            'organization_zipcode' => '',
            'organization_email' => '',
            'organization_phone' => '',
            'organization_mobile' => '',
            'organization_tollfree' => '',
            'organization_fax' => '',
            'organization_website' => '',
            'organization_businessNumber' => '',
            'organization_taxExtension' => '',
            'organization_importerExtension' => '',
            'organization_locale' => $LOCALE->current(),
            'organization_language' => $locales[$LOCALE->current()] ?? '',
            'organization_avatar' => '',
        ];

        // Check if user is authenticated
        if(!in_array(get_class($AUTH),["Module","LaswitchTech\Core\Module"]) && $AUTH->isAuthenticated()){

            // Add user's information
            $variables['username'] = $AUTH->user()->username ?? $variables['username'];
            $variables['user_name'] = $AUTH->user()->vcard('name') ?? $variables['user_name'];
            $variables['user_title'] = $AUTH->user()->vcard('title') ?? $variables['user_title'];
            $variables['user_role'] = $AUTH->user()->vcard('role') ?? $variables['user_role'];
            $variables['user_address'] = $AUTH->user()->vcard('address') ?? $variables['user_address'];
            $variables['user_city'] = $AUTH->user()->vcard('city') ?? $variables['user_city'];
            $variables['user_state'] = $AUTH->user()->vcard('state') ?? $variables['user_state'];
            $variables['user_country'] = $AUTH->user()->vcard('country') ?? $variables['user_country'];
            $variables['user_zipcode'] = $AUTH->user()->vcard('zipcode') ?? $variables['user_zipcode'];
            $variables['user_email'] = $AUTH->user()->vcard('email') ?? $variables['user_email'];
            $variables['user_phone'] = $AUTH->user()->vcard('phone') ?? $variables['user_phone'];
            $variables['user_mobile'] = $AUTH->user()->vcard('mobile') ?? $variables['user_mobile'];
            $variables['user_tollfree'] = $AUTH->user()->vcard('tollfree') ?? $variables['user_tollfree'];
            $variables['user_fax'] = $AUTH->user()->vcard('fax') ?? $variables['user_fax'];
            $variables['user_locale'] = $AUTH->user()->vcard('locale') ?? $variables['user_locale'];
            $variables['user_language'] = $locales[$variables['user_locale'] ?? $LOCALE->current()];
            if(!empty($AUTH->user()->vcard('avatar'))){
                $variables['user_avatar'] =  'data:'.$AUTH->user()->vcard('avatar')['type'].';base64,' . base64_encode(file_get_contents($this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $AUTH->user()->vcard('avatar')['path'] . DIRECTORY_SEPARATOR . $AUTH->user()->vcard('avatar')['uuid']));
            }

            // Add organization's information
            $variables['organization_name'] = $AUTH->user()->organization()->name ?? $variables['organization_name'];
            $variables['organization_title'] = $AUTH->user()->organization()->title ?? $variables['organization_title'];
            $variables['organization_role'] = $AUTH->user()->organization()->role ?? $variables['organization_role'];
            $variables['organization_address'] = $AUTH->user()->organization()->address ?? $variables['organization_address'];
            $variables['organization_city'] = $AUTH->user()->organization()->city ?? $variables['organization_city'];
            $variables['organization_state'] = $AUTH->user()->organization()->state ?? $variables['organization_state'];
            $variables['organization_country'] = $AUTH->user()->organization()->country ?? $variables['organization_country'];
            $variables['organization_zipcode'] = $AUTH->user()->organization()->zipcode ?? $variables['organization_zipcode'];
            $variables['organization_email'] = $AUTH->user()->organization()->email ?? $variables['organization_email'];
            $variables['organization_phone'] = $AUTH->user()->organization()->phone ?? $variables['organization_phone'];
            $variables['organization_mobile'] = $AUTH->user()->organization()->mobile ?? $variables['organization_mobile'];
            $variables['organization_tollfree'] = $AUTH->user()->organization()->tollfree ?? $variables['organization_tollfree'];
            $variables['organization_fax'] = $AUTH->user()->organization()->fax ?? $variables['organization_fax'];
            $variables['organization_website'] = $AUTH->user()->organization()->website ?? $variables['organization_website'];
            $variables['organization_businessNumber'] = $AUTH->user()->organization()->businessNumber ?? $variables['organization_businessNumber'];
            $variables['organization_taxExtension'] = $AUTH->user()->organization()->taxExtension ?? $variables['organization_taxExtension'];
            $variables['organization_importerExtension'] = $AUTH->user()->organization()->importerExtension ?? $variables['organization_importerExtension'];
            $variables['organization_locale'] = $AUTH->user()->organization()->locale ?? $variables['organization_locale'];
            $variables['organization_language'] = $locales[$variables['organization_locale']];
            if($AUTH->user()->organization()->avatar['id']){
                $variables['organization_avatar'] =  'data:'.$AUTH->user()->organization()->avatar['type'].';base64,' . base64_encode(file_get_contents($this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $AUTH->user()->organization()->avatar['path'] . DIRECTORY_SEPARATOR . $AUTH->user()->organization()->avatar['uuid']));
            }
        }

        // Add dynamic variables
        foreach($values as $key => $value){
            $variables[$key] = $value;
        }

        // Check if an avatar is set
        if(isset($values['avatar']) && !empty($values['avatar'])){
            $this->Log->debug('Avatar Type: '.gettype($values['avatar']));
            $this->Log->debug('Avatar Value: '.$values['avatar']);

            // Check if the value is file id
            if(filter_var($values['avatar'], FILTER_VALIDATE_INT)!== false){

                // Retrieve the avatar's information
                $avatar = $this->Database->query()
                    ->table('files')
                    ->select('*')
                    ->filter()
                    ->where('id', $values['avatar'])
                    ->limit(1)
                    ->fetch();

                // Check if the avatar exists
                if($avatar){

                    // Select the avatar
                    $avatar = $avatar[array_key_first($avatar)];

                    // Set the avatar's path
                    $variables['avatar'] = 'data:'.$avatar['type'].';base64,' . base64_encode(file_get_contents($this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $avatar['path'] . DIRECTORY_SEPARATOR . $avatar['uuid']));
                }
            } else {

                // Check if the value is a string and a valid path
                if(is_string($values['avatar']) && is_file($values['avatar'])){

                    // Set the avatar's path
                    $variables['avatar'] = 'data:' . mime_content_type($values['avatar']) . ';base64,' . base64_encode(file_get_contents($values['avatar']));
                } else {

                    // Check if the value is a url starting with /plugins/clients/logo?id=
                    if(is_string($values['avatar']) && array_key_exists('website',$values) && preg_match('/^\/plugins\/clients\/logo\?id=([0-9]+)$/', $values['avatar'], $matches)){

                        // Set the ID of the organization
                        $id = $matches[1];

                        // Retrieve the favicon
                        $content = $this->Helper->Favicon->content($values['website']);
                        $mimetype = $this->Helper->Favicon->mimeType($content);
                        $variables['avatar'] = 'data:' . $mimetype . ';base64,' . base64_encode($content);
                    }

                    // Check if the value is a url starting with /plugins/documents/logo?id=
                    if(is_string($values['avatar']) && array_key_exists('website',$values) && preg_match('/^\/plugins\/documents\/logo\?id=([0-9]+)$/', $values['avatar'], $matches)){

                        // Set the ID of the organization
                        $id = $matches[1];

                        // Retrieve the favicon
                        $content = $this->Helper->Favicon->content($values['website']);
                        $mimetype = $this->Helper->Favicon->mimeType($content);
                        $variables['avatar'] = 'data:' . $mimetype . ';base64,' . base64_encode($content);
                    }

                    // Check if the value is a url starting with /plugins/organizations/logo?id=
                    if(is_string($values['avatar']) && array_key_exists('website',$values) && preg_match('/^\/plugins\/organizations\/logo\?id=([0-9]+)$/', $values['avatar'], $matches)){

                        // Set the ID of the organization
                        $id = $matches[1];

                        // Retrieve the favicon
                        $content = $this->Helper->Favicon->content($values['website']);
                        $mimetype = $this->Helper->Favicon->mimeType($content);
                        $variables['avatar'] = 'data:' . $mimetype . ';base64,' . base64_encode($content);
                    }
                }
            }
        }

        // // Replace the placeholders with the corresponding data
        // $variables['keywords'] = $this->PDF->replaceVars($values['keywords'] ?? '',$values['docvals'] ?? []);

        // Return the variables
        return $variables;
    }

    /**
     * Generate a PDF from the document
     *
     * @param array $document
     * @return string
     */
    public function generate(array $document): ?string
    {
        // Generate the UUIDs
        $document['doctype']['template']['uuid'] = $this->UUID->toString($document['doctype']['id'].$document['doctype']['created']);
        $document['uuid'] = $this->UUID->toString($document['id'].$document['created']);

        // Create a variables dictionary
        $variables = $document['docvals'];
        $variables['title'] = $document['title'];
        $variables['subject'] = $document['subject'];
        $variables['author'] = $document['author'];
        $variables['creator'] = $document['creator'];
        $variables['created'] = $document['created'];
        $variables['approvedOn'] = $document['approvedOn'];
        $variables['filename'] = $document['filename'];

        // Retrieve the values
        $values = $this->variables($variables);

        // Create an instance of the PDF class
        $PDF = new PDF();

        // Set the PDF Properties
        $PDF->mode($document['doctype']['mode'] ?? 'utf-8');
        $PDF->format($document['doctype']['format'] ?? 'Letter');
        $PDF->orientation($document['doctype']['orientation'] ?? 'P');
        $PDF->dpi($document['doctype']['dpi'] ?? 96);
        $document['title'] ? $PDF->title($document['title']) : null;
        $document['author'] ? $PDF->author($document['author']) : null;
        $document['creator'] ? $PDF->creator($document['creator']) : null;
        $document['subject'] ? $PDF->subject($document['subject']) : null;
        $document['keywords'] ? $PDF->keywords($document['keywords']) : null;
        $document['watermark'] ? $PDF->watermark($document['watermark']) : null;
        $PDF->template($document['doctype']['template']['path']);
        $PDF->variables($values);
        $PDF->path($this->Path . DIRECTORY_SEPARATOR . $document['uuid'] . DIRECTORY_SEPARATOR . $this->Helper->Documents->replace($document['filename'], $values));

        // Set the PDF Security
        if(!empty($document['doctype']['password']) && !is_null($document['doctype']['password'])){
            foreach($document['permissions'] ?? ['print', 'print-highres'] as $permission){
                $PDF->allow($permission);
            }
            if(!empty($document['password']) && !is_null($document['password'])){
                $PDF->user($this->UUID->toString($document['id'].$document['created'].$document['uuid']));
            }
            $PDF->owner($document['doctype']['uuid']);
            $PDF->encryption(intval($document['doctype']['encryption']) ?? 128);
        }

        // Check if the document supports a letterhead
        if($document['doctype']['hasLetterhead'] && $document['doctype']['letterhead']){

            // Check if a custom letterhead is set
            if($document['letterhead']['id']){

                // Add the letterhead
                $PDF->letterhead($this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $document['letterhead']['path'] . DIRECTORY_SEPARATOR . $document['letterhead']['uuid']);
            } else {

                // Create an instance of the PDF class
                $letterhead = new PDF();

                // Set the PDF Properties
                $letterhead->mode($document['doctype']['mode'] ?? 'utf-8');
                $letterhead->format($document['doctype']['format'] ?? 'Letter');
                $letterhead->orientation($document['doctype']['orientation'] ?? 'P');
                $letterhead->dpi($document['doctype']['dpi'] ?? 96);
                $document['title'] ? $letterhead->title($document['title']) : null;
                $document['author'] ? $letterhead->author($document['author']) : null;
                $document['creator'] ? $letterhead->creator($document['creator']) : null;
                $document['subject'] ? $letterhead->subject($document['subject']) : null;
                $document['keywords'] ? $letterhead->keywords($document['keywords']) : null;
                $document['watermark'] ? $letterhead->watermark($document['watermark']) : null;
                $letterhead->variables($values);
                $letterhead->path($this->Path . DIRECTORY_SEPARATOR . $document['uuid'] . DIRECTORY_SEPARATOR . 'letterhead.pdf');

                // Set the Letterhead's Template
                $path = $this->Config->root() . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'doctype' . DIRECTORY_SEPARATOR . $document['doctype']['letterhead'];
                if(!is_file($path)){
                    $path = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'doctype' . DIRECTORY_SEPARATOR . $document['doctype']['letterhead'];
                }
                if(is_file($path)){
                    $letterhead->template($path);
                }

                // Generate the letterhead
                $PDF->letterhead($letterhead->generate());
            }
        }

        // Return the file path
        return $PDF->generate();
    }
}
