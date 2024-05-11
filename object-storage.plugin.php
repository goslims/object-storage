<?php
/**
 * Plugin Name: SLiMS Object Storage
 * Plugin URI: -
 * Description: Simpan data-data anda di cloud
 * Version: 1.0.0
 * Author: Drajat Hasan
 * Author URI: https://t.me/drajathasan
 */
use SLiMS\Plugins;
use SLiMS\Filesystems\Storage;

require __DIR__ . '/vendor/autoload.php';

if (file_exists($diskPath = __DIR__ . '/config/disks.php')) {
    config()->append('filesystem.disks', require $diskPath);
}