<?php

namespace Smile\HipaySyliusPlugin\Payum\Action;

use App\Entity\Payment\PaymentSecurityToken;
use HiPay\Fullservice\Enum\Transaction\TransactionState;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Psr\Log\LoggerInterface;
use Smile\HipaySyliusPlugin\Api\ApiOneyConfig;
use Smile\HipaySyliusPlugin\Api\CreateTransaction;
use Smile\HipaySyliusPlugin\Api\HipayStatus;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

final class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private LoggerInterface $logger;
    private CreateTransaction $createTransaction;
    private ApiOneyConfig $apiOney;

    public function __construct(LoggerInterface $logger, CreateTransaction $createTransaction)
    {
        $this->logger = $logger;
        $this->createTransaction = $createTransaction;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        /** @var PaymentSecurityToken $token */
        $token = $request->getToken();
        $gateway = $token->getGatewayName();
        try {
            if ($gateway === HipayOney3GatewayFactory::FACTORY_NAME || $gateway === HipayOney4GatewayFactory::FACTORY_NAME) {
                $transaction = $this->createTransaction->createOney($payment, $gateway, $gatewayConfig->getConfig());
            } else {
                $transaction = $this->createTransaction->create($payment, $token);
            }
        } catch (\Exception $exception) {
            $this->logErrors($exception->getMessage());
            dd($exception);
            return;
        }

        switch ($transaction->getState()) {
            case TransactionState::COMPLETED:
            case TransactionState::PENDING:
                $payment->setDetails([
                    'status' => HipayStatus::CODE_STATUS_CAPTURED,
                    'hipay_order_id' => $transaction->getMid(),
                    'transaction_id' => $transaction->getTransactionReference(),
                    'payum_token' => $token->getHash(),
                ]);

                break;
            case TransactionState::FORWARDING:
                $forwardUrl = $transaction->getForwardUrl();
                $payment->setDetails([
                     'status' => HipayStatus::CODE_STATUS_PENDING,
                     'hipay_order_id' => $transaction->getMid(),
                     'transaction_id' => $transaction->getTransactionReference(),
                     'payum_token' => $token->getHash(),
                 ]);

                throw new HttpPostRedirect($forwardUrl);

            case TransactionState::ERROR:
            case TransactionState::DECLINED:
                $reason = $transaction->getReason();
                $this->logErrors('There was an error requesting new transaction: ' . $reason['message']);

                break;
            default:
                throw new HipayException('An error occured, process has been cancelled.');
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof PaymentInterface
        ;
    }

    private function logErrors(string $message): void
    {
        $this->logger->error($message);
    }
}
