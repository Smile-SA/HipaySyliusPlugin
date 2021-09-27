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

namespace Smile\HipaySyliusPlugin\Context;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PaymentContext
{
    public const HIPAY_TOKEN = 'hipay_token';
    public const HIPAY_PAYMENT_PRODUCT = 'hipay_payment_product';

    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function set(string $code, $value): void
    {
        if (null === $value) {
            return;
        }
        $this->session->set($code, $value);
    }

    public function remove(string $code): void
    {
        $this->session->remove($code);
    }

    public function get(string $code)
    {
        if ($this->session->has($code)) {
            return $this->session->get($code);
        }

        return null;
    }
}
