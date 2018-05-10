<?php
namespace Com\Tqdev\CrudApi;

use Com\Tqdev\CrudApi\Api\ApiService;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Cache\NoCache;
use Com\Tqdev\CrudApi\Cache\TempFileCache;
use Com\Tqdev\CrudApi\Controller\CacheController;
use Com\Tqdev\CrudApi\Controller\DataController;
use Com\Tqdev\CrudApi\Controller\MetaController;
use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\MetaService;
use Com\Tqdev\CrudApi\Router\CorsMiddleware;
use Com\Tqdev\CrudApi\Router\GlobRouter;

class Api
{
    private $router;
    private $responder;
    private $debug;

    public function __construct(Config $config)
    {
        $db = new GenericDB(
            $config->getDriver(),
            $config->getAddress(),
            $config->getPort(),
            $config->getDatabase(),
            $config->getUsername(),
            $config->getPassword()
        );
        switch ($config->getCacheType()) {
            case 'TempFile':
                $cache = new TempFileCache($config->getCachePath(), false);
                break;
            default:
                $cache = new NoCache();
        }
        $meta = new MetaService($db, $cache, $config->getCacheTime());
        $responder = new Responder();
        $router = new GlobRouter($responder);
        new CorsMiddleware($router, $responder, $config->getAllowedOrigins());
        $api = new ApiService($db, $meta);
        new DataController($router, $responder, $api);
        new MetaController($router, $responder, $meta);
        new CacheController($router, $responder, $cache);
        $this->router = $router;
        $this->responder = $responder;
        $this->debug = $config->getDebug();
    }

    public function handle(Request $request): Response
    {
        $response = null;
        try {
            $response = $this->router->route($request);
        } catch (\Throwable $e) {
            if ($e instanceof \PDOException) {
                if (strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
                    return $this->responder->error(ErrorCode::DUPLICATE_KEY_EXCEPTION, '');
                }
                if (strpos(strtolower($e->getMessage()), 'constraint') !== false) {
                    return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
                }
            }
            $response = $this->responder->error(ErrorCode::ERROR_NOT_FOUND, $e->getMessage());
            if ($this->debug) {
                $response->addHeader('X-Debug-Info', 'Exception in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        }
        return $response;
    }
}
