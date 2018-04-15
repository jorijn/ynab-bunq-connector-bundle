<?php

namespace Jorijn\YNAB\BunqConnectorBundle\Command;

use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Object\NotificationFilter;
use bunq\Model\Generated\Object\Pointer;
use Jorijn\SymfonyBunqBundle\Component\Command\ApiHelper;
use Jorijn\SymfonyBunqBundle\Component\Traits\ApiContextAwareTrait;
use Jorijn\SymfonyBunqBundle\Model\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use YNAB\Client\AccountsApi;
use YNAB\Client\BudgetsApi;

class VerifyConfigurationCommand extends Command
{
    use ApiContextAwareTrait;

    const IBAN = 'IBAN';
    const UNKNOWN = 'UNKNOWN';
    const NOTIFICATION_DELIVERY_METHOD_URL = 'URL';
    const NOTIFICATION_CATEGORY_MUTATION = 'MUTATION';
    const SYMFONY_BUNQ_CALLBACK_URL = 'symfony_bunq.callback_url';
    const OK = 'OK';
    const NOT_OK = 'NOT OK';

    /** @var AccountsApi */
    protected $accountsApi;
    /** @var BudgetsApi */
    protected $budgetsApi;
    /** @var array */
    protected $connections;
    /** @var RouterInterface */
    protected $router;
    /** @var ApiHelper */
    protected $apiHelper;

    /**
     * ListBudgetsCommand constructor.
     *
     * @param string          $name
     * @param AccountsApi     $accountsApi
     * @param BudgetsApi      $budgetsApi
     * @param ApiHelper       $apiHelper
     * @param RouterInterface $router
     * @param array           $connections
     */
    public function __construct(
        string $name,
        AccountsApi $accountsApi,
        BudgetsApi $budgetsApi,
        ApiHelper $apiHelper,
        RouterInterface $router,
        array $connections
    ) {
        parent::__construct($name);

        $this->accountsApi = $accountsApi;
        $this->budgetsApi = $budgetsApi;
        $this->connections = $connections;
        $this->router = $router;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Configures the Command instance.
     */
    protected function configure()
    {
        $this->setDescription('This command verifies and summarizes current configuration');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$api = $this->apiHelper->restore($this->getHelper('question'), $input, $output)) {
            return;
        }

        $user = $this->apiHelper->currentUser();

        $output->writeln(PHP_EOL.'<info>BUNQ CONFIGURATION</info>'.PHP_EOL);

        $table = new Table($output);
        $table->setHeaders(['Configuration', 'Status']);
        $table->addRow(['Person', $user->getBunqUser()->getLegalName()]);
        $table->addRow(['Environment', $api->getEnvironmentType()->getChoiceString()]);
        $table->addRow(['Callback URL', $this->getUrl()]);
        $table->addRow(['Callback Status', $this->getCallbackStatus($user) ? self::OK : self::NOT_OK]);
        $table->render();

        $output->writeln(PHP_EOL.'<info>YNAB CONFIGURATION</info>'.PHP_EOL);

        $table = new Table($output);
        $table->setHeaders(['bunq Account', 'bunq IBAN', 'YNAB Budget', 'YNAB Account']);

        foreach ($this->connections as $connection) {
            $bunqAccount = MonetaryAccountBank::get($connection['bunq_account_id'])->getValue();

            $ynabBudget = $this->budgetsApi->getBudgetById(
                $connection['ynab_budget_id']
            )->getData()->getBudget()->getName();

            $ynabAccount = $this->accountsApi->getAccountById(
                $connection['ynab_budget_id'],
                $connection['ynab_account_id']
            )->getData()->getAccount()->getName();

            $table->addRow([
                $bunqAccount->getDescription(),
                $this->getIbanForBankAccount($bunqAccount),
                $ynabBudget,
                $ynabAccount,
            ]);
        }

        $table->render();
    }

    protected function getUrl(): string
    {
        return $this->router->generate(
            self::SYMFONY_BUNQ_CALLBACK_URL,
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    protected function getCallbackStatus(User $user): bool
    {
        $allCurrentNotificationFilter = $user->getBunqUser()->getNotificationFilters();

        /** @var NotificationFilter $filter */
        foreach ($allCurrentNotificationFilter as $filter) {
            if (
                self::NOTIFICATION_CATEGORY_MUTATION === $filter->getCategory()
                && self::NOTIFICATION_DELIVERY_METHOD_URL === $filter->getNotificationDeliveryMethod()
                && $filter->getNotificationTarget() === $this->getUrl()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param MonetaryAccountBank $bankAccount
     *
     * @return string
     */
    protected function getIbanForBankAccount(MonetaryAccountBank $bankAccount): string
    {
        /** @var Pointer $alias */
        foreach ($bankAccount->getAlias() as $alias) {
            if (self::IBAN === $alias->getType()) {
                return $alias->getValue();
            }
        }

        return self::UNKNOWN;
    }
}
