<?php
namespace SLiMS\ObjectStorage;

use Aws\Result;

class GioObjectAttribute
{
    private GioAdapter $adapter;
    private string $bucket;
    private array $item;
    private Result $awsResult;

    public function __construct(GioAdapter $adapter, string $bucket, array $item = [])
    {
        $this->adapter = $adapter;
        $this->bucket = $bucket;
        $this->item = $item;
    }

    public function getDetail(): Result
    {
        $client = $this->adapter->getClient();
        return $client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->item['Key']
        ]);
    }

    public function getExpire()
    {
        $detail = $this->getDetail();
        return (string)$detail->get('Expiration');
    }

    public function getLastModified()
    {
        $detail = $this->getDetail();
        return (string)$detail->get('LastModified');
    }

    public function getMimeType()
    {
        $detail = $this->getDetail();
        return (string)$detail->get('ContentType');
    }

    public function getUrl()
    {
        $detail = $this->getDetail();
        return $detail->get('@metadata')['effectiveUri'];
    }

    public function getFileSize()
    {
        $detail = $this->getDetail();
        return $detail->get('ContentLength');
    }

    public function getName()
    {
        return $this->item['Key'];
    }

    public function getContents()
    {
        $detail = $this->getDetail();
        return $detail->get('Body')->getContents();
    }
}
