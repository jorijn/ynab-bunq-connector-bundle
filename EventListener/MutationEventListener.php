<?php

namespace Jorijn\YNAB\BunqConnectorBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Jorijn\SymfonyBunqBundle\Event\MutationEvent;
use Jorijn\YNAB\BunqConnectorBundle\Exception\PaymentReceivedForUnknownAccountException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use YNAB\Client\TransactionsApi;
use YNAB\Model\SaveTransactionWrapper;

class MutationEventListener
{
    use LoggerAwareTrait;

    /** @var TransactionsApi */
    protected $transactionsApi;
    /** @var array */
    protected $connections;

    /**
     * MutationEventListener constructor.
     *
     * @param TransactionsApi $transactionsApi
     * @param LoggerInterface $logger
     */
    public function __construct(TransactionsApi $transactionsApi, LoggerInterface $logger, array $connections = [])
    {
        $this->setLogger($logger);
        $this->transactionsApi = $transactionsApi;
        $this->connections = new ArrayCollection($connections);
    }

    /**
     * @param MutationEvent $event
     */
    public function onMutation(MutationEvent $event)
    {
        $payment = $event->getPayment();
        $accountId = $payment->getMonetaryAccountId();
        $amount = (float) $payment->getAmount()->getValue() * 1000;
        $description = $payment->getDescription();
        $counterPartyName = $payment->getCounterpartyAlias()->getDisplayName();
        $date = (new \DateTime($payment->getCreated()))->format('Y-m-d');

        // get the connection from the configuration
        $connection = $this->connections->filter(function ($entry) use ($accountId) {
            return $entry['bunq_account_id'] === $accountId;
        })->first();

        // write to log
        $this->logger->info('payment event received for ynab', [
            'bunq_account_id' => $accountId,
            'amount' => $amount,
            'description' => $description,
            'counter_party_name' => $counterPartyName,
            'date' => $date,
        ]);

        // validate account
        if (false === $connection) {
            throw new PaymentReceivedForUnknownAccountException(sprintf(
                'Received transaction for unknown account ID %s',
                $accountId
            ));
        }

        $ynabBudgetId = $connection['ynab_budget_id'];
        $ynabAccountId = $connection['ynab_account_id'];

        // create the ynab transaction
        $transactionWrapper = new SaveTransactionWrapper([
            'transaction' => [
                'account_id' => $ynabAccountId,
                'date' => $date,
                'amount' => $amount,
                'payee_id' => null,
                'payee_name' => $counterPartyName,
                'category_id' => null,
                'memo' => $description,
                'cleared' => 'cleared',
                'approved' => false,
                'flag_color' => null,
                'import_id' => $payment->getId(),
            ],
        ]);

        // post it to ynab
        try {
            $transactionId = $this->transactionsApi->createTransaction($ynabBudgetId, $transactionWrapper)
                ->getData()
                ->getTransaction()
                ->getId();

            $this->logger->info('transaction succesfully created', ['id' => $transactionId]);
        } catch (\Throwable $exception) {
            $this->logger->error('could not create transaction', ['exception' => $exception]);
        }
    }
}
