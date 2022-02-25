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

namespace Smile\HipaySyliusPlugin\Payum\Action;

use HiPay\Fullservice\Enum\Transaction\TransactionState;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Psr\Log\LoggerInterface;
use Smile\HipaySyliusPlugin\Api\CreateTransaction;
use Smile\HipaySyliusPlugin\Factory\NotifyTokenFactory;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Sylius\Component\Core\Model\PaymentInterface;
use Throwable;

final class CaptureAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;
    use GatewayFactoryNameGetterTrait;

    private CreateTransaction $createTransaction;
    private NotifyTokenFactory $notifyTokenFactory;
    private LoggerInterface $logger;

    public function __construct(
        CreateTransaction $createTransaction,
        NotifyTokenFactory $notifyTokenFactory,
        LoggerInterface $logger
    ) {
        $this->createTransaction = $createTransaction;
        $this->notifyTokenFactory = $notifyTokenFactory;
        $this->logger = $logger;
    }

    /**
     * @var Capture $request
     */
    public function execute($request): void
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        try {
            $transaction = $this->createTransaction->create(
                $payment,
                $request->getToken(),
                $this->notifyTokenFactory->getNotifyToken($payment, $this->tokenFactory)
            );
        } catch (Throwable $exception) {
            // @todo Handle code 3010004 => Duplicate order
            $this->logger->error($exception->getMessage());
            return;
        }

        $paymentDetails = $payment->getDetails();
        $paymentDetails['hipay_responses'][] = $transaction->toArray();
        $payment->setDetails($paymentDetails);

        // Check if we need to force the redirection to a third party url
        if (TransactionState::FORWARDING === $transaction->getState()) {
            throw new HttpRedirect($transaction->getForwardUrl());
        }

    }

    public function supports($request): bool
    {
        if (!$request instanceof Capture || !$request->getFirstModel() instanceof PaymentInterface) {
            return false;
        }

        return in_array(
            $this->getGatewayFactoryName($request->getModel()->getMethod()->getGatewayConfig()),
            [
                HipayCardGatewayFactory::FACTORY_NAME,
                HipayMotoCardGatewayFactory::FACTORY_NAME,
                HipayOney3GatewayFactory::FACTORY_NAME,
                HipayOney4GatewayFactory::FACTORY_NAME,
            ]
        );
    }
}
