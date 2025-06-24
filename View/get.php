<?php

// Set Content Type
$types = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/bmp'
];
$type = $this->call('type');
if(!in_array($type, $types)){
    $type = 'application/octet-stream';
}
header('Content-Type: ' . $type);

// Set Content Disposition
if(is_null($this->Request->getParams('GET','download'))){
    header('Content-Disposition: inline; filename="' . $this->call('filename') . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $this->call('filename') . '"');
}

// Check if the file should be downloaded
if(!is_null($this->Request->getParams('GET','download'))){
    header('Content-Description: File Transfer');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $this->call('size'));
}

// Output the file content
echo $this->call('content');
