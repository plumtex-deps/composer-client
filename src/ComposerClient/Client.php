<?php

namespace MicroweberPackages\ComposerClient;

use MicroweberPackages\App\Models\SystemLicenses;
use MicroweberPackages\ComposerClient\Traits\FileDownloader;

class Client
{
    use FileDownloader;

    public $logfile;
    public $licenses = [];
    public $packageServers = [
        'https://market.microweberapi.com/packages/microweber/packages.json',
    ];

    public function __construct()
    {
        $this->logfile = userfiles_path() . 'install_item_log.txt';
    }

    public function search($filter = array())
    {
        $packages = [];
        foreach ($this->packageServers as $package) {

            $getRepositories = $this->getPackageFile($package);

            if (empty($filter)) {
                return $getRepositories;
            }

            foreach ($getRepositories as $packageName => $packageVersions) {

                if (!is_array($packageVersions)) {
                    continue;
                }

                if ((isset($filter['require_name']) && ($filter['require_name'] == $packageName))) {

                    $packageVersions['latest'] = end($packageVersions);

                    foreach ($packageVersions as $packageVersion => $packageVersionData) {
                        if ($filter['require_version'] == $packageVersion) {
                            $packages[] = $packageVersionData;
                            break;
                        }
                    }
                }

            }
        }

        return $packages;
    }

    public function getPackageFile($packageUrl)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $packageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode(json_encode($this->licenses))
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return ["error" => "cURL Error #:" . $err];
        } else {
            $getPackages = json_decode($response, true);
            if (isset($getPackages['packages']) && is_array($getPackages['packages'])) {
                return $getPackages['packages'];
            }
            return [];
        }
    }

    public function newLog($log)
    {
        @file_put_contents($this->logfile, $log . PHP_EOL);
    }

    public function clearLog()
    {
        @file_put_contents($this->logfile, '');
    }

    public function log($log)
    {
        @file_put_contents($this->logfile, $log . PHP_EOL, FILE_APPEND);
    }
}
