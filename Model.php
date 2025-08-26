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

            // Process the docvals
            if($key === 'docvals' && !is_array($value)){

                // Decode the JSON value
                $record[$key] = json_decode($value ?? '[]', true);
            }

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
     * Check if a string is a valid JSON
     *
     * @param int|string $string
     * @return bool
     */
    protected function avatar($string): string
    {
        // Import Global Variables
        global $REQUEST;

        // Check if $string is an integer or a string
        if(is_int($string) || is_string($string)){

            // Check if the value is file id
            if(filter_var($string, FILTER_VALIDATE_INT)!== false){

                // Retrieve the avatar's information
                $avatar = $this->Database->query()
                    ->table('files')
                    ->select('*')
                    ->filter()
                    ->where('id', $string)
                    ->limit(1)
                    ->fetch();

                // Check if the avatar exists
                if($avatar){

                    // Select the avatar
                    $avatar = $avatar[array_key_first($avatar)];

                    // Set the avatar's path
                    return 'data:'.$avatar['type'].';base64,' . base64_encode(file_get_contents($this->Config->root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $avatar['path'] . DIRECTORY_SEPARATOR . $avatar['uuid']));
                }
            } else {

                // Check if the value is a string and a valid path
                if(is_string($string) && is_file($string)){

                    // Set the avatar's path
                    return 'data:' . mime_content_type($string) . ';base64,' . base64_encode(file_get_contents($string));
                } else {

                    // Check if the value is a url starting with /avatar
                    if(is_string($string) && preg_match('/^\/avatar/', $string, $matches)){

                        // Set full url
                        $url = $REQUEST->getHostAddress() . $string;

                        // Check if the URL is valid
                        if (filter_var($url, FILTER_VALIDATE_URL)) {

                            // Decide if we can allow self-signed (dev/internal) on retry
                            $hostOnly = parse_url($url, PHP_URL_HOST);
                            $isPrivateHost = function (?string $h = null) : bool {
                                if (!$h) return false;
                                if ($h === 'localhost' || preg_match('/\.(local|lan|test)$/i', $h)) return true;
                                if (filter_var($h, FILTER_VALIDATE_IP)) {
                                    if (preg_match('#^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)#', $h)) return true;
                                }
                                return false;
                            };
                            $allowInsecure = $isPrivateHost($hostOnly) || getenv('ALLOW_INSECURE_SSL') === '1';

                            // Small helper to fetch binary + content-type (cURL first, stream fallback)
                            $fetch = function (string $u, bool $insecure = false) use ($allowInsecure) : array {
                                $binary = null;
                                $ctype  = null;

                                // --- cURL path ---
                                if (function_exists('curl_init')) {
                                    $ch = curl_init($u);
                                    $opts = [
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_CONNECTTIMEOUT => 5,
                                        CURLOPT_TIMEOUT        => 10,
                                        CURLOPT_USERAGENT      => 'DocumentsModel/1.0',
                                        CURLOPT_HEADER         => true,
                                        CURLOPT_HTTPHEADER     => ['Accept: image/*'],
                                    ];

                                    // SSL verification
                                    if ($insecure) {
                                        $opts[CURLOPT_SSL_VERIFYPEER] = false;
                                        $opts[CURLOPT_SSL_VERIFYHOST] = 0;
                                    } else {
                                        $opts[CURLOPT_SSL_VERIFYPEER] = true;
                                        $opts[CURLOPT_SSL_VERIFYHOST] = 2;
                                        $cafile = ini_get('openssl.cafile');
                                        if ($cafile && is_file($cafile)) {
                                            $opts[CURLOPT_CAINFO] = $cafile;
                                        }
                                    }

                                    curl_setopt_array($ch, $opts);
                                    $resp = curl_exec($ch);

                                    if ($resp === false) {
                                        $err = curl_error($ch);
                                        $eno = curl_errno($ch);
                                        // Log once; no binary returned
                                        error_log("DocumentsModel avatar fetch cURL error [$eno]: $err" . ($insecure ? ' (insecure)' : ''));
                                    } else {
                                        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                                        $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                        $ctype      = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: null;
                                        $binary     = substr($resp, $headerSize);
                                        if ($httpCode < 200 || $httpCode >= 300) {
                                            $binary = null;
                                            error_log("DocumentsModel avatar fetch HTTP $httpCode from $u");
                                        }
                                    }
                                    curl_close($ch);
                                }

                                // --- stream fallback ---
                                if ($binary === null) {
                                    $context = stream_context_create([
                                        'http' => ['timeout' => 10, 'method' => 'GET', 'header' => "Accept: image/*\r\n"],
                                        'ssl'  => [
                                            'verify_peer'       => !$insecure,
                                            'verify_peer_name'  => !$insecure,
                                            'allow_self_signed' => $insecure,
                                        ],
                                    ]);
                                    $data = @file_get_contents($u, false, $context);
                                    if ($data !== false) {
                                        $binary = $data;
                                        if (isset($http_response_header) && is_array($http_response_header)) {
                                            foreach ($http_response_header as $h) {
                                                if (stripos($h, 'Content-Type:') === 0) {
                                                    $ctype = trim(substr($h, 13));
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }

                                return [$binary, $ctype];
                            };

                            // Try secure first
                            [$binary, $contentType] = $fetch($url, false);

                            // If that failed, one retry allowing self-signed (for dev/private hosts)
                            if ($binary === null && $allowInsecure) {
                                $this->Log->warning("Avatar fetch failed with strict TLS; retrying with self-signed allowed for $hostOnly");
                                [$binary, $contentType] = $fetch($url, true);
                            }

                            if ($binary) {
                                // Normalize/verify MIME
                                if (!$contentType || stripos($contentType, 'image/') !== 0) {
                                    if (class_exists('\finfo')) {
                                        $fi = new \finfo(FILEINFO_MIME_TYPE);
                                        $detected = $fi->buffer($binary);
                                        if ($detected) $contentType = $detected;
                                    }
                                }

                                if ($contentType && stripos($contentType, 'image/') === 0) {
                                    return 'data:' . $contentType . ';base64,' . base64_encode($binary);
                                } else {
                                    $this->Log->warning("Avatar fetch returned non-image content-type: " . ($contentType ?: 'unknown'));
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return '';
        }
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
        global $AUTH,$LOCALE,$REQUEST;

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
            'user.name' => '',
            'user.title' => '',
            'user.role' => '',
            'user.address' => '',
            'user.city' => '',
            'user.state' => '',
            'user.country' => '',
            'user.zipcode' => '',
            'user.email' => '',
            'user.phone' => '',
            'user.mobile' => '',
            'user.tollfree' => '',
            'user.fax' => '',
            'user.locale' => $LOCALE->current(),
            'user.language' => $locales[$LOCALE->current()] ?? '',
            'user.avatar' => '',
            'organization.name' => '',
            'organization.title' => '',
            'organization.role' => '',
            'organization.address' => '',
            'organization.city' => '',
            'organization.state' => '',
            'organization.country' => '',
            'organization.zipcode' => '',
            'organization.email' => '',
            'organization.phone' => '',
            'organization.mobile' => '',
            'organization.tollfree' => '',
            'organization.fax' => '',
            'organization.website' => '',
            'organization.businessNumber' => '',
            'organization.taxExtension' => '',
            'organization.importerExtension' => '',
            'organization.locale' => $LOCALE->current(),
            'organization.language' => $locales[$LOCALE->current()] ?? '',
            'organization.avatar' => '',
        ];

        // Check if user is authenticated
        if(!in_array(get_class($AUTH),["Module","LaswitchTech\Core\Module"]) && $AUTH->isAuthenticated()){

            // Add user's username
            $variables['username'] = $AUTH->user()->username ?? $variables['username'];

            // Add Auth's Objects Information
            foreach($variables as $key => $value){
                $keys = explode('.', $key);
                if(in_array($keys[0], ['user','organization']) && isset($keys[1]) && !empty($keys[1])){
                    switch($keys[0]){
                        case 'user':
                            if($keys[1] === 'language') {
                                $variables[$key] = $locales[$variables[$keys[0].'.locale'] ?? $LOCALE->current()];
                                break;
                            }
                            if($keys[1] === 'avatar') {
                                $variables[$key] = $this->avatar('/avatar?id='.$AUTH->user()->vcard('id'));
                                break;
                            }
                            $variables[$key] = $AUTH->user()->vcard($keys[1]) ?? $variables[$key];
                            break;
                        case 'organization':
                            if($keys[1] === 'language') {
                                $variables[$key] = $locales[$variables[$keys[0].'.locale'] ?? $LOCALE->current()];
                                break;
                            }
                            if($keys[1] === 'avatar') {
                                $variables[$key] = $this->avatar('/avatar?id='.$AUTH->user()->organization()->vcard['id']);
                                break;
                            }
                            $variables[$key] = $AUTH->user()->organization()->{$keys[1]} ?? $variables[$key];
                            break;
                    }
                }
            }
        }

        // Add dynamic variables
        foreach($values as $key => $value){
            if(is_array($value)){
                foreach($value as $k => $v){
                    if(!is_array($v)){
                        $variables[$key.'.'.$k] = $v;
                    }
                }
            } else {
                if($key === 'avatar') {
                    $variables[$key] = $this->avatar($value);
                    continue;
                }
                $variables[$key] = $value;
            }
        }

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
