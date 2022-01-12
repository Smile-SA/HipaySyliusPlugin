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

final class OneyCustomerController extends AbstractController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderOneyCustomerIframe(Request $request): Response
    {
        $gateway = $request->attributes->get('gateway') ?? null;
        if (null === $gateway) {
            throw new \LogicException('Unable to find gateway in request');
        }

        return new Response(
            $this->twig->render(
                '@SmileHipaySyliusPlugin/Iframe/hipay_oney_iframe.html.twig',
                [
                    'gateway' => $gateway
                ]
            )
        );
    }
}
