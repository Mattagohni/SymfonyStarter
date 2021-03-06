<?php

/*
 * This file is part of AppName.
 *
 * (c) Monofony
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Behat\Page\Backend\Crud;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;
use App\Behat\Service\Accessor\TableAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class IndexPage extends SymfonyPage implements IndexPageInterface
{
    /**
     * @var TableAccessorInterface
     */
    private $tableAccessor;

    /**
     * @var string
     */
    private $routeName;

    /**
     * @param Session                $session
     * @param array                  $parameters
     * @param RouterInterface        $router
     * @param TableAccessorInterface $tableAccessor
     * @param string                 $routeName
     */
    public function __construct(
        Session $session,
        array $parameters,
        RouterInterface $router,
        TableAccessorInterface $tableAccessor,
        $routeName
    ) {
        parent::__construct($session, $parameters, $router);

        $this->tableAccessor = $tableAccessor;
        $this->routeName = $routeName;
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleResourceOnPage(array $parameters)
    {
        try {
            $rows = $this->tableAccessor->getRowsWithFields($this->getElement('table'), $parameters);

            return 1 === count($rows);
        } catch (\InvalidArgumentException $exception) {
            return false;
        } catch (ElementNotFoundException $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnFields($columnName)
    {
        return $this->tableAccessor->getIndexedColumn($this->getElement('table'), $columnName);
    }

    /**
     * {@inheritdoc}
     */
    public function sortBy($fieldName)
    {
        $sortableHeaders = $this->tableAccessor->getSortableHeaders($this->getElement('table'));
        Assert::keyExists($sortableHeaders, $fieldName, sprintf('Column "%s" is not sortable.', $fieldName));

        $sortableHeaders[$fieldName]->find('css', 'a')->click();
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleResourceWithSpecificElementOnPage(array $parameters, $element)
    {
        try {
            $rows = $this->tableAccessor->getRowsWithFields($this->getElement('table'), $parameters);

            if (1 !== count($rows)) {
                return false;
            }

            return null !== $rows[0]->find('css', $element);
        } catch (\InvalidArgumentException $exception) {
            return false;
        } catch (ElementNotFoundException $exception) {
            return false;
        }
    }

    /**
     * @return int
     */
    public function countItems()
    {
        try {
            return $this->getTableAccessor()->countTableBodyRows($this->getElement('table'));
        } catch (ElementNotFoundException $exception) {
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteResourceOnPage(array $parameters)
    {
        $tableAccessor = $this->getTableAccessor();
        $table = $this->getElement('table');

        $deletedRow = $tableAccessor->getRowWithFields($table, $parameters);
        $actionButtons = $tableAccessor->getFieldFromRow($table, $deletedRow, 'actions');

        $actionButtons->pressButton('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function getActionsForResource(array $parameters)
    {
        $tableAccessor = $this->getTableAccessor();
        $table = $this->getElement('table');

        $resourceRow = $tableAccessor->getRowWithFields($table, $parameters);

        return $tableAccessor->getFieldFromRow($table, $resourceRow, 'actions');
    }

    /**
     * {@inheritdoc}
     */
    public function checkResourceOnPage(array $parameters): void
    {
        $tableAccessor = $this->getTableAccessor();
        $table = $this->getElement('table');

        $resourceRow = $tableAccessor->getRowWithFields($table, $parameters);
        $bulkCheckbox = $resourceRow->find('css', '.bulk-select-checkbox');

        Assert::notNull($bulkCheckbox);

        $bulkCheckbox->check();
    }

    public function filter()
    {
        $this->getElement('filter')->press();
    }

    public function bulkDelete(): void
    {
        $this->getElement('bulk_actions', ['%text%' => 'Bulk actions'])->pressButton('Delete');
        $this->getElement('confirmation_button')->click();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * @return TableAccessorInterface
     */
    protected function getTableAccessor()
    {
        return $this->tableAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'bulk_actions' => '.accordion:contains("%text%")',
            'confirmation_button' => '#confirmation-button',
            'filter' => 'button:contains("Filter")',
            'table' => '.table',
        ]);
    }
}
