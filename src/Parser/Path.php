<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Psr\Http\Message\RequestInterface as Request;

class Path implements ParserInterface
{
    use ExplodeTrait;

    /** @var Request */
    protected $request;

    /** @var array */
    protected $parameter;

    /** @var string */
    protected $route;

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     */
    public function __construct(Request $request, array $parameter, $route)
    {
        $this->request = $request;
        $this->parameter = $parameter;
        $this->route = $route;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $path = $this->request->getUri()->getPath();
        preg_match_all("/\{(.*?)\}/", $this->route, $variableMatchersInRoute);
        $key = str_replace(
            '{' . $this->parameter['name'] . '}',
            '(?P<' . $this->parameter['name'] . '>[^/]+)',
            $this->route
        );

        // inject other path variables too into the final regexp key to be able to match it
        if (!empty($variableMatchersInRoute[1])) {
            foreach ($variableMatchersInRoute[1] as $variableMatcherInRoute) {
                $key = str_replace(
                    '{' . $variableMatcherInRoute . '}',
                    '(?P<' . $variableMatcherInRoute . '>[^/]+)',
                    $key
                );
            }
        }

        $key = "@{$key}@";
        if (!preg_match($key, $path, $pathMatches)) {
            return;
        }
        if (!isset($pathMatches[$this->parameter['name']])) {
            return null;
        }
        $value = $pathMatches[$this->parameter['name']];
        if ($this->parameter['type'] === 'array') {
            $value = $this->explodeValue($value, $this->parameter);
        }

        return $value;
    }
}
