<?php

namespace Creonit\MarkupBundle\Event;

use App\Model\User;
use Creonit\MarkupBundle\Markup;
use Creonit\RestBundle\Exception\RestErrorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;
use Twig\Error\RuntimeError;

class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Markup
     */
    protected $markup;
    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    protected $normalizedUser;

    public function __construct(Markup $markup, Environment $twig, TokenStorageInterface $tokenStorage, NormalizerInterface $serializer)
    {
        $this->markup = $markup;
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['injectTwigVariables', 0]
            ],
            KernelEvents::EXCEPTION => [
                ['onException', 0]
            ]
        ];
    }

    public function injectTwigVariables(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (preg_match('#^/api/#', $event->getRequest()->getPathInfo())) {
            return;
        }

        $this->twig->addGlobal('request', $this->serializer->normalize($event->getRequest()));

        if ($token = $this->tokenStorage->getToken() and ($user = $token->getUser()) and ($user instanceof User)) {
            if (null === $this->normalizedUser) {
                $this->twig->addGlobal('user', $this->normalizedUser = $this->serializer->normalize($user));
            }
        }
    }

    public function onException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof RuntimeError) {
            $previousException = $exception->getPrevious();
            if ($previousException instanceof RestErrorException) {
                $event->stopPropagation();

                switch ($previousException->getCode()) {
                    case 404:
                        throw new NotFoundHttpException($previousException->getMessage());
                    case 403:
                    case 401:
                        throw new AccessDeniedHttpException($previousException->getMessage());
                    default:
                        throw $previousException;
                }
            }
        }
    }
}