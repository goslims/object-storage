<?php
namespace SLiMS\ObjectStorage;

use closure;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use SLiMS\Filesystems\{Guard,Utils,Stream};
use SLiMS\Filesystems\Providers\Contract;
use utility;

class Gio extends Contract
{
    use Guard,Utils,Stream;
    
    private $uploadedFile = '';
    private $uploadStatus = true;
    
    /**
     * Define adapter and filesystem
     *
     * @param string $root
     */
    public function __construct(array $clientConstruct, array $config, string $diskName)
    {
        $this->adapter = new GioAdapter($clientConstruct, $config);
        $this->filesystem = new Filesystem($this->adapter);
        $this->diskName = $diskName;
        $this->path = '/';
    }

    /**
     * Upload file process with stream
     *
     * @param string $fileToUpload
     * @param closure $validation
     * @return object
     */
    public function upload(string $fileToUpload, closure $validation)
    {
        try {
            // create new random file name
            $this->uploadedFile = $_FILES[$fileToUpload]['name'];

            // Write file 
            // dd($this->uploadedFile);
            $this->filesystem->write($_FILES[$fileToUpload]['name'], $this->streamContent($_FILES[$fileToUpload]['tmp_name']));

            // Make a validation
            $validation($this);

            // Close resource
            // if (is_resource($resource)) fclose($resource);

        } catch (FilesystemException | UnableToWriteFile $e) {
            $this->error = $e->getMessage();
        }
        
        return $this;
    }

    private function streamContent($path)
    {
        ob_start();
        // Open the file in binary mode
        $fp = fopen($path, 'rb');

        // Output the file
        while (!feof($fp)) {
            // Read and output a chunk of the file
            echo fread($fp, 8192);

            // Flush the output buffer to free up memory
            // ob_flush();
            // flush();
        }

        // Close the file
        fclose($fp);
        return ob_get_clean();
    }

    /**
     * Rename uploaded file with new name
     *
     * @param string $newName
     * @return object
     */
    public function as(string $newName)
    {
        if ($this->uploadStatus && $this->isExists($originalName = $this->uploadedFile))
        {
            $this->move($originalName, ($newName = $newName . $this->getExt($this->uploadedFile)));
            $this->uploadedFile = $newName;
        }
        
        return $this;
    }
}