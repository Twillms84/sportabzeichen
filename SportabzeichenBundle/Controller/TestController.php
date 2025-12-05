<?php
namespace PulsR\SportabzeichenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/hello", name="hello_world")
     */
    public function hello(): Response
    {
        return new Response('Hallo IServ 🧩 – Modul reagiert!');
    }
}
