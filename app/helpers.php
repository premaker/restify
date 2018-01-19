<?php

if (! function_exists('request_with')) {
    /**
     * Checks the 'with' query parameter
     * 
     * @param  string $with
     * @return boolean
     */
    function request_with($with)
    {
        // static $_with_collection;

        // if (! isset($_with_collection)) {
            if (! app('request')->has('with')) {
                return false;
            }

            $_with_collection = collect(explode(',', app('request')->input('with')));
        // }

        return $_with_collection->contains($with);
    }
}

if (! function_exists('per_page')) {
    /**
     * Returns number of items to display in page
     * 
     * @return integer
     */
    function per_page()
    {
        static $_items_per_page;

        if (isset($_items_per_page)) {
            return $_items_per_page;
        }

        return $_items_per_page = app('request')->has('limit') ? (int) app('request')->input('limit') : 25;
    }
}

if (! function_exists('request_token')) {
    /**
     * Fetches the request token from the header/input array
     * 
     * @return integer
     */
    function request_token()
    {
        static $_request_token;

        if (isset($_request_token)) {
            return $_request_token;
        }

        $request = app('request');

        return $_request_token = $request->header('Authorization') ? str_replace('Bearer ', '', (string) $request->header('Authorization')) : $request->input('token');
    }
}

if (! function_exists('json_response')) {
    /**
     * Returns an instance of \App\Http\Response
     * 
     * @return \App\Http\Response
     */
    function json_response(array $options = [])
    {
        return new \App\Http\Response($options);
    }
}
