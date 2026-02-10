<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoNodeProxyService
{
    private const GEONODE_API_URL = 'https://proxylist.geonode.com/api/proxy-list';

    public function getProxies(int $limit = 50): array
    {
        try {
            $response = Http::get(self::GEONODE_API_URL, [
                'limit' => $limit,
                'page' => 1,
                'sort_by' => 'lastChecked',
                'sort_type' => 'desc',
                'protocols' => 'http,https',
            ]);

            if (!$response->successful()) {
                Log::error('GeoNode API failed: ' . $response->status());
                return [];
            }

            $data = $response->json();
            $proxies = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $proxy) {
                    $proxies[] = [
                        'url' => strtolower($proxy['protocols'][0]) . '://' . $proxy['ip'] . ':' . $proxy['port'],
                        'ip' => $proxy['ip'],
                        'port' => $proxy['port'],
                        'protocol' => $proxy['protocols'][0],
                    ];
                }
            }

            return $proxies;

        } catch (\Exception $e) {
            Log::error('GeoNode Service Error: ' . $e->getMessage());
            return [];
        }
    }
}
