<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Smalot\PdfParser\Parser;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use AppBundle\Entity\Document;
use AppBundle\Form\DocumentType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $path = $this->getParameter('pdf_directory');
        $pathFiles = $this->getParameter('pdf_directory_files');
        $file = new Filesystem();
        try {
            $file->mkdir($path);
            $file->mkdir($pathFiles);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your directory at ".$e->getPath();
        }

        $listUsers = array();
        $path = $this->getParameter('pdf_directory');
        $userPath = $path.'/users.json';
        if ($file->exists($userPath)) {
            $userFile = file_get_contents($userPath);
            $listUsers = json_decode($userFile);
        }

        // replace this example code with whatever you need
        return $this->render(
          'default/index.html.twig',
          [
            'base_dir' => realpath(
              $this->getParameter('kernel.root_dir').'/..'
            ),
            'users' => $listUsers,
          ]
        );
    }

    /**
     * @Route(
     *     "/view-document/{code}",
     *     name="viewDocument",
     * )
     */
    public function viewdocumentAction($code)
    {
        $pathFiles = $this->getParameter('pdf_directory_files');
        $pathDocument = $pathFiles.'/'.$code.'.pdf';

        return new BinaryFileResponse($pathDocument);
    }

    /**
     * @Route("/documents", name="documents")
     */
    public function documentsAction(Request $request)
    {
        $form = $this->createFormBuilder()
          ->add(
            'submitFile',
            FileType::class,
            ['label' => 'Document to Send']
          )
          ->add(
            'save',
            SubmitType::class,
            ['label' => 'Valider']
          )
          ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('submitFile')->getData();
            $fileName = 'documents.pdf';
            // Generate a unique name for the file before saving it
            $file->move(
              $this->getParameter('pdf_directory'),
              $fileName
            );

            $path = $this->getParameter('pdf_directory');
            $pathFiles = $this->getParameter('pdf_directory_files');
            $pathDocument = $path.'/'.$fileName;
            $parser = new Parser();
            $pdf = $parser->parseFile($pathDocument);
            $pages = $pdf->getPages();
            foreach ($pages as $key => $page) {
                $string = $page->getText();
                $string = preg_replace('/\s+/', '', $string);
                preg_match('/Retraite(.*?)Solde/', $string, $display);
                if (preg_match(
                    '/Retraite(.*?)Solde/',
                    $string,
                    $display
                  ) === 1
                ) {
                    $matricule = $display[1];
                    $pathNewDocument = $pathFiles.'/'.$matricule.'.pdf';
                    exec(
                      "pdftk $pathDocument cat $key output $pathNewDocument"
                    );
                }
            }

        }

        return $this->render(
          'default/documents.html.twig',
          [
            'form' => $form->createView(),
          ]
        );
    }

    /**
     * @Route("/users", name="users")
     */
    public function usersAction(Request $request)
    {
        $file = new Filesystem();
        $listUsers = array();
        $path = $this->getParameter('pdf_directory');
        $userPath = $path.'/users.json';
        if ($file->exists($userPath)) {
            $userFile = file_get_contents($userPath);
            $listUsers = json_decode($userFile);
        }
        $form = $this->createFormBuilder()
          ->add(
            'submitFile',
            FileType::class,
            ['label' => 'File to Submit']
          )
          ->add(
            'save',
            SubmitType::class,
            ['label' => 'Valider']
          )
          ->getForm();
        $form->handleRequest($request);
        // Check if we are posting stuff
        if ($form->isSubmitted() && $form->isValid()) {
            // Get file
            $file = $form->get('submitFile');
            $filename = $file->getData();
            $listUsers = array();
            if (($handle = fopen($filename, "r")) !== false) {
                fgetcsv($handle);
                while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                    $user =
                      [
                        'code' => trim($data[0]),
                        'name' => trim($data[1]),
                        'firstname' => trim($data[2]),
                        'email' => trim($data[3]),
                      ];
                    array_push($listUsers, $user);
                }
                fclose($handle);
            }
            $json = json_encode($listUsers);
            $file = new Filesystem();
            $file->dumpFile($userPath, $json);
        }

        return $this->render(
          'default/users.html.twig',
          [
            'form' => $form->createView(),
            'users' => $listUsers,
          ]
        );
    }
}
