# SLiMS Object Storage
Merupakan sub-pustaka dari ```SLiMS\Filesystems``` dalam bentuk [plugins](https://slims.web.id/docs/development-guide/Plugin/Intro/) yang digunakan untuk menyimpan file-file yang diunggah ke SLiMS di sebuah storage server yang menerapkan arsitektur [object-storage](https://cloudmatika.co.id/blog-detail/object-storage) dan protokol [S3](https://idcloudhost.com/blog/mengenal-protokol-object-storage-s3/).

## Peringatan
Saat ini hanya mendukung SLiMS di cabang ```develop``` bagi anda yang masih menggunakan SLiMS versi 9.6.1 segara upgrade ke versi ```develop``` (Segala risiko ditanggung sendiri).

## Adapter
Secara bawaan *adapter* untuk berkomunikasi dengan *storage server* sebagai berikut:
* [Biznet Gio Object Storage](#biznet-gio-object-storage)
### Detail penjelasan
#### Biznet Gio Object Storage
Pada layanan ini anda dapat menggunakan sub-pustaka ini dengan kloning repo ini pada folder plugin:
```bash
cd plugins/
git clone https://github.com/drajathasan/slims-object-storage
cd slims-object-storage
composer install
mkdir config/
```
Membuat konfigurasi diska anda pada direktori ```config``` yang telah dibuat sebelumnya.
```bash
nano config/disks.php
```
pada konfigurasi file diatas isi dengan skrip dibawah berikut:
```php
<?php
return [
    'repository' => [
        'provider' => \SLiMS\ObjectStorage\Gio::class,
        'options' => [
            [
                'version' => 'latest',
                'region'  => '<Region>',
                'endpoint' => '<S3 Endpoint>',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => '<key>',
                    'secret' => '<secret>'
                ],
            ],
            [
               'bucket' => '<bucket>'
            ]
        ]
    ]
];
```
Anda dapat mengganti setiap isian yang diawali karakter ```<``` dan akhiri ```>``` dengan yang anda miliki. Untuk order pemesanan dapat diakses [disini](https://www.biznetgio.com/product/neo-object-storage).

Jika anda hendak menambahkan storage lain maka bisa menulis sebagai berikut
```php
<?php
return [
    'repository' => [
        'provider' => \SLiMS\ObjectStorage\Gio::class,
        'options' => [
            [
                'version' => 'latest',
                'region'  => '<Region>',
                'endpoint' => '<S3 Endpoint>',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => '<key>',
                    'secret' => '<secret>'
                ],
            ],
            [
               'bucket' => '<bucket>'
            ]
        ]
    ],
    'backup' => [
        'provider' => \SLiMS\ObjectStorage\Gio::class,
        'options' => [
            [
                'version' => 'latest',
                'region'  => '<Region>',
                'endpoint' => '<S3 Endpoint>',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => '<key>',
                    'secret' => '<secret>'
                ],
            ],
            [
               'bucket' => '<bucket>'
            ]
        ]
    ]
    // dst
];
```

Cara penggunaan bisa anda baca [disini](https://slims.web.id/docs/development-guide/Storage/Intro/)