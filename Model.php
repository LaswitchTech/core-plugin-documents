<?php

/**
 * Core Framework - DocumentsModel
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Model;
use \LaswitchTech\Core\Objects\PDF;

class DocumentsModel extends Model {

    // Global Properties
    private $Config;
    private $UUID;
    private $Log;

    // Properties
    private $Path;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Import Global Variables
        global $CONFIG, $UUID, $LOG;

        // Set Properties
        $this->Config = $CONFIG;
        $this->UUID = $UUID;
        $this->Log = $LOG;

        // Initialize properties
        $this->Path = $this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'documents';

        // Add the documents log
        $this->Log->add('documents');
    }

    /**
     * Retrieve Document Types
     *
     * @param bool $all
     * @return array
     */
    public function types(bool $all = false): array
    {
        // Import Global Variables
        global $AUTH;

        // Create the Query
        $Query = $this->Database->query()
            ->table('doctypes')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->filter()
            ->where('id', 9999, '<>');

        // Retrieve the Results
        $result = $Query->result();

        // Decode JSON Fields
        foreach($result as $key => $record){
            if(!$all && !$AUTH->isAuthorized("DocType>".$result[$key]["name"],1)){
                unset($result[$key]);
                continue;
            }
            $result[$key]['locked'] = json_decode($record['locked'] ?? '[]', true);
            $result[$key]['permissions'] = json_decode($record['permissions'] ?? '[]', true);
        }

        // Return the Results
        return $result;
    }

    /**
     * Retrieve Document Type
     *
     * @param int $id
     * @return array
     */
    public function type(int $id): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('doctypes')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->filter()
            ->where('id', 9999, '<>')
            ->where('id', $id)
            ->limit(1);

        // Retrieve the Results
        $result = $Query->result();

        // Decode JSON Fields
        foreach($result as $key => $record){
            $result[$key]['locked'] = json_decode($record['locked'] ?? '[]', true);
            $result[$key]['permissions'] = json_decode($record['permissions'] ?? '[]', true);
        }

        // Return the Results
        return $result[array_key_first($result)] ?? [];
    }

    /**
     * Create a new Document and return the id
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('documents')
            ->insert($data);

        // Execute the Query
        $affectedRows = $Query->execute();

        // Execute the Query
        return $Query->lastId();
    }

    /**
     * Update a Document
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('documents')
            ->update($data)
            ->where('id', $id);

        // Execute the Query
        return $Query->execute();
    }

    /**
     * Retrieve a Document
     *
     * @param mixed $id
     * @return array
     */
    public function get(mixed $id): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('documents')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('doctype', 'doctypes', 'id')
            ->join('letterhead', 'files', 'id')
            ->join('organization', 'organizations', 'id')
            ->filter()
            ->where('id', 9999, '<>')
            ->where('isArchived', 0)
            ->filter()
            ->where('id', $id, '=', 'OR')
            ->where('uuid', $id, '=', 'OR')
            ->limit(1);

        // Retrieve the Results
        $result = $Query->result();

        // Decode JSON Fields
        foreach($result as $key => $record){

            // Decode JSON Fields
            $result[$key]['docvals'] = json_decode($record['docvals'] ?? '[]', true);
            $result[$key]['doctype']['uuid'] = $this->UUID->toString($record['doctype']['id'].$record['doctype']['template'].$record['doctype']['locale']);
            $result[$key]['doctype']['locked'] = json_decode($record['doctype']['locked'] ?? '[]', true);
            $result[$key]['doctype']['permissions'] = json_decode($record['doctype']['permissions'] ?? '[]', true);

            // Set the document's template
            $result[$key]['doctype']['template'] = [
                "name" => $result[$key]['doctype']['template'],
                "path" => $this->Config->root() . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'doctype' . DIRECTORY_SEPARATOR . $result[$key]['doctype']['template'],
            ];

            // Set Content
            if(!is_file($result[$key]['doctype']['template']['path'])){
                $result[$key]['doctype']['template']['path'] = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'doctype' . DIRECTORY_SEPARATOR . $result[$key]['doctype']['template'];
            }
            $result[$key]['doctype']['template']['content'] = is_file($result[$key]['doctype']['template']['path']) ? file_get_contents($result[$key]['doctype']['template']['path']) : '';

            // Add the document's template's variables
            $result[$key]['doctype']['template']['variables'] = $this->vars($result[$key]['doctype']['template']['content']);
        }

        // Return the Results
        return $result[array_key_first($result)] ?? [];
    }

    /**
     * Retrieve Document's Variables
     *
     * @param string $string
     * @return array
     */
    private function vars(string $string): array
    {
        // Regular expression to match the variables in the format %VAR%
        $pattern = '/{{([^}]+)}}/';

        // Find all matches
        preg_match_all($pattern, $string, $matches);

        // The variables are in the second element of the $matches array
        $variables = array_unique($matches[0]);

        // Return the variables
        return $variables;
    }

    /**
     * Replace Variables from an array
     *
     * @param string $string
     * @param array $values
     * @return string
     */
    private function replace(string $string, array $values): string
    {
        // Replace the placeholders with the corresponding data
        foreach ($values as $key => $value) {
            $string = str_replace('{{' . $key . '}}', $value ?? '', $string);
        }

        // Return the string
        return $string;
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
        global $AUTH,$LOCALE,$CONFIG,$HELPER;

        // Set the Log
        $this->Log->set('documents');

        // Retrieve Locales
        $locales = $LOCALE->list();

        // Unset reserved variables
        foreach(['doctype','letterhead'] as $key){ if(isset($values[$key])){ unset($values[$key]); } }

        // Initialize the variables
        $variables = [
            'root' => $CONFIG->root() ?? '',
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
                        $content = $HELPER->Favicon->content($values['website']);
                        $mimetype = $HELPER->Favicon->mimeType($content);
                        $variables['avatar'] = 'data:' . $mimetype . ';base64,' . base64_encode($content);
                    }

                    // Check if the value is a url starting with /plugins/leads/logo?id=
                    if(is_string($values['avatar']) && array_key_exists('website',$values) && preg_match('/^\/plugins\/leads\/logo\?id=([0-9]+)$/', $values['avatar'], $matches)){

                        // Set the ID of the organization
                        $id = $matches[1];

                        // Retrieve the favicon
                        $content = $HELPER->Favicon->content($values['website']);
                        $mimetype = $HELPER->Favicon->mimeType($content);
                        $variables['avatar'] = 'data:' . $mimetype . ';base64,' . base64_encode($content);
                    }

                    // Check if the value is a url starting with /plugins/organizations/logo?id=
                    if(is_string($values['avatar']) && array_key_exists('website',$values) && preg_match('/^\/plugins\/organizations\/logo\?id=([0-9]+)$/', $values['avatar'], $matches)){

                        // Set the ID of the organization
                        $id = $matches[1];

                        // Retrieve the favicon
                        $content = $HELPER->Favicon->content($values['website']);
                        $mimetype = $HELPER->Favicon->mimeType($content);
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
        $PDF->path($this->Path . DIRECTORY_SEPARATOR . $document['uuid'] . DIRECTORY_SEPARATOR . $this->replace($document['filename'], $values));

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
