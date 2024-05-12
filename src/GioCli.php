<?php
namespace SLiMS\ObjectStorage;

use SLiMS\Cli\Command;
use SLiMS\Filesystems\Storage;

class GioCli extends Command
{
    /**
     * Signature is combination of command name
     * argument and options
     *
     * @var string
     */
    protected string $signature = 'gio {type}';

    /**
     * Command description
     *
     * @var string
     */
    protected string $description = 'Command description';

    /**
     * Handle command process
     *
     * @return void
     */
    public function handle()
    {
        $repository = Storage::repository();

        if (method_exists($this, $method = $this->argument('type'))) {
            $this->$method($repository);
        }
    }

    private function listall(Object $repository)
    {
        dump($repository->directories()->toArray());
    }
} 