<?php
/*
 * This file is part of the HipaySyliusPlugin
 *
 * (c) Smile <dirtech@smile.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Smile\HipaySyliusPlugin\Controller;

use Smile\HipaySyliusPlugin\Form\Type\HipayOneyRequiredFieldsType;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\PayPalPlugin\Processor\LocaleProcessorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class HostedFieldController extends AbstractController
{
    private Environment $twig;
    private ChannelContextInterface $channelContext;
    private LocaleContextInterface $localeContext;
    private LocaleProcessorInterface $localeProcessor;
    private ApiCredentialRegistry $apiCredentialRegistry;

    public function __construct(
        Environment $twig,
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext,
        LocaleProcessorInterface $localeProcessor,
        ApiCredentialRegistry $apiCredentialRegistry
    ) {
        $this->twig = $twig;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
        $this->localeProcessor = $localeProcessor;
        $this->apiCredentialRegistry = $apiCredentialRegistry;
    }

    public function renderHostedFieldsAction(Request $request): Response
    {
        $gateway = $request->attributes->get('gateway') ?? null;
        if (null === $gateway) {
            throw new \LogicException('Unable to find gateway in request');
        }

        $config = $this->apiCredentialRegistry->getApiConfig($gateway);
        try {
            return new Response(
                $this->twig->render(
                    '@SmileHipaySyliusPlugin/Checkout/hipay_hosted_fields.html.twig',
                    [
                        'locale' => $this->localeProcessor->process($this->localeContext->getLocaleCode()),
                        'gateway' => $gateway,
                        'apiConfig' => [
                            'username' => $config->getUsername(),
                            'password' => $config->getPassword(),
                            'stage' => $config->getStage(),
                            'locale' => $config->getLocale(),
                        ],
                    ]
                )
            );
        } catch (\InvalidArgumentException $exception) {
            return new Response('');
        }
    }
}
