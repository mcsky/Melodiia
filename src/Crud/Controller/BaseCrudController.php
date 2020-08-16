<?php

namespace SwagIndustries\Melodiia\Crud\Controller;

use SwagIndustries\Melodiia\Bridge\Symfony\Response\FormErrorResponse;
use SwagIndustries\Melodiia\Crud\CrudControllerInterface;
use SwagIndustries\Melodiia\Response\ApiResponse;
use SwagIndustries\Melodiia\Response\WrongDataInput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Zend\Json\Exception\RuntimeException;
use Zend\Json\Json;

abstract class BaseCrudController implements CrudControllerInterface
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * @return ApiResponse|FormInterface
     */
    protected function decodeInputData(FormFactoryInterface $formFactory, string $form, Request $request, bool $clearMissing = null, object $data = null)
    {
        if (null === $clearMissing) {
            $clearMissing = !in_array($request->getMethod(), ['POST', 'PUT']);
        }
        try {
            $form = $formFactory->createNamed('', $form, $data);
            $inputData = Json::decode($request->getContent(), Json::TYPE_ARRAY);
            $form->submit($inputData, $clearMissing);

            if (!$form->isSubmitted()) {
                return new WrongDataInput();
            }

            if (!$form->isValid()) {
                return new FormErrorResponse($form);
            }
        } catch (RuntimeException $e) {
            return new WrongDataInput();
        }

        return $form;
    }

    protected function dispatch($event, string $eventName)
    {
        $this->dispatcher->dispatch($event, $eventName);
    }
}
