<?php namespace Skosh\Twig;

use Parsedown;
use Skosh\Builder;
use Skosh\Twig\Extensions\SocialShare;

/**
 * A twig extension for Skosh
 */
class Extension extends \Twig_Extension
{
    /**
     * Application instance.
     *
     * @var \Skosh\Builder
     */
    private $builder;

    /**
     * Holds a list of global variables.
     *
     * @var array
     */
    private $globals = array();

    public function __construct(Builder $builder, array $globals = array())
    {
        $this->builder = $builder;
        $this->globals = $globals;
    }

    public function initRuntime(\Twig_Environment $env)
    {
        // Add an escaper for XML
        $env->getExtension('core')->setEscaper('xml', function($env, $content) {
            return htmlentities($content, ENT_COMPAT | ENT_XML1);
        });
    }

    public function getFunctions()
    {
        return array(
            'isCurrent'   => new \Twig_Function_Method($this, 'functionIsCurrent'),
            'file_exists' => new \Twig_Function_Method($this, 'functionFileExists'),
            'asset'       => new \Twig_Function_Method($this, 'functionGetAsset'),
            'share_link'  => new \Twig_Function_Method($this, 'functionShareLink', ['is_safe' => ['twig', 'html']]),
            'editButton'  => new \Twig_Function_Method($this, 'functionEditButton', [
                'needs_environment' => true,
                'is_safe'           => ['twig', 'html']
            ]),
        );
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('markdown', [$this, 'filterMarkdown'], ['is_safe' => ['twig', 'html']]),
            new \Twig_SimpleFilter('truncate', [$this, 'filterTruncate'], ['is_safe' => ['twig', 'html']]),
            new \Twig_SimpleFilter('wordwrap', [$this, 'filterWordwrap'], ['is_safe' => ['twig', 'html']])
        ];
    }

    public function getGlobals()
    {
        return $this->globals;
    }

    public function getName()
    {
        return 'skosh';
    }

    public function functionFileExists($file)
    {
        return file_exists($this->builder->target.$file);
    }

    public function functionIsCurrent($page, $pattern)
    {
        return str_is($pattern, $page);
    }

    public function functionShareLink($network, $page)
    {
        return SocialShare::generate($network, $page);
    }

    public function functionGetAsset($path)
    {
        return $this->builder->getAsset($path);
    }

    public function functionEditButton(\Twig_Environment $environment, $page, $parent, $template)
    {
        // Must be markdown and have a parent
        if ($page->type === 'markdown' && $parent)
        {
            // Get URL
            $url = $parent->get('edit_url');

            if (!$url) {
                return;
            }

            // Parse URL
            $edit_url = str_replace([
                '{filename}'
            ], [
                $page->filename
            ], $url);

            // Render button template
            return $environment->render($template, array(
                'edit_url' => $edit_url,
                'page'     => $page
            ));
        }
    }

    public function filterMarkdown($string)
    {
        return Parsedown::instance()->parse($string);
    }

    public function filterTruncate($string, $width)
    {
        return current(explode("\n", wordwrap($string, $width, "...\n")));
    }

    public function filterWordwrap($string, $prefix, $sufix)
    {
        if ($string) {
            return "{$prefix}{$string}{$sufix}";
        }
    }
}
