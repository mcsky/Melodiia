<?php

namespace Biig\Melodiia\Crud\Controller;

use Biig\Melodiia\Bridge\Symfony\Response\FormErrorResponse;
use Biig\Melodiia\Crud\CrudableModelInterface;
use Biig\Melodiia\Crud\CrudControllerInterface;
use Biig\Melodiia\Crud\FilterCollectionFactoryInterface;
use Biig\Melodiia\Crud\Persistence\DataStoreInterface;
use Biig\Melodiia\Exception\MelodiiaLogicException;
use Biig\Melodiia\Response\OkContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GetAll implements CrudControllerInterface
{
    /** @var DataStoreInterface */
    private $dataStore;

    /** @var AuthorizationCheckerInterface */
    private $checker;

    /** @var FilterCollectionFactoryInterface */
    private $filterCollectionFactory;

    public function __construct(
        DataStoreInterface $dataStore,
        AuthorizationCheckerInterface $checker,
        FilterCollectionFactoryInterface $collectionFactory
    ) {
        $this->dataStore = $dataStore;
        $this->checker = $checker;
        $this->filterCollectionFactory = $collectionFactory;
    }

    public function __invoke(Request $request)
    {
        // Metadata you can specify in routing definition
        $modelClass = $request->attributes->get(self::MODEL_ATTRIBUTE);
        $securityCheck = $request->attributes->get(self::SECURITY_CHECK, null);
        $groups = $request->attributes->get(self::SERIALIZATION_GROUP, []);

        if (
            empty($modelClass) || !class_exists($modelClass)
            || !is_subclass_of($modelClass, CrudableModelInterface::class)
        ) {
            throw new MelodiiaLogicException('If you use melodiia CRUD classes, you need to specify a model.');
        }

        if ($securityCheck && !$this->checker->isGranted($securityCheck)) {
            throw new AccessDeniedException(\sprintf('Access denied to data of type "%s".', $modelClass));
        }

        $filters = $this->filterCollectionFactory->createCollection($modelClass);
        $form = $filters->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            return new FormErrorResponse($form);
        }

        $page = $request->query->getInt('page', 1);
        $maxPerPage = $request->attributes->get(self::MAX_PER_PAGE_ATTRIBUTE, 30);

        $items = $this->dataStore->getPaginated($modelClass, $page, $filters, $maxPerPage);

        return new OkContent($items, $groups);
    }
}
