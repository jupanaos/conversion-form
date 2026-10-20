<?php

namespace App\Controller;

use App\Http\ConversionResponseFactory;
use App\Service\ConversionService;
use App\Validation\UploadedFileValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/conversions', name: 'api_conversions_')]
class ConversionController extends AbstractController
{
    public function __construct(
        private readonly UploadedFileValidator $validator,
        private readonly ConversionService $service,
        private readonly ConversionResponseFactory $responses,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $file = $this->validator->validate($request->files->get('file'));
        $csv = $this->service->convert($file->getContent());

        return $this->responses->csv(
            $csv,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.csv',
        );
    }
}
