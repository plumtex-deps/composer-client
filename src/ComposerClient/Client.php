<?php

namespace MicroweberPackages\ComposerClient;

use MicroweberPackages\App\Models\SystemLicenses;
use MicroweberPackages\ComposerClient\Traits\FileDownloader;

class Client
{
    use FileDownloader;

    public $licenses = [];
    public $packageServers = [
        'https://packages.microweberapi.com/packages.json',
    ];

    public function __construct()
    {
        //
    }

    public function setLicense($license)
    {
        $this->licenses[] = $license;
    }

    public function getPackageByName($packageName, $packageVersion = false) {

        $foundedPackage = [];
        foreach ($this->packageServers as $package) {

            $singlePackageParseUrl = parse_url($package);
            $singlePackageUrl = $singlePackageParseUrl['scheme'] .'://'. $singlePackageParseUrl['host']. '/packages/'.$packageName.'.json';

            $packageFile = $this->getPackageFile($singlePackageUrl);

            if (!empty($packageFile)) {
                foreach ($packageFile as $name => $versions) {
                    if (!is_array($versions)) {
                        continue;
                    }
                    if ($packageName == $name) {

                        $versions['latest'] = end($versions);

                        if ($packageVersion) {
                            foreach ($versions as $version => $versionData) {
                                if ($packageVersion == $version) {
                                    $foundedPackage = $versionData;
                                    break;
                                }
                            }
                        } else {
                            $foundedPackage = end($versions);
                        }
                    }

                }
            }
        }

        return $foundedPackage;
    }

    public function search($filter = array())
    {
        if (!empty($filter) && isset($filter['require_name'])) {

            $packageName = $filter['require_name'];

            $packageVersion = false;
            if (isset($filter['require_version'])) {
                $packageVersion = $filter['require_version'];
            }

            return $this->getPackageByName($packageName, $packageVersion);
        }

        foreach ($this->packageServers as $package) {
            $packageFile = $this->getPackageFile($package);
            if (!empty($packageFile)) {
                return $packageFile;
            }
        }

        return [];
    }

    public function getPackageFile($packageUrl)
    {
        $curl = curl_init();

        $headers = [];
        if (defined('MW_VERSION')) {
            $headers[] = "MW_VERSION: " . MW_VERSION;
        }
        if (!empty($this->licenses)) {
            $headers[] = "Authorization: Basic " . base64_encode(json_encode($this->licenses));
        }

        $opts = [
            CURLOPT_URL => $packageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
        ];
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($curl, $opts);

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

}
