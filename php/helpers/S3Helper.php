<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Helper {
    private $s3Client;
    private $bucket;
    private $cdnUrl;

    public function __construct() {
        $config = require __DIR__ . '/../config/aws_config.php';
        
        $this->s3Client = new S3Client([
            'version' => $config['version'],
            'region'  => $config['region'],
            'credentials' => [
                'key'    => $config['credentials']['key'],
                'secret' => $config['credentials']['secret'],
            ],
        ]);
        
        $this->bucket = $config['bucket'];
        $this->cdnUrl = $config['cdn_url'];
    }

    public function uploadFile($file, $directory, $fileName = null) {
        try {
            if (!$fileName) {
                $fileName = uniqid() . '_' . basename($file['name']);
            }

            $key = $directory . '/' . $fileName;
            
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SourceFile' => $file['tmp_name'],
                'ContentType' => $file['type'],
            ]);

            return [
                'success' => true,
                'url' => $this->cdnUrl . '/' . $key,
                'key' => $key
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function deleteFile($key) {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key
            ]);

            return [
                'success' => true
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getFileUrl($key) {
        return $this->cdnUrl . '/' . $key;
    }
} 