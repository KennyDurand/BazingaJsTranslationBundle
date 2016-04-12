<?php

namespace Bazinga\Bundle\JsTranslationBundle\Dumper;

use Bazinga\Bundle\JsTranslationBundle\Finder\TranslationFinder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Adrien Russo <adrien.russo.qc@gmail.com>
 */
class TranslationDumper
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var TranslationFinder
     */
    private $finder;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $loaders = array();

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array List of locales translations to dump
     */
    private $activeLocales;

    /**
     * @var array List of domains translations to dump
     */
    private $activeDomains;

    /**
     * @var string
     */
    private $localeFallback;

    /**
     * @var string
     */
    private $defaultDomain;

    /**
     * @param EngineInterface   $engine         The engine.
     * @param TranslationFinder $finder         The translation finder.
     * @param RouterInterface   $router         The router.
     * @param FileSystem        $filesystem     The file system.
     * @param string            $localeFallback
     * @param string            $defaultDomain
     */
    public function __construct(
        EngineInterface $engine,
        TranslationFinder $finder,
        RouterInterface $router,
        Filesystem $filesystem,
        $localeFallback = '',
        $defaultDomain  = '',
        array $activeLocales = array(),
        array $activeDomains = array()
    ) {
        $this->engine         = $engine;
        $this->finder         = $finder;
        $this->router         = $router;
        $this->filesystem     = $filesystem;
        $this->localeFallback = $localeFallback;
        $this->defaultDomain  = $defaultDomain;
        $this->activeLocales  = $activeLocales;
        $this->activeDomains  = $activeDomains;
    }

    /**
     * Get array of active locales
     */
    public function getActiveLocales()
    {
        return $this->activeLocales;
    }

    /**
     * Get array of active locales
     */
    public function getActiveDomains()
    {
        return $this->activeDomains;
    }

    /**
     * Add a translation loader if it does not exist.
     *
     * @param string          $id     The loader id.
     * @param LoaderInterface $loader A translation loader.
     */
    public function addLoader($id, $loader)
    {
        if (!array_key_exists($id, $this->loaders)) {
            $this->loaders[$id] = $loader;
        }
    }

    /**
     * Dump all translation files.
     *
     * @param string $target Target directory.
     * @param array $skipDomains Domains to skip.
     */
    public function dump($target, $skipDomains = [])
    {
        $translations = $this->getTranslations($skipDomains);

        $content = $this->engine->render('BazingaJsTranslationBundle::getTranslations.js.twig', array(
            'translations'      => $translations,
            'fallback'          => $this->localeFallback,
            'defaultDomain'     => $this->defaultDomain,
            'include_config'    => true,
        ));

        $this->filesystem->mkdir(dirname($target));

        if (file_exists($target)) {
            $this->filesystem->remove($target);
        }

        file_put_contents($target, $content);
    }

    /**
     * @param array $skipDomains Domains to skip.
     *
     * @return array
     */
    private function getTranslations(array $skipDomains)
    {
        $translations = array();
        $activeLocales = $this->activeLocales;
        $activeDomains = $this->activeDomains;
        foreach ($this->finder->all() as $file) {
            list($extension, $locale, $domain) = $this->getFileInfo($file);

            if ( (count($activeLocales) > 0 && !in_array($locale, $activeLocales)) || (count($activeDomains) > 0 && !in_array($domain, $activeDomains)) ) {
                continue;
            }

            if (in_array($domain, $skipDomains)) {
                continue;
            }

            if (!isset($translations[$locale])) {
                $translations[$locale] = array();
            }

            if (!isset($translations[$locale][$domain])) {
                $translations[$locale][$domain] = array();
            }

            if (isset($this->loaders[$extension])) {
                $catalogue = $this->loaders[$extension]
                    ->load($file, $locale, $domain);

                $translations[$locale][$domain] = array_replace_recursive(
                    $translations[$locale][$domain],
                    $catalogue->all($domain)
                );
            }
        }

        return $translations;
    }

    private function getFileInfo($file)
    {
        $filename  = explode('.', $file->getFilename());
        $extension = end($filename);
        $locale    = prev($filename);

        $domain = array();
        while (prev($filename)) {
            $domain[] = current($filename);
        }
        $domain = implode('.', $domain);

        return array($extension, $locale, $domain);
    }
}
