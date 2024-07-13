<?php
// etape3.1
// Ce code montre la communication entre Order Service et Billing Service 

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Order;


class OrderController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/order/create', name: 'create_order', methods: ['POST'])]
    public function createOrder(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // crée une nouvelle commande

        $order = new Order();
        $order->setProductId($data['product_id']);
        $order->setCustomerEmail($data['customer_email']);
        $order->setQuantity($data['quantity']);
        $order->setTotalPrice($data['total_price']);

        // maintenant on sauvegarde la commande dans la bdd
        $entityManager->persist($order);
        $entityManager->flush();

        // on envoie une requête à Billing Service afin qu'elle crée une facture
        $response = $this->client->request('POST', 'http://billing-service.local/create-invoice', [
            'json' => [
                'amount' => $data['total_price'],
                'due_date' => (new \DateTime('+30 days'))->format('Y-m-d'),
                'customer_email' => $data['customer_email']
            ],
        ]);

        return new JsonResponse(['status' => 'Order created and invoice requested!'], JsonResponse::HTTP_CREATED);
    }
}

?>
