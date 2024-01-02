<?php

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class LocaleResolver implements ValueResolverInterface
{
    /** @var string */
    private $defaultLocale;

    public function __construct(string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (
            $argumentType != LocaleRequest::class
            && !is_subclass_of($argumentType, LocaleRequest::class, true)
        ) {
            return [];
        }

        $localeRequest = new LocaleRequest();

        $localeRequest->needLocale = $request->query->has('locale');
        $localeRequest->locale = $request->query->get('locale', $this->defaultLocale);
        $contextLocales = $request->query->get('contentLocale', $localeRequest->locale);

        $localeRequest->locale = str_replace(
            ['pt-br', 'zh-cn', 'zh-tw', 'es-mx'],
            ['pt', 'cn', 'cn', 'es'],
            $localeRequest->locale
        );
        $contextLocales = str_replace(
            ['pt-br', 'zh-cn', 'zh-tw', 'es-mx'],
            ['pt', 'cn', 'cn', 'es'],
            $contextLocales
        );

        $showAll = $request->query->has('showAll');
        $localeRequest->contentLocales = explode(',', $contextLocales);
        $localeRequest->bestContentLocale = current($localeRequest->contentLocales);
        if ($showAll) {
            $localeRequest->contentLocales = [];
        }

        return [$localeRequest];
    }
}
