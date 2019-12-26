# flarum-react

A simple library to serve Flarum over ReactPHP HTTP servers.

## Installation

```
composer require ordinaryjellyfish/flarum-react
```

## Usage

In your Flarum's `index.php` file:
```php
require '../vendor/autoload.php';

use OrdinaryJellyfish\FlarumReact\Server as FlarumServer;
use React\Promise\Promise;

$loop = React\EventLoop\Factory::create();

$server = new React\Http\Server(function ($request) {
    return new Promise(function ($resolve) use ($request) {
        $flarumServer = (new FlarumServer(
            $request,
            Flarum\Foundation\Site::fromPaths([
                'base' => __DIR__.'/..',
                'public' => __DIR__.'/../public',
                'storage' => __DIR__.'/../storage',
            ])
        ));
        $flarumServer->listen();

        $resolve($flarumServer->getResponse());
    });
});

$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);

$loop->run();
```
Visit http://localhost:8080 and voila! The beauty of Flarum arises. Note that this example does not handle static file serving. I may implement a handler to make everything simpler.

