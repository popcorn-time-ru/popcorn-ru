<?php

namespace App\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class LocaleParamConverter implements ParamConverterInterface
{
    /** @var array */
    private $extractLocales;

    /** @var string */
    private $defaultLocale;

    public function __construct(array $extractLocales, string $defaultLocale)
    {
        $this->extractLocales = $extractLocales;
        $this->defaultLocale = $defaultLocale;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $localeRequest = new LocaleRequest();

        $localeRequest->needLocale = $request->query->has('locale');
        $localeRequest->locale = $request->query->get('locale', $this->defaultLocale);
        $contextLocales = $request->query->get('contentLocale', $localeRequest->locale);

        $localeRequest->locale = str_replace(
            ['pt-br', 'zh-cn', 'zh-tw', 'es-mx'],
            ['pt', 'cn', 'cn', 'es'],
            $localeRequest->locale
        );
        $localeRequest->contentLocales = str_replace(
            ['pt-br', 'zh-cn', 'zh-tw', 'es-mx'],
            ['pt', 'cn', 'cn', 'es'],
            $localeRequest->contentLocales
        );

        $showAll = $request->query->has('showAll');
        $localeRequest->contentLocales = explode(',', $contextLocales);
        $localeRequest->bestContentLocale = current($localeRequest->contentLocales);
        if ($showAll) {
            $localeRequest->contentLocales = [];
        }

        $request->attributes->set($configuration->getName(), $localeRequest);
        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getConverter() === 'locale_params' && $configuration->getClass() === LocaleRequest::class;
    }
}
