<?php
//etape 3.2
// la ommunication entre Billing Service et Notification Service 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Invoice;

class BillingController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/invoice/create', name: 'create_invoice', methods: ['POST'])]
    public function createInvoice(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // crée une nouvelle facture

        $invoice = new Invoice();
        $invoice->setAmount($data['amount']);
        $invoice->setDueDate(new \DateTime($data['due_date']));
        $invoice->setCustomerEmail($data['customer_email']);

        // on sauvegarde la facture dans la bdd
        $entityManager->persist($invoice);
        $entityManager->flush();

        // Envoyer une requête à Notification Service pour envoyer un email
        $notificationResponse = $this->client->request('POST', 'http://notification-service.local/send-notification', [
            'json' => [
                'sujet' => 'Billing',
                'recipient' => $data['customer_email'],
                'message' => 'Your invoice has been created.'
            ],
        ]);

        return new JsonResponse(['status' => 'Invoice created and notification sent!'], JsonResponse::HTTP_CREATED);
    }
}
