<?php

namespace App\Controller;

use Error;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/aquao/notifications/')]
class NotificationController extends AbstractController
{

    public function __construct(
        private LoggerInterface $notificationLogger
    ) {
    }

    #[Route('client/created', name: 'notification_client_created')]
    public function created(Request $request): Response
    {
        return $this->onClientNotification($request, 'CREATED');
    }

    #[Route('client/updated', name: 'notification_client_updated')]
    public function updated(Request $request): Response
    {
        return $this->onClientNotification($request, 'UPDATED');
    }

    #[Route('client/deleted', name: 'notification_client_deleted')]
    public function deleted(Request $request): Response
    {
        return $this->onClientNotification($request, 'DELETED');
    }

    #[Route('client/anonymized', name: 'notification_client_anonymized')]
    public function anonymized(Request $request): Response
    {
        return $this->onClientNotification($request, 'ANONYMIZED');
    }


    private function onClientNotification(Request $request, string $status): Response
    {
        $uid = $request->headers->get('x-aquao-notification-uid');
        $signature = $request->headers->get('x-aquao-notification-sign');

        try {
            $requestBody = $request->getContent();
            $this->checkSignature($requestBody, $signature);
            $client = json_decode($requestBody, true);
            $this->notificationLogger->notice("SUCCESS `$status` notification for client " . $client['code']);
        } catch (\Throwable $th) {
            $this->notificationLogger->notice("FAILED `$status` notification : " . $th->getMessage());
        }

        return new Response('', 204);
    }

    private function checkSignature(string $data, string $signature)
    {

        if (!$signature) {
            throw new Error("missing signature");
        }

        $computed = base64_encode(hash_hmac(
            "sha256",
            $data,
            $this->getParameter('app.notification_secret'),
            true
        ));

        if ($computed != $signature) {
            throw new Error("bad signature `$signature` from computed `$computed`");
        }
    }
}
