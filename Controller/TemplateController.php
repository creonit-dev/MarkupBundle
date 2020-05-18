<?php

namespace Creonit\MarkupBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TemplateController extends AbstractController
{
    public function renderAction(Request $request, Environment $twig)
    {
        $template = $twig->load($request->attributes->get('template', 'static.page.twig'));
        $response = new Response();

        if ($request->headers->has('X-Fragment')) {
            $fragment = $request->headers->get('X-Fragment') . '_fragment';
            if (!$template->hasBlock($fragment)) {
                throw $this->createNotFoundException();
            }

            $content = $template->renderBlock($fragment);
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        } else {
            $content = $template->render();
        }

        return $response->setContent($content);
    }
}