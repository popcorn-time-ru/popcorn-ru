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
        $localeRequest->contentLocales = explode(',', $contextLocales);
        $localeRequest->bestContentLocale = current($localeRequest->contentLocales);
        // if (!in_array($localeRequest->locale, $this->extractLocales)) {
        //     $localeRequest->locale = $this->defaultLocale;
        // }
        $request->attributes->set($configuration->getName(), $localeRequest);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getConverter() === 'locale_params' && $configuration->getClass() === LocaleRequest::class;
    }
}
