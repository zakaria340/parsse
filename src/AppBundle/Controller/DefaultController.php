<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Smalot\PdfParser\Parser;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use AppBundle\Form\SendMailType;
use AppBundle\Form\UploadDocumentsType;
use AppBundle\Form\UploadUsersType;

class DefaultController extends Controller {
  /**
   * @Route("/", name="homepage")
   */
  public function indexAction(Request $request) {
    $fs = new Filesystem();
    $path = $this->getParameter('pdf_directory');
    $pathFiles = $this->getParameter('pdf_directory_files');
    try {
      $fs->mkdir($path);
      $fs->mkdir($pathFiles);
    } catch (IOExceptionInterface $e) {
      echo "An error occurred while creating your directory at " . $e->getPath(
        );
    }
    $form = $this->createForm(SendMailType::class);
    $file = new Filesystem();

    $path = $this->getParameter('pdf_directory');
    $userPath = $path . '/users.json';
    if ($file->exists($userPath)) {
      $userFile = file_get_contents($userPath);
      $listUsers = json_decode($userFile, TRUE);
    }

    $fileName = 'documents.pdf';
    $path = $this->getParameter('pdf_directory');
    $pathDocument = $path . '/' . $fileName;

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();
      $i = 0;
      foreach ($listUsers as $user) {
        $pathFiles = $this->getParameter('pdf_directory_files');
        $pathNewDocument = $pathFiles . '/' . $user['code'] . '.pdf';
        if ($file->exists($pathNewDocument)) {
          $message = \Swift_Message::newInstance()
            ->setSubject($data['subject'])
            ->setFrom('usher340@gmail.com')
            ->setTo($user['email'])
            ->setBody(
              $this->renderView(
              // app/Resources/views/Emails/mail.html.twig
                'emails/mail.html.twig',
                array('body' => $data['body'])
              ),
              'text/html'
            )
            ->attach(\Swift_Attachment::fromPath($pathNewDocument));
          $this->get('mailer')->send($message);
          $i++;
        }
      }

      //$file->remove($pathFiles);
      //$file->remove($pathDocument);
      $this->addFlash(
        'notice',
        'Your email was sent successfully!' . ' ' . $i . ' emails'
      );

    }

    $readyUsers = $readyDocuments = FALSE;

    if (!empty($listUsers)) {
      $readyUsers = TRUE;
    }

    if ($file->exists($pathDocument)) {
      $readyDocuments = TRUE;
    }
    return $this->render(
      'default/index.html.twig',
      [
        'form' => $form->createView(),
        'readyUsers' => $readyUsers,
        'readyDocuments' => $readyDocuments,
      ]
    );
  }

  /**
   * @Route(
   *     "/view-document/{code}",
   *     name="viewDocument",
   * )
   */
  public function viewdocumentAction($code) {
    $pathFiles = $this->getParameter('pdf_directory_files');
    $pathDocument = $pathFiles . '/' . $code . '.pdf';

    return new BinaryFileResponse($pathDocument);
  }

  /**
   * @Route(
   *     "/delete-files/{code}",
   *     name="deletefiles",
   * )
   */
  public function deletedocumentsAction($code) {
    $file = new Filesystem();
    $path = $this->getParameter('pdf_directory');
    $pathFiles = $this->getParameter('pdf_directory_files');
    if ($code == 'users') {
      $userPath = $path . '/users.json';
      $file->remove($userPath);
      $this->addFlash(
        'notice',
        'Users deleted successfully!'
      );
      return $this->redirectToRoute('users');
    }
    if ($code == 'documents') {
      $fileName = 'documents.pdf';
      $pathDocument = $path . '/' . $fileName;
      $file->remove($pathDocument);
      $file->remove($pathFiles);
      $this->addFlash(
        'notice',
        'Documents deleted successfully!'
      );
      return $this->redirectToRoute('documents');
    }
  }

  /**
   * @Route("/documents", name="documents")
   */
  public function documentsAction(Request $request) {
    $listUsers = $this->getListUsers();
    $pathFiles = $this->getParameter('pdf_directory_files');
    $form = $this->createForm(UploadDocumentsType::class);
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
      $pathDocument = $path . '/' . $fileName;
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
          $pathNewDocument = $pathFiles . '/' . $matricule . '.pdf';
          exec(
            "pdftk $pathDocument cat $key output $pathNewDocument"
          );
        }
      }
      $this->addFlash(
        'notice',
        'Documents uploaded successfully!'
      );
    }

    return $this->render(
      'default/documents.html.twig',
      [
        'form' => $form->createView(),

        'base_dir' => realpath(
          $this->getParameter('kernel.root_dir') . '/..'
        ),
        'users' => $listUsers,
      ]
    );
  }

  /**
   * @Route("/users", name="users")
   */
  public function usersAction(Request $request) {
    $listUsers = $this->getListUsers();
    $form = $this->createForm(UploadUsersType::class);
    $form->handleRequest($request);

    // Check if we are posting stuff
    if ($form->isSubmitted() && $form->isValid()) {
      // Get file
      $file = $form->get('submitFile');
      $filename = $file->getData();
      $listUsers = array();
      if (($handle = fopen($filename, "r")) !== FALSE) {
        fgetcsv($handle);
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
          $user =
            [
              'code' => utf8_encode(trim($data[0])),
              'name' => utf8_encode(trim($data[1])),
              'firstname' => utf8_encode(trim($data[2])),
              'email' => utf8_encode(trim($data[3])),
            ];
          array_push($listUsers, $user);
        }
        fclose($handle);
      }
      $json = json_encode($listUsers);
      $file = new Filesystem();
      $path = $this->getParameter('pdf_directory');
      $userPath = $path . '/users.json';
      $file->dumpFile($userPath, $json);

      $this->addFlash(
        'notice',
        'Users uploaded successfully!'
      );
    }

    return $this->render(
      'default/users.html.twig',
      [
        'form' => $form->createView(),
        'users' => $listUsers,
      ]
    );
  }

  /**
   * Return List Users.
   */
  public function getListUsers() {
    $file = new Filesystem();
    $listUsers = array();
    $path = $this->getParameter('pdf_directory');
    $pathFiles = $this->getParameter('pdf_directory_files');
    $userPath = $path . '/users.json';
    if ($file->exists($userPath)) {
      $userFile = file_get_contents($userPath);
      $listUsers = json_decode($userFile, TRUE);
      foreach ($listUsers as $key => $value) {
        $pathNewDocument = $pathFiles . '/' . $value['code'] . '.pdf';
        $file = new Filesystem();
        if ($file->exists($pathNewDocument)) {
          $listUsers[$key]['exist'] = TRUE;
        }
        else {
          $listUsers[$key]['exist'] = FALSE;
        }
      }
    }
    return $listUsers;
  }
}
