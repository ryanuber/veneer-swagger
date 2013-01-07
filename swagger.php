<?php
namespace veneer\endpoint\api_docs;
class v1 extends \veneer\call
{
    public $get = array(
        '/' => array('function' => 'api_docs', 'response_detail' => false),
        '/fetch' => array('function' => 'fetch', 'response_detail' => false)
    );
    public function api_docs($args)
    {
        $apis = array();
        foreach (\veneer\util::get_endpoints() as $version => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $apis[] = array(
                    'path' => "/v1/api_docs/fetch?endpoint=/{$version}/{$endpoint}",
                    'description' => ''
                );
            }
        }
        return $this->response->set(array(
            'apiVersion' => '0.1',
            'swaggerVersion' => '1.1',
            'basePath' => 'http://'.$_SERVER['HTTP_HOST'],
            'apis' => $apis
        ), 200);
    }
    public function fetch($args)
    {
        $ch = curl_init('http://'.$_SERVER['HTTP_HOST'].$args['endpoint']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $out = json_decode(curl_exec($ch), true);
        $result = array(
            'apiVersion' => 'v1',
            'swaggerVersion' => '1.1',
            'basePath' => 'http://'.$_SERVER['HTTP_HOST'],
            'resourcePath' => $args['endpoint'],
            'apis' => array(),
            'models' => array()
        );

        foreach ($out as $method => $apis) {
            foreach ($apis as $api => $data) {
                $current = array();
                $params  = array();
                foreach (explode('/', $api) as $part) {
                    if (preg_match('~^:([^/]+)$~', $part, $matches)) {
                        $param = array(
                            'name' => $matches[1],
                            'paramType' => 'path',
                            'dataType'  => 'string',
                            'required'  => true
                        );
                        if (array_key_exists($matches[1], $data['parameters'])) {
                            $param = array_merge($param, $data['parameters'][$matches[1]]);
                        }
                        array_push($params, $param);
                    }
                }
                $route = preg_replace('~/:([^/]+)~', '/{$1}', $args['endpoint'].$api);
                if (is_array($data) && is_array($data['parameters'])) {
                    foreach ($data['parameters'] as $param_name => $param_data) {
                        $param = array();
                        $param['name'] = $param_name;
                        $param['dataType'] = 'string';
                        $param['paramType'] = 'query';
                        $found = false;
                        foreach ($params as $p) {
                            if ($p['name'] == $param_name) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            array_push($params, array_merge($param, $param_data));
                        }
                    }
                }
                $current['path'] = $route;
                $current['description'] = (array_key_exists('description', $data) ? $data['description'] : '');
                $current['operations'][] = array(
                    'httpMethod' => strtoupper($method),
                    'nickname' => uniqid(),
                    'notes' => $current['description'],
                    'summary' => (array_key_exists('summary', $data) ? $data['summary'] : ''),
                    'parameters' => $params,
                    'errorResponses' => array()
                );
                array_push($result['apis'], $current);
            }
        }

        return $this->response->set($result, 200);
    }
}
