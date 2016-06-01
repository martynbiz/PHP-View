<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/PHP-View
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/PHP-View/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Views;

use \InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PhpRenderer
 * @package Slim\Views
 *
 * Render PHP view scripts into a PSR-7 Response object
 */
class PhpRenderer
{
    /**
     * @var array
     */
    protected $templatePaths;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * SlimRenderer constructor.
     *
     * @param string|array $templatePath
     * @param array $attributes
     */
    public function __construct($templatePaths = "", $attributes = [])
    {
        // $templatePaths will be stored as an array, so convert if string
        if (! is_array($templatePaths)) {
            $templatePaths = array($templatePaths);
        }

        // ensure that each path is suffixed with slash
        foreach($templatePaths as $i => $templatePath) {
            $chr = substr($templatePaths[$i],-1);
            if ($chr !== '/') {
                $templatePaths[$i] .= '/';
            }
        }

        $this->templatePaths = $templatePaths;
        $this->attributes = $attributes;
    }

    /**
     * Render a template
     *
     * $data cannot contain template as a key
     *
     * throws RuntimeException if $templatePath . $template does not exist
     *
     * @param ResponseInterface $response
     * @param string             $template
     * @param array              $data
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function render(ResponseInterface $response, $template, array $data = [])
    {
        $output = $this->fetch($template, $data);

        $response->getBody()->write($output);

        return $response;
    }

    /**
     * Get the attributes for the renderer
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the attributes for the renderer
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Add an attribute
     *
     * @param $key
     * @param $value
     */
    public function addAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    /**
     * Retrieve an attribute
     *
     * @param $key
     * @return mixed
     */
    public function getAttribute($key) {
        if (!isset($this->attributes[$key])) {
            return false;
        }

        return $this->attributes[$key];
    }

    /**
     * Get the template path. Old function, use when dealing with a single path
     *
     * @return string|array
     */
    public function getTemplatePath()
    {
        return $this->templatePaths[0];
    }

    /**
     * Get the template paths as an array
     *
     * @return array
     */
    public function getTemplatePaths()
    {
        return $this->templatePaths;
    }

    /**
     * Set the template path
     *
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath)
    {
        array_push($this->templatePaths, $templatePath);
    }

    /**
     * Renders a template and returns the result as a string
     *
     * cannot contain template as a key
     *
     * throws RuntimeException if $templatePath . $template does not exist
     *
     * @param $template
     * @param array $data
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function fetch($template, array $data = []) {
        if (isset($data['template'])) {
            throw new \InvalidArgumentException("Duplicate template key found");
        }

        // loop through each $templatePaths and look for the template
        $foundPath = null;
        foreach ($this->templatePaths as $templatePath) {
            if (is_file($templatePath . $template)) {
                $foundPath = $templatePath . $template;
            }
        }
        if (is_null($foundPath)) {
            throw new \RuntimeException("View cannot render `$template` because the template does not exist");
        }


        /*
        foreach ($data as $k=>$val) {
            if (in_array($k, array_keys($this->attributes))) {
                throw new \InvalidArgumentException("Duplicate key found in data and renderer attributes. " . $k);
            }
        }
        */
        $data = array_merge($this->attributes, $data);

        ob_start();
        $this->protectedIncludeScope($foundPath, $data);
        $output = ob_get_clean();

        return $output;
    }

    /**
     * @param string $template
     * @param array $data
     */
    protected function protectedIncludeScope ($template, array $data) {
        extract($data);
        include $template;
    }
}
