<?php

namespace App\Http;

use Illuminate\Http\Response as BaseResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Response Class
 *
 * @category Libraries
 * @author Mohamed Hamdallah <moh8med@gmail.com>
 */
class Response
{
    /**
     * HTTP status codes translation table.
     * 
     * @var array
     */
    protected $status;

    /**
     * Meta attributes array.
     * 
     * @var array
     */
    protected $meta;

    /**
     * Default response options
     * 
     * @var array
     */
    protected $options = [
        'charset' => 'utf-8',
        'json_protection' => true
    ];

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->status = BaseResponse::$statusTexts;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Returns JSON response code
     * 
     * @param  array   $data
     * @param  int $status_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function json($data = [], $status_code = 200, $headers = [])
    {
        if (env('DUMP_QUERY_LOG')) {
            dd(app('db')->getQueryLog());
        }

        if ( ! isset($this->status[$status_code])) {
            return $this->internalServerError('No status text available. Please check your status code number or supply your own message text.');
        }

        $data['status'] = $this->status[$status_code];
        $data['status_code'] = $status_code;

        if ($this->meta) {
            $data['meta'] = $this->meta;
        }

        $headers['Content-Type'] = 'application/json; charset='.$this->option('charset');
        // $headers = $this->getDefaultHeaders();

        $response = response()->json($data, $status_code, $headers);

            // return $response;
        if (app()->environment('testing') || ! $this->option('json_protection')) {
            return $response;
        }

        $json = ")]}',\n" . $response->getContent();
        return $response->setContent($json);
    }

    /**
     * Return the raw contents of a binary file.
     * 
     * @param  \App\File $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function file(\App\File $file, $headers = [])
    {
        // $headers['Content-Type'] = 'application/pdf; charset='.$this->option('charset');

        $directory = $file->directory ? $file->directory.'/' : '';
        $path = public_path("storage/{$directory}{$file->filename}.{$file->extension}");

        $headers['Access-Control-Allow-Methods'] = 'HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS';
        $headers['Access-Control-Allow-Headers'] = app('request')->header('Access-Control-Request-Headers');
        $headers['Access-Control-Allow-Origin'] = '*';
        $headers['Access-Control-Max-Age'] = '86400';

        return new BinaryFileResponse($path, 200, $headers);
    }

    // protected function getDefaultHeaders()
    // {
    //  return [
    //      'Access-Control-Allow-Methods' => 'HEAD, GET, POST, PUT, PATCH, DELETE',
    //      'Access-Control-Allow-Headers' => app('request')->header('Access-Control-Request-Headers'),
    //      'Access-Control-Allow-Origin' => '*',
    //      'Access-Control-Max-Age' => '86400',
    //      'Content-Type' => 'application/json; charset='.$this->option('charset'),
    //  ];
    // }

    /**
     * Sets the meta attributes.
     * 
     * @param  array $data
     * @return Response
     */
    public function meta(array $data = [])
    {
        $this->meta = $data;
        return $this;
    }

    /**
     * Returns JSON success response
     * 
     * @param  int $status_code
     * @param  array $data
     * @return Response
     */
    public function success($data, $status_code = 200, $headers = [])
    {
        $data = is_string($data) ? ['messages' => [$data]] : $data;
        return $this->json(['data' => $data], $status_code, $headers);
    }

    /**
     * Returns JSON error response
     * 
     * @param  int $status_code
     * @param  array $messages
     * @return Response
     */
    public function error($messages, $status_code = 400, $headers = [])
    {
        // $messages = is_string($messages) ? ['message' => $messages] : ['messages' => $messages];
        $messages = is_string($messages) ? ['messages' => [$messages]] : ['messages' => $messages];

        return $this->json(['error' => $messages], $status_code, $headers);
    }

    public function unauthorized($messages = null)
    {
        if (! $messages) {
            $messages = 'Invalid or expired authentication token.';
            // $messages = 'Authentication Failed: Invalid or expired token.';
        }

        return $this->error($messages, 401);
    }

    public function forbidden($messages = null)
    {
        if (! $messages) {
            // $messages = 'You are not permitted to perform the requested operation.';
            $messages = 'Insufficient privileges to perform this action.';
        }

        return $this->error($messages, 403);
    }

    public function notFound($messages = null)
    {
        if (! $messages) {
            $messages = 'The resource you request was not found.';
            // $messages = 'The page you requested was not found, use another endpoint instead.';
        }

        return $this->error($messages, 404);
    }

    public function conflict($messages = null)
    {
        if (! $messages) {
            $messages = 'The request could not be completed due to a conflict with the current state of the target resource.';
        }

        return $this->error($messages, 409);
    }

    public function validationErrors($messages = null)
    {
        if (! $messages || ! isset($messages['messages'])) {
            $messages['messages'][] = 'The given data failed to pass validation.';
        }

        if (isset($messages['fields']) && $messages['fields'] instanceof \Illuminate\Support\MessageBag) {
            $fields = [];

            foreach ($messages['fields']->keys() as $key) {
                if (strpos($key, '.') !== false) {
                    $new_key = explode('.', $key)[0];
                    $fields[$new_key] = $messages['fields']->get($key);
                } else {
                    $fields[$key] = $messages['fields']->get($key);
                }
            }

            $messages['fields'] = $fields;
        }

        return $this->json(['error' => $messages], 422);
    }

    public function internalServerError($messages = null)
    {
        if (! $messages) {
            $messages = 'An unexpected error occurred.';
        }

        return $this->error($messages, 500);
    }

    /**
     * Returns JSON error response
     * 
     * @param  array $messages
     * @param  int $status_code
     * @return Response
     */
    public function paginate($data, $status_code = 200, $headers = [])
    {
        $res['data'] = isset($data['data']) ? $data['data'] : array();

        // Prepare URL query params
        $params = [];

        if (app('request')->has('with')) {
            $params['with'] = app('request')->input('with');
        }
        if (app('request')->has('limit')) {
            $params['limit'] = (int) app('request')->input('limit');
        }
        if (app('request')->has('token')) {
            $params['token'] = request_token();
            // $params['token'] = \Auth::user()->api_token;
            // $params['token'] = app('request')->input('token');
        }

        $url_params = http_build_query($params);

        if ($data['next_page_url']) {
            $data['next_page_url'] .= '&'.$url_params;
        }

        if ($data['prev_page_url']) {
            $data['prev_page_url'] .= '&'.$url_params;
        }

        // $res['meta']['paging'] = array_except($data, 'data');
        $this->meta['paging'] = array_except($data, 'data');

        return $this->json($res, $status_code);
    }

    /**
     * Checks an option
     * 
     * @param  string $option
     * @return mixed
     */
    protected function option($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }
}
