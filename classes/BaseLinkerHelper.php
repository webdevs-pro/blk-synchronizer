<?php

class BaseLinkerHelper
{
    private string $token = '';

    /**
     * @param array $apiParams
     * @return mixed
     * @throws Exception
     */
    public function exec(array $apiParams)
    {
        $blk_settings = get_option( 'blk_settings' );
        $this->token = $blk_settings['blk_api_token'] ?? '';
        
        $curl = curl_init('https://api.baselinker.com/connector.php');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["X-BLToken: $this->token"]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($apiParams));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);

        if (!$response) {
            curl_close($curl);
            throw new Exception('ERROR: Empty response from BLK - Request:'. json_encode($apiParams));
        }

        $response = json_decode($response, true);

        if (empty($response['status']) || $response['status'] === 'ERROR') {
            curl_close($curl);
            throw new Exception('ERROR:'. json_encode($response));
        }

        curl_close($curl);

        return $response['products'];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getProductsList(): array
    {
        $page = 1;
        $lists = [];
        do {
            $apiParams = [
                'method' => 'getProductsList',
                'parameters' => '{"storage_id": "bl_1", "page": ' . $page . '}',
            ];
            $result = $this->exec($apiParams);
            $resultCount = count($result);
            $lists[] = $result;
            $page++;
        } while ($resultCount !== 0);
        return array_merge(...$lists);
    }

    /**
     * @param $productIds
     * @return array
     * @throws Exception
     */
    public function getProducts($productIds): array
    {
        $productIds = array_chunk($productIds, 1000);
        $lists = [];
        foreach ($productIds as $key => $chunkIds) {
            $apiParams = [
                'method' => 'getProductsData',
                'parameters' => '{"storage_id": "bl_1", "products": [' . implode(', ', $chunkIds) . '], "page": ' . ($key + 1) . '}',
            ];
            $result = $this->exec($apiParams);
            $lists[] = $result;
        }
        return array_merge(...$lists);
    }
}
