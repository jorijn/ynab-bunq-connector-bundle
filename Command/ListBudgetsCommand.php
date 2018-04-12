<?php

namespace Jorijn\YNAB\BunqConnectorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use YNAB\Client\AccountsApi;
use YNAB\Client\BudgetsApi;

class ListBudgetsCommand extends Command
{
    /** @var AccountsApi */
    protected $accountsApi;
    /** @var BudgetsApi */
    protected $budgetsApi;

    /**
     * ListBudgetsCommand constructor.
     *
     * @param string      $name
     * @param AccountsApi $accountsApi
     * @param BudgetsApi  $budgetsApi
     */
    public function __construct(string $name, AccountsApi $accountsApi, BudgetsApi $budgetsApi)
    {
        parent::__construct($name);

        $this->accountsApi = $accountsApi;
        $this->budgetsApi = $budgetsApi;
    }

    /**
     * Configures the Command instance.
     */
    protected function configure()
    {
        $this->setDescription('This command displays all accounts and budgets registered to this API key');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Budget ID', 'Budget Name', 'Account ID', 'Account Name', 'Account Balance']);

        // fetch the budgets from ynab
        $budgets = $this->budgetsApi->getBudgets()->getData()->getBudgets();
        foreach ($budgets as $budget) {
            $accounts = $this->accountsApi
                ->getAccounts($budget->getId())
                ->getData()
                ->getAccounts();

            foreach ($accounts as $account) {
                if (true === $account->getClosed()) {
                    continue;
                }

                $table->addRow([
                    $budget->getId(),
                    $budget->getName(),
                    $account->getId(),
                    $account->getName(),
                    round($account->getBalance() / 1000, 2),
                ]);
            }
        }

        $table->render();
    }
}
