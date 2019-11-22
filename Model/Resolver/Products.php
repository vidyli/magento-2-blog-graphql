<?php

declare(strict_types=1);

namespace Mageplaza\BlogGraphQl\Model\Resolver;

use Mageplaza\Blog\Helper\Data;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mageplaza\BlogGraphQl\Model\Resolver\Filter\Query\Filter;

/**
 * Class Products
 * @package Mageplaza\BlogGraphQl\Model\Resolver
 */
class Products implements ResolverInterface
{

    /**
     * @var Data
     */
    private $_helperData;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Filter
     */
    protected $filterQuery;

    /**
     * Products constructor.
     *
     * @param Data $helperData
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Filter $filterQuery
     */
    public function __construct(
        Data $helperData,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Filter $filterQuery
    ) {
        $this->_helperData           = $helperData;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterQuery           = $filterQuery;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->vaildateArgs($args);
        $searchCriteria = $this->searchCriteriaBuilder->build('products', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);
        $post       = $this->_helperData->getFactoryByType()->create()->load($args['postId']);
        $collection = $post->getSelectedProductsCollection();
        $searchResult = $this->filterQuery->getResult($searchCriteria, 'product', $collection);

        //possible division by 0
        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $searchCriteria->getCurrentPage();
        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items'       => $searchResult->getItemsSearchResult()
        ];
    }

    /**
     * @param array $args
     *
     * @throws GraphQlInputException
     */
    private function vaildateArgs(array $args): void
    {
        if (!isset($args['postId'])) {
            throw new GraphQlInputException(__('postId value is not Null.'));
        }

        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
    }
}
