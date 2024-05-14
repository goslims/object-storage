<?php
namespace SLiMS\ObjectStorage;

use Aws\S3\S3Client;  
use Aws\Exception\AwsException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\Config;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

class GioAdapter implements FilesystemAdapter 
{
    private S3Client $client;
    private FinfoMimeTypeDetector $mimedetector;
    private string $bucket;

    public function __construct(array $clientConstruct, array $config)
    {
        $this->client = new S3Client($clientConstruct, $config);
        $this->config = (object)$config;
        $this->mimedetector = new FinfoMimeTypeDetector;
    }

    public function getClient(): S3Client
    {
        return $this->client;
    }

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function fileExists(string $path): bool
    {
        return $this->client->doesObjectExist($this->config->bucket, $path);
    }

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->client->putObject([ 
            'Bucket' => $this->config->bucket,
            'Key'    => $path,
            'Body' => $contents,
            'ContentType' => $this->mimedetector->detectMimeTypeFromBuffer($contents)
        ]);
    }

    /**
     * @param resource $contents
     *
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->client->putObject([ 
            'Bucket' => $this->config->bucket,
            'Key'    => $path,
            'SourceFile' => $contents,
            'ContentType' => $this->mimedetector->detectMimeTypeFromBuffer(file_get_contents($contents))
        ]);
    }

    /**
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function read(string $path): string
    {
        $attribute = new GioObjectAttribute($this, $this->config->bucket, ['Key' => $path]);
        return $attribute->getContents();
    }

    /**
     * @return resource
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function readStream(string $path)
    {   
        if (property_exists($this->config, 'ttl')) {
            $object = $this->client->getCommand('GetObject', [
                'Bucket' => $this->config->bucket,
                'Key' => $path
            ]);

            $request = $this->client->createPresignedRequest($object, '+' . $this->config->ttl . ' minutes');
            // Get the actual presigned-url
            return (string)$request->getUri();
        }

        return $this->read($path);
    }

    /**
     * @throws UnableToDeleteFile
     * @throws FilesystemException
     */
    public function delete(string $path): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->config->bucket,
            'Key' => $path
        ]);
    }

    /**
     * @throws UnableToDeleteDirectory
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    /**
     * @throws UnableToCreateDirectory
     * @throws FilesystemException
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->client->putObject([ 
            'Bucket' => $this->config->bucket,
            'Key'    => "$path/"
        ]);
    }

    /**
     * @throws InvalidVisibilityProvided
     * @throws FilesystemException
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $defaultVisibility = ['FULL_CONTROL','WRITE','WRITE_ACP','READ','READ_ACP'];

        if (in_array($visibility, $defaultVisibility)) throw new \Exception("Visibility $visibility is not supported!", 1);

        $this->client->putObjectAcl([
            'Bucket' => $this->config->bucket,
            'Key' => $path,
            'ACL' => $visibility
        ]);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     * 
     */
    public function visibility(string $path): FileAttributes
    {
        $grants = $this->client->getObjectAcl([
            'Bucket' => $this->config->bucket,
            'Key' => $path
        ])->get('Grants')[0]??[];

        return new FileAttributes($path, null, $grants['Permission']);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function mimeType(string $path): FileAttributes
    {
        $attribute = new GioObjectAttribute($this, $this->config->bucket, ['Key' => $path]);
        return new FileAttributes($path, null, null, null, $attribute->getMimeType());
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes($path, null, null, 0);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function fileSize(string $path): FileAttributes
    {
        $attribute = new GioObjectAttribute($this, $this->config->bucket, ['Key' => $path]);
        return new FileAttributes($path, $attribute->getFileSize());
    }

    /**
     * @return iterable<StorageAttributes>
     *
     * @throws FilesystemException
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $iterator = $this->listDirectory();

        foreach ($iterator as $item) {
            yield new GioObjectAttribute($this, $this->config->bucket, $item);
        }
    }

    private function listDirectory()
    {
        return $this->client->listObjects([
            'Bucket' => $this->config->bucket,
            'MaxKeys' => property_exists($this->config, 'max_keys') ?  $this->config->max_keys : 1000,
        ])->get('Contents');
    }

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, Config $config): void
    {
        // Renaming data
        if (!$this->fileExists($destination)) {
            $this->write($destination, $this->read($source), $config);
        } else {
            // overwrite existing data
            $this->copy($source, $destination, $config);
        }

        $this->delete($source);
    }

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $this->client->copyObject([
            'Bucket' => $this->config->bucket,
            'Key' => urlencode($destination),
            'CopySource' => $this->config->bucket . '/' . urlencode($source)
        ]);
    }
}
