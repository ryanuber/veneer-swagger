<?php
/**
 * veneer - An Experimental API Framework for PHP
 *
 * @author     Ryan Uber <ru@ryanuber.com>
 * @copyright  Ryan Uber <ru@ryanuber.com>
 * @link       https://github.com/ryanuber/veneer
 * @license    http://opensource.org/licenses/MIT
 * @package    veneer
 * @category   api
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace veneer\endpoint\swagger;

/**
 * An endpoint to massage typical veneer documentation data normally
 * exposed via the HTTP OPTIONS method into a format consumable by the
 * swagger specification. This allows you to expose interactive
 * documentation using swagger-ui, without writing any additional code
 * or even modifying any of your existing endpoints. Just drop this in
 * and point swagger-ui at it!
 */
class v1 extends \veneer\call
{
    public $get = array(
        '/' => array(
            'function' => 'swagger',
            'output_handler' => 'json'
        ),
        '/fetch' => array(
            'function' => 'fetch',
            'output_handler' => 'json'
        )
    );

    public function swagger($args)
    {
        $apis = array();
        foreach (\veneer\util::get_endpoints() as $version => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $endpoint != 'swagger' && $apis[] = array(
                    'path' => "/v1/swagger/fetch?endpoint=/{$version}/{$endpoint}",
                    'description' => ''
                );
            }
        }
        return $this->response->set(array(
            'apiVersion' => '0.1',
            'swaggerVersion' => '1.1',
            'basePath' => 'http'.(array_key_exists('HTTPS', $_SERVER)?'s':'').'://'.$_SERVER['HTTP_HOST'],
            'apis' => $apis
        ), 200);
    }

    public function fetch($args)
    {
        $docs = \veneer\util::get_documentation();
        list($null, $version, $endpoint) = explode('/', $args['endpoint']);
        $out = $docs[$endpoint][$version];
        $result = array(
            'apiVersion' => 'v1',
            'swaggerVersion' => '1.1',
            'basePath' => 'http'.(array_key_exists('HTTPS', $_SERVER)?'s':'').'://'.$_SERVER['HTTP_HOST'],
            'resourcePath' => $args['endpoint'],
            'apis' => array(),
            'models' => array()
        );

        foreach ($out as $method => $apis) {
            foreach ($apis as $api => $data) {
                is_array($data) || $data = array();
                array_key_exists('parameters', $data) || $data['parameters'] = array();
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

                /**
                 * veneer-swagger can't support route splats, because they do not describe
                 * a concrete endpoint route.
                 */
                if (strpos($route, '*')) {
                    continue;
                }

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
                $current['description'] = '';
                if (array_key_exists('description', $data)) {
                    $current['description'] = str_replace(
                        "\n",
                        '<br>',
                        $data['description']
                    );
                }
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

?>
