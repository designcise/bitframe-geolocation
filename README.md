# BitFrame\Locale\GeoLocation

Geo Location wrapper class to fetch user geo data as a middleware.

### Installation

See [installation docs](https://www.bitframephp.com/middleware/locale/geolocation) for instructions on installing and using this middleware.

### Usage Example

```
use \BitFrame\Locale\GeoLocation;
use \BitFrame\Locale\GeoLocationData;

require 'vendor/autoload.php';

$app = new \BitFrame\Application;

$app->run([
    /* In order to output the http response from the middlewares, 
     * make sure you include a response emitter middleware, for example:
     * \BitFrame\Message\DiactorosResponseEmitter::class, */
    GeoLocation::class,
    function($request, $response, $next) {
        $loc = $request->getAttribute(GeoLocationData::class);

        $response->getBody()->write('<pre>' . print_r($loc, true) . '</pre>');

        return $response;
    }
]);
```

### Tests

To execute the test suite, you will need [PHPUnit](https://phpunit.de/).

### Contributing

* File issues at https://github.com/designcise/bitframe-geolocation/issues
* Issue patches to https://github.com/designcise/bitframe-geolocation/pulls

### Documentation

Documentation is available at:

* https://www.bitframephp.com/middleware/locale/geolocation/

### License

Please see [License File](LICENSE.md) for licensing information.